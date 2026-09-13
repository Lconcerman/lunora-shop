<?php
/**
 * LUNORA — central database connection (PDO / MySQL).
 *
 * Edit the four constants below (or set them as environment variables
 * of the same name on your server) to match your MySQL setup, then
 * import schema.sql once, before first use:
 *
 *   mysql -u root -p -e "CREATE DATABASE lunora"
 *   mysql -u root -p lunora < schema.sql
 *
 * Every other file talks to the database through lunora_db() and
 * prepared statements only — there is no string-built SQL anywhere
 * in this project.
 */

define('LUNORA_DB_HOST', getenv('LUNORA_DB_HOST') ?: '127.0.0.1');
define('LUNORA_DB_NAME', getenv('LUNORA_DB_NAME') ?: 'lunora');
define('LUNORA_DB_USER', getenv('LUNORA_DB_USER') ?: 'root');
define('LUNORA_DB_PASS', getenv('LUNORA_DB_PASS') ?: '');

/** Returns a single shared PDO connection for the request (created on first use). */
function lunora_db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . LUNORA_DB_HOST . ';dbname=' . LUNORA_DB_NAME . ';charset=utf8mb4';

    try {
        $pdo = new PDO($dsn, LUNORA_DB_USER, LUNORA_DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // throw on every DB error instead of failing silently
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,                  // use real prepared statements, not PHP-emulated ones
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die('Database connection failed. Check the LUNORA_DB_* settings in db.php. (' . $e->getMessage() . ')');
    }

    return $pdo;
}
