<?php
/**
 * LUNORA — auth layer (PDO / MySQL backed).
 * Users live in the `users` table (see schema.sql). Every query here goes
 * through a prepared statement with bound parameters — no user input is
 * ever concatenated into SQL.
 */

require_once __DIR__ . '/db.php';

// These must be defined before lunora_resume_remember_login() runs below —
// unlike function definitions, top-level const/define statements execute
// in file order, not hoisted.
const LUNORA_REMEMBER_COOKIE = 'lunora_remember';
const LUNORA_REMEMBER_DAYS   = 30;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Re-establish a session from a "remember me" token, if present, before
// anything on the page checks lunora_current_user().
lunora_resume_remember_login();

/** All users, oldest first. (Small admin-only helper — not used on hot paths.) */
function lunora_load_users(): array {
    return lunora_db()->query('SELECT * FROM users ORDER BY created_at ASC')->fetchAll();
}

function lunora_find_user_by_email(string $email): ?array {
    $stmt = lunora_db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    return $user ?: null;
}

function lunora_find_user_by_id(string $id): ?array {
    $stmt = lunora_db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ?: null;
}

/**
 * Insert a new customer account. $data: full_name, email, password (plain —
 * hashed here with password_hash before it ever touches the database).
 * Returns the stored record. Call lunora_find_user_by_email() first to
 * check for a duplicate — email has a UNIQUE constraint as a backstop.
 */
function lunora_create_user(array $data): array {
    $user = [
        'id'            => 'u_' . bin2hex(random_bytes(10)),
        'full_name'     => trim($data['full_name'] ?? ''),
        'email'         => strtolower(trim($data['email'] ?? '')),
        'password_hash' => password_hash((string) ($data['password'] ?? ''), PASSWORD_DEFAULT),
        'role'          => 'customer',
        'created_at'    => date('Y-m-d H:i:s'),
    ];

    $stmt = lunora_db()->prepare(
        'INSERT INTO users (id, full_name, email, password_hash, role, created_at)
         VALUES (:id, :full_name, :email, :password_hash, :role, :created_at)'
    );
    $stmt->execute($user);

    return $user;
}

/**
 * Update a customer's editable profile fields. Caller (profile.php) is
 * responsible for checking the new email isn't already taken by someone
 * else first, via lunora_find_user_by_email().
 */
function lunora_update_user_profile(string $userId, array $data): bool {
    $stmt = lunora_db()->prepare(
        'UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?'
    );
    return $stmt->execute([
        trim($data['full_name'] ?? ''),
        strtolower(trim($data['email'] ?? '')),
        trim($data['phone'] ?? ''),
        $userId,
    ]);
}

/** Store the (already-validated, already-saved-to-disk) profile photo path for a user. */
function lunora_update_profile_image(string $userId, string $relativePath): bool {
    $stmt = lunora_db()->prepare('UPDATE users SET profile_image = ? WHERE id = ?');
    return $stmt->execute([$relativePath, $userId]);
}

/** Returns the logged-in user's record, or null. */
function lunora_current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return lunora_find_user_by_id($_SESSION['user_id']);
}

/** True if the logged-in user has the 'admin' role. */
function lunora_is_admin(): bool {
    $u = lunora_current_user();
    return $u !== null && ($u['role'] ?? 'customer') === 'admin';
}

/**
 * Guard for admin-only pages. Redirects to the admin login (or the site
 * login, if the visitor isn't logged in at all) when the check fails.
 */
function lunora_require_admin(string $adminLoginPath = 'login.php'): void {
    if (!lunora_is_admin()) {
        header('Location: ' . $adminLoginPath);
        exit;
    }
}

/**
 * Guard for pages that require a logged-in customer (e.g. checkout).
 * Redirects to the login page, passing along the current page as a
 * "redirect" parameter so the user lands back here after logging in.
 */
function lunora_require_login(string $loginPath = 'login.php'): void {
    if (!lunora_current_user()) {
        $returnTo = $_SERVER['REQUEST_URI'] ?? '';
        header('Location: ' . $loginPath . '?redirect=' . urlencode($returnTo));
        exit;
    }
}

/**
 * Validates a "redirect" target so we only ever send users to a
 * local page (never an external URL) after login/registration.
 */
function lunora_safe_redirect_target(?string $target, string $default = 'index.php'): string {
    if (!$target) return $default;
    // Only allow same-site absolute paths (starting with a single "/") —
    // no scheme, no "//" host trick. Keeping the leading slash matters:
    // stripping it turns this into a relative link, which the browser
    // then resolves against whatever subfolder the site lives in,
    // duplicating that folder in the URL.
    if (preg_match('#^/[A-Za-z0-9_\-./]+\.php(\?[A-Za-z0-9_=&%.\-]*)?$#', $target)) {
        return $target;
    }
    return $default;
}

