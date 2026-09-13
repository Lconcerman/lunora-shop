<?php
require_once __DIR__ . '/../auth.php';

if (lunora_is_admin()) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrf     = $_POST['csrf'] ?? '';

    if (!lunora_csrf_check($csrf)) {
        $errors[] = 'Your session expired — please try again.';
    } else {
        $user = lunora_find_user_by_email($email);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif (($user['role'] ?? 'customer') !== 'admin') {
            $errors[] = 'This account does not have admin access.';
        } else {
            lunora_login($user);
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
<title>Admin Login — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="login-shell">
  <div class="login-card">
    <h1>LUNORA Admin</h1>
    <p class="sub">Sign in to manage orders, stock, and products.</p>

    <?php if ($errors): ?>
      <div class="auth-error">
        <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="post">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
      <label class="field">Email
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required autofocus autocomplete="username">
      </label>
      <label class="field">Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="btn" style="justify-content:center; margin-top:8px;">Log In</button>
    </form>
  </div>
</div>
</body>
</html>
