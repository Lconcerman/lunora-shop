<?php
require_once __DIR__ . '/auth.php';

if (lunora_current_user()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf     = $_POST['csrf'] ?? '';
    $remember = !empty($_POST['remember']);

    if (!lunora_csrf_check($csrf)) {
        $errors[] = 'Your session expired — please try again.';
    } else {
        $user = lunora_find_user_by_email($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } else {
            lunora_login($user);
            if ($remember) {
                lunora_remember_login($user);
            }
            lunora_flash_set('success', 'Welcome back, ' . explode(' ', $user['full_name'])[0] . '.');
            header('Location: index.php');
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
<title>Log In — LUNORA</title>
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
      <h2>Carry something considered.</h2>
      <p>Sign in to track your orders, save favorites, and check out faster next time.</p>
    </div>
  </div>

  <div class="auth-panel">
    <div class="auth-card">
      <a class="auth-back" href="index.php">
        <svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
        Back to shop
      </a>
      <div class="auth-card__mark">L</div>
      <h1>Log in</h1>
      <p class="auth-card__subtitle">Welcome back. Enter your details to continue.</p>

      <?php if ($errors): ?>
        <div class="auth-error">
          <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
      <?php endif; ?>

      <form class="auth-form" method="post" novalidate>
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">

        <label>Email
          <div class="input-group">
            <svg viewBox="0 0 24 24"><path d="M3 6l9 7 9-7"/><rect x="3" y="4" width="18" height="16" rx="2"/></svg>
            <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@example.com" required autocomplete="email">
          </div>
        </label>

        <label>Password
          <div class="input-group">
            <svg viewBox="0 0 24 24"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
            <input type="password" name="password" id="loginPassword" class="has-toggle" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required autocomplete="current-password">
            <button type="button" class="pw-toggle" data-target="loginPassword" aria-label="Show password">
              <svg class="icon-on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7Z"/><circle cx="12" cy="12" r="3"/><line x1="2" y1="22" x2="22" y2="2"/></svg>
            </button>
          </div>
        </label>

        <div class="auth-form-row">
          <label class="auth-remember">
            <input type="checkbox" name="remember" value="1">
            Remember me
          </label>
        </div>

        <button type="submit" class="auth-submit">Log In</button>
      </form>

      <p class="auth-switch">New to LUNORA? <a href="register.php">Create an account</a></p>
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
