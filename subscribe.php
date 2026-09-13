<?php
/**
 * LUNORA — newsletter signup endpoint, called by the footer's
 * "Be the First to Know" form via fetch(). Always returns JSON.
 */
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Please enter a valid email address.']);
    exit;
}

try {
    $db = lunora_db();
    // Self-healing: create the table on first use if the schema hasn't been (re-)imported yet.
    $db->exec('CREATE TABLE IF NOT EXISTS newsletter_subscribers (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        email      VARCHAR(190) NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_newsletter_email (email)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

    $stmt = $db->prepare('SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        echo json_encode(['ok' => true, 'message' => "You're already subscribed — thank you!"]);
        exit;
    }

    $insert = $db->prepare('INSERT INTO newsletter_subscribers (email, created_at) VALUES (?, NOW())');
    $insert->execute([$email]);

    echo json_encode(['ok' => true, 'message' => "Thanks — check your inbox for your 10% off code!"]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Something went wrong. Please try again shortly.']);
}