function lunora_login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function lunora_logout(): void {
    lunora_forget_remember_token();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---------------- "Remember me" persistent login ----------------
 * PHP's session cookie only carries a session ID; the server still
 * garbage-collects the session data after session.gc_maxlifetime
 * seconds (~24 minutes by default) regardless of the cookie's
 * expiry. A long-lived session cookie therefore does NOT keep
 * anyone logged in. Instead we issue a separate, long-lived
 * "remember me" token (selector + validator, per Barry Jaspan's
 * well-known scheme) stored hashed in auth_tokens and use it to
 * transparently re-establish a session on a later visit.
 * (LUNORA_REMEMBER_COOKIE / LUNORA_REMEMBER_DAYS are defined up top,
 * before session_start(), since lunora_resume_remember_login() runs
 * immediately after it.)
 */

/** Issue a new remember-me token for $user and set the cookie. */
function lunora_remember_login(array $user): void {
    $selector  = bin2hex(random_bytes(9));
    $validator = bin2hex(random_bytes(32));
    $expires   = time() + LUNORA_REMEMBER_DAYS * 86400;

    $stmt = lunora_db()->prepare(
        'INSERT INTO auth_tokens (selector, validator_hash, user_id, expires_at, created_at)
         VALUES (:selector, :validator_hash, :user_id, :expires_at, :created_at)'
    );
    $stmt->execute([
        'selector'       => $selector,
        'validator_hash' => hash('sha256', $validator),
        'user_id'        => $user['id'],
        'expires_at'     => date('Y-m-d H:i:s', $expires),
        'created_at'     => date('Y-m-d H:i:s'),
    ]);

    setcookie(LUNORA_REMEMBER_COOKIE, $selector . ':' . $validator, [
        'expires'  => $expires,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

/** Delete the current remember-me token (DB row + cookie), if any. */
function lunora_forget_remember_token(): void {
    if (!empty($_COOKIE[LUNORA_REMEMBER_COOKIE])) {
        [$selector] = array_pad(explode(':', $_COOKIE[LUNORA_REMEMBER_COOKIE], 2), 1, '');
        if ($selector !== '') {
            $stmt = lunora_db()->prepare('DELETE FROM auth_tokens WHERE selector = ?');
            $stmt->execute([$selector]);
        }
    }
    setcookie(LUNORA_REMEMBER_COOKIE, '', time() - 42000, '/');
}

/**
 * If there's no active session but a valid remember-me cookie is
 * present, log the associated user back in and rotate the token
 * (issuing a fresh selector/validator) so a stolen cookie is only
 * usable once. Call this early, before lunora_current_user().
 */
function lunora_resume_remember_login(): void {
    if (!empty($_SESSION['user_id']) || empty($_COOKIE[LUNORA_REMEMBER_COOKIE])) {
        return;
    }

    $parts = explode(':', $_COOKIE[LUNORA_REMEMBER_COOKIE], 2);
    if (count($parts) !== 2) {
        setcookie(LUNORA_REMEMBER_COOKIE, '', time() - 42000, '/');
        return;
    }
    [$selector, $validator] = $parts;

    $stmt = lunora_db()->prepare('SELECT * FROM auth_tokens WHERE selector = ? LIMIT 1');
    $stmt->execute([$selector]);
    $token = $stmt->fetch();

    if (!$token || strtotime($token['expires_at']) < time()
        || !hash_equals($token['validator_hash'], hash('sha256', $validator))) {
        // Invalid, expired, or mismatched — clear whatever's left so it can't be retried.
        lunora_db()->prepare('DELETE FROM auth_tokens WHERE selector = ?')->execute([$selector]);
        setcookie(LUNORA_REMEMBER_COOKIE, '', time() - 42000, '/');
        return;
    }

    $user = lunora_find_user_by_id($token['user_id']);
    lunora_db()->prepare('DELETE FROM auth_tokens WHERE selector = ?')->execute([$selector]);
    if ($user) {
        lunora_login($user);
        lunora_remember_login($user); // rotate: issue a new token for next time
    }
}

/* ---------------- CSRF ---------------- */

function lunora_csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function lunora_csrf_check(?string $token): bool {
    return is_string($token) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}

/* ---------------- Flash messages ---------------- */

function lunora_flash_set(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function lunora_flash_get(): ?array {
    if (empty($_SESSION['flash'])) return null;
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $f;
}
