<?php
require_once __DIR__ . '/auth.php';

$redirectTarget = lunora_safe_redirect_target($_GET['redirect'] ?? ($_POST['redirect'] ?? null));

if (lunora_current_user()) {
    header('Location: ' . $redirectTarget);
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';
    $csrf     = $_POST['csrf'] ?? '';

    if (!lunora_csrf_check($csrf)) {
        $errors[] = 'Your session expired — please try again.';
    } else {
        if ($name === '') $errors[] = 'Please enter your full name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (!$errors && lunora_find_user_by_email($email)) {
            $errors[] = 'An account with that email already exists.';
        }

        if (!$errors) {
            $user = lunora_create_user([
                'full_name' => $name,
                'email'     => $email,
                'password'  => $password,
            ]);
            lunora_login($user);
            lunora_flash_set('success', 'Welcome to LUNORA, ' . explode(' ', $name)[0] . '. Your account is ready.');
            header('Location: ' . $redirectTarget);
            exit;
        }
    }
}

$csrfToken = lunora_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create an Account — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner header-inner--auth">
    <span></span>
    <a href="index.php" class="wordmark">LUNORA</a>
    <span></span>
  </div>
</header>

<div class="auth-shell">
  <div class="auth-visual" aria-hidden="true">
    <img src="images/hero_model.jpg" alt="" style="object-position: center 8%;">
    <div class="auth-visual__content">
      <div class="auth-visual__mark">L</div>
      <h2>Join the edit.</h2>
      <p>Create an account to save favorites, track orders, and check out faster next time.</p>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-card">
      <a class="auth-back" href="index.php">
        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        Back to shop
      </a>
      <div class="auth-card__mark">L</div>
      <h1>Create an account</h1>
      <p class="auth-card__subtitle">It only takes a minute — you'll be shopping in no time.</p>

      <?php if ($errors): ?>
        <div class="auth-error">
          <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($redirectTarget) ?>">

        <label>Full name
          <div class="input-group">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
            <input type="text" name="fullname" value="<?= htmlspecialchars($name) ?>" placeholder="Jane Doe" required autocomplete="name">
          </div>
        </label>

        <label>Email
          <div class="input-group">
            <svg viewBox="0 0 24 24"><path d="M3 6l9 7 9-7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@example.com" required autocomplete="email">
          </div>
        </label>

        <label>Password
          <div class="input-group">
            <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" name="password" id="regPassword" class="has-toggle" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required minlength="8" autocomplete="new-password">
            <button type="button" class="pw-toggle" data-target="regPassword" aria-label="Show password">
              <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/><line x1="2" y1="22" x2="22" y2="2"/></svg>
            </button>
          </div>
          <span class="auth-hint">At least 8 characters.</span>
        </label>

        <label>Confirm password
          <div class="input-group">
            <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" name="confirm" id="regConfirm" class="has-toggle" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required autocomplete="new-password">
            <button type="button" class="pw-toggle" data-target="regConfirm" aria-label="Show password">
              <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/><line x1="2" y1="22" x2="22" y2="2"/></svg>
            </button>
          </div>
        </label>

        <button type="submit" class="auth-submit">Create Account</button>
      </form>

      <p class="auth-switch">Already have an account? <a href="login.php?redirect=<?= urlencode($redirectTarget) ?>">Log in</a></p>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.pw-toggle').forEach(function(btn){
  btn.addEventListener('click', function(){
    var input = document.getElementById(btn.getAttribute('data-target'));
    var isVisible = input.type === 'text';
    input.type = isVisible ? 'password' : 'text';
    btn.classList.toggle('is-visible', !isVisible);
    btn.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
  });
});
</script>

<footer class="site-footer">
  <div class="footer-base" style="justify-content:center; border-top:none;">
    <span class="wordmark--footer">LUNORA</span>
    <span>&copy; <?= date('Y') ?> LUNORA. All rights reserved.</span>
  </div>
</footer>

</body>
</html>
