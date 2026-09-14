<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';

lunora_require_login('login.php');

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();
$lunora_notif_unread = lunora_count_unread_notifications($lunora_user['id']);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !lunora_csrf_check($_POST['csrf'] ?? null)) {
    lunora_flash_set('error', 'Your session expired — please try again.');
    header('Location: profile.php');
    exit;
}

// ---- Handle "update details" form ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if ($fullName === '') {
        $errors[] = 'Please enter your name.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $existing = lunora_find_user_by_email($email);
        if ($existing && $existing['id'] !== $lunora_user['id']) {
            $errors[] = 'That email is already in use by another account.';
        }
    }

    if (!$errors) {
        lunora_update_user_profile($lunora_user['id'], [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
        ]);
        lunora_flash_set('success', 'Your profile has been updated.');
        header('Location: profile.php');
        exit;
    }
}

// ---- Handle profile photo upload ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_photo') {
    $file = $_FILES['photo'] ?? null;

    if (!$file || $file['error'] === UPLOAD_ERR_NO_FILE) {
        $errors[] = 'Please choose an image to upload.';
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'The upload failed — please try again.';
    } elseif ($file['size'] > 3 * 1024 * 1024) {
        $errors[] = 'Please choose an image under 3MB.';
    } else {
        $imageInfo = @getimagesize($file['tmp_name']);
        $allowed = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_WEBP => 'webp',
            IMAGETYPE_GIF => 'gif',
        ];
        if (!$imageInfo || !isset($allowed[$imageInfo[2]])) {
            $errors[] = 'Please upload a JPG, PNG, GIF, or WEBP image.';
        } else {
            $ext = $allowed[$imageInfo[2]];
            $dir = __DIR__ . '/uploads/avatars';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $filename = $lunora_user['id'] . '_' . time() . '.' . $ext;
            $destination = $dir . '/' . $filename;

            // Remove the old photo file, if any, so orphaned uploads don't pile up.
            if (!empty($lunora_user['profile_image'])) {
                $old = __DIR__ . '/' . ltrim($lunora_user['profile_image'], '/');
                if (is_file($old)) {
                    @unlink($old);
                }
            }

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                lunora_update_profile_image($lunora_user['id'], 'uploads/avatars/' . $filename);
                lunora_flash_set('success', 'Your profile photo has been updated.');
                header('Location: profile.php');
                exit;
            } else {
                $errors[] = 'Could not save the uploaded file — check folder permissions.';
            }
        }
    }
}

// Re-fetch in case anything above changed it (e.g. errors happened after a successful earlier step).
$lunora_user = lunora_current_user();
$csrfToken = lunora_csrf_token();
$avatarSrc = !empty($lunora_user['profile_image']) ? htmlspecialchars($lunora_user['profile_image']) : '';
$initial = strtoupper(substr($lunora_user['full_name'] ?: 'U', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>
<?php include __DIR__ . '/includes/site_header.php'; ?>

<?php if ($lunora_flash): ?>
<div class="flash-banner flash-banner--<?= htmlspecialchars($lunora_flash['type']) ?>"><?= htmlspecialchars($lunora_flash['message']) ?></div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="flash-banner flash-banner--error"><?= htmlspecialchars(implode(' ', $errors)) ?></div>
<?php endif; ?>

<div class="breadcrumb">
<div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>My Profile</span></div>
</div>

<main class="account-layout">
<div class="info-page__inner" style="max-width:640px;">
<h1>My Profile</h1>

<div class="profile-photo-block">
    <div class="profile-photo-block__avatar">
        <?php if ($avatarSrc): ?>
            <img src="<?= $avatarSrc ?>" alt="Your profile photo">
        <?php else: ?>
            <span><?= htmlspecialchars($initial) ?></span>
        <?php endif; ?>
    </div>
    <form method="post" enctype="multipart/form-data" class="profile-photo-block__form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="action" value="update_photo">
        <label class="profile-photo-block__label">
            <input type="file" name="photo" accept="image/png,image/jpeg,image/gif,image/webp" required>
            <span>Change photo</span>
        </label>
        <button type="submit" class="auth-submit" style="width:auto; padding:8px 18px; font-size:0.8rem;">Upload</button>
    </form>
</div>

<form method="post" class="profile-form">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
    <input type="hidden" name="action" value="update_profile">

    <label class="profile-form__field">
        <span>Full name</span>
        <input type="text" name="full_name" value="<?= htmlspecialchars($lunora_user['full_name']) ?>" required>
    </label>

    <label class="profile-form__field">
        <span>Email</span>
        <input type="email" name="email" value="<?= htmlspecialchars($lunora_user['email']) ?>" required>
    </label>

    <label class="profile-form__field">
        <span>Phone</span>
        <input type="tel" name="phone" value="<?= htmlspecialchars($lunora_user['phone'] ?? '') ?>" placeholder="e.g. +63 991 234 5678">
    </label>

    <button type="submit" class="auth-submit" style="width:auto; padding:10px 24px;">Save Changes</button>
</form>

</div>
<?php include __DIR__ . '/includes/best_seller_sidebar.php'; ?>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>
<script src="script.js"></script>
</body>
</html>
