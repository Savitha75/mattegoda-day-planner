<?php
declare(strict_types=1);
// Creates an administrator, or resets the password of an existing one (FR-38).
// Run it from the project folder in PowerShell:
//   C:\xampp\php\php.exe tools\create_admin.php admin
if (PHP_SAPI !== 'cli') {               // never from a browser
    http_response_code(404);
    exit;
}
require __DIR__ . '/../config/config.php';
require __DIR__ . '/../includes/helpers.php';
require __DIR__ . '/../src/Database.php';

$username = trim($argv[1] ?? '');
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
    fwrite(STDERR, "Usage: php tools/create_admin.php <username>   (3-50 letters, digits, _ . -)\n");
    exit(1);
}

echo 'New password for ' . $username . ' (at least 12 characters): ';
$password = rtrim((string) fgets(STDIN), "\r\n");     // typed here, so it never appears in the command history
if (strlen($password) < 12) {
    fwrite(STDERR, "The password must be at least 12 characters.\n");
    exit(1);
}

// FR-38, NFR-09: bcrypt hash with its own random salt; the password itself is never stored
$hash = password_hash($password, PASSWORD_DEFAULT);
$stmt = Database::getConnection()->prepare(
    'INSERT INTO admin_user (username, password_hash) VALUES (?, ?)
     ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), failed_attempts = 0, locked_until = NULL'
);
$stmt->execute([$username, $hash]);

echo $stmt->rowCount() === 1
    ? "Administrator '$username' created.\n"
    : "Password for '$username' reset.\n";
