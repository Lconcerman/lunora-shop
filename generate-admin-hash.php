<?php
/**
 * TEMPORARY — generates a bcrypt hash for your new admin password.
 * 1. Put this file in your project root.
 * 2. Change the password below to whatever you want your real admin
 *    password to be.
 * 3. Open it in your browser: http://localhost/lunora.com/generate-admin-hash.php
 * 4. Copy the hash it prints.
 * 5. DELETE THIS FILE — it's not meant to stay in your project.
 */

$newPassword = 'PickABrandNewPasswordHere';

echo htmlspecialchars(password_hash($newPassword, PASSWORD_DEFAULT));
