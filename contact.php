<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();

$errors = [];
$sent = false;
$name = $lunora_user['full_name'] ?? '';
$email = $lunora_user['email'] ?? '';
$subject = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '')                                        $errors[] = 'Please enter your name.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($message === '')                                     $errors[] = 'Please enter a message.';

    if (!$errors) {
        try {
            $db = lunora_db();
            $db->exec('CREATE TABLE IF NOT EXISTS contact_messages (
                id         INT AUTO_INCREMENT PRIMARY KEY,
                name       VARCHAR(190) NOT NULL,
                email      VARCHAR(190) NOT NULL,
                subject    VARCHAR(190) NOT NULL DEFAULT "",
                message    TEXT NOT NULL,
                status     VARCHAR(20) NOT NULL DEFAULT "new",
                created_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            $stmt = $db->prepare('INSERT INTO contact_messages (name, email, subject, message, status, created_at) VALUES (?, ?, ?, ?, "new", NOW())');
            $stmt->execute([$name, $email, $subject, $message]);

            $sent = true;
            $name = $lunora_user['full_name'] ?? '';
            $email = $lunora_user['email'] ?? '';
            $subject = '';
            $message = '';
        } catch (Throwable $e) {
            $errors[] = 'Something went wrong sending your message. Please try again shortly.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contact Us — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/site_header.php'; ?>

<div class="breadcrumb">
  <div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>Contact Us</span></div>
</div>

<main class="info-page">
  <div class="info-page__inner">
    <h1>Contact Us</h1>
    <p style="color:var(--ink-soft); font-size:0.94rem; margin-bottom: 6px;">Have a question about an order, a product, or anything else? Send us a message and our team will get back to you by email.</p>

    <?php if ($sent): ?>
      <p class="form-note form-note--success">Thanks — your message has been sent. We'll reply to your email shortly.</p>
    <?php endif; ?>

    <?php if ($errors): ?>
      <p class="form-note form-note--error"><?= htmlspecialchars(implode(' ', $errors)) ?></p>
    <?php endif; ?>

    <form class="contact-form" method="post" action="contact.php">
      <label>Name
        <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
      </label>
      <label>Email
        <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
      </label>
      <label>Subject
        <input type="text" name="subject" value="<?= htmlspecialchars($subject) ?>" placeholder="Order question, product question, etc.">
      </label>
      <label>Message
        <textarea name="message" required><?= htmlspecialchars($message) ?></textarea>
      </label>
      <button type="submit">Send Message</button>
    </form>
  </div>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>

<script src="script.js"></script>
</body>
</html>
