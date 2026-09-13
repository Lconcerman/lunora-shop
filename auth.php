<?php
/**
 * LUNORA — auth layer (PDO / MySQL backed).
 * Users live in the `users` table (see schema.sql). Every query here goes
 * through a prepared statement with bound parameters — no user input is
 * ever concatenated into SQL.
 */

require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

function lunora_login(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
}

function lunora_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
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
