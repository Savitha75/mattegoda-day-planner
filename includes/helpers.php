<?php
declare(strict_types=1);

/** Escape any text before printing it into HTML (prevents XSS). */
function h(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Build a link that works wherever the project folder lives. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Send the browser to another page and stop this script. */
function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

/** Store a one-time message to show after a redirect. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Read the stored messages and clear them so they appear only once. */
function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** Append a line to logs/app.log (that folder is blocked from the web). */
function log_error(string $message): void
{
    $line = sprintf('[%s] %s%s', date('Y-m-d H:i:s'), $message, PHP_EOL);
    error_log($line, 3, __DIR__ . '/../logs/app.log');
}