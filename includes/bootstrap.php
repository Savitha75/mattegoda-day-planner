<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/helpers.php';

// Autoload: "Place" → src/Place.php, loaded only when first used
spl_autoload_register(function (string $class): void {
    $file = __DIR__ . '/../src/' . $class . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});

// Errors: show details while developing, hide them from real users
error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');

set_exception_handler(function (Throwable $e): void {
    log_error(get_class($e) . ': ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo APP_DEBUG
        ? '<pre>' . h((string) $e) . '</pre>'
        : '<p>Sorry, something went wrong. Please try again later.</p>';
});

// Session (needed for flash messages now, admin login later)
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly'  => true,   // JavaScript can't read the session cookie
        'cookie_samesite'  => 'Lax',  // cookie not sent on cross-site POSTs
        'use_strict_mode'  => true,   // reject made-up session IDs
    ]);
}