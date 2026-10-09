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

/** FR-13: the value as safe HTML, or "Not available" when it is empty. */
function show_value(?string $value): string
{
    if ($value === null || trim($value) === '') {
        return '<span class="text-muted fst-italic">Not available</span>';
    }
    return h($value);
}

/** "06:30:00" -> "6:30 am" */
function format_time(?string $time): ?string
{
    if ($time === null) {
        return null;
    }
    $t = DateTime::createFromFormat('H:i:s', $time);
    return $t ? $t->format('g:i a') : null;
}

/** 90 -> "1 h 30 min" */
function format_duration(?int $minutes): ?string
{
    if ($minutes === null || $minutes <= 0) {
        return null;
    }
    $h = intdiv($minutes, 60);
    $m = $minutes % 60;
    return trim(($h > 0 ? "$h h " : '') . ($m > 0 ? "$m min" : ''));
}

/**
 * FR-06, FR-22: valid category IDs from ?cat[]=… (anything else is ignored).
 * @param array<int, Category> $all  result of Category::findAll()
 * @return int[]
 */
function selected_categories(array $all): array
{
    $selected = [];
    foreach ((array) ($_GET['cat'] ?? []) as $value) {
        if (is_string($value) && ctype_digit($value) && isset($all[(int) $value])) {
            $selected[] = (int) $value;
        }
    }
    return array_values(array_unique($selected));
}

/** FR-09: ?q= trimmed and limited to 100 characters. */
function search_keyword(): string
{
    $q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
    return mb_substr($q, 0, 100);
}

/** Query string for the current filters, e.g. "cat%5B0%5D=2&q=park" (empty when none). */
function filter_query(array $categories, string $q): string
{
    return http_build_query(array_filter(['cat' => $categories, 'q' => $q], fn($v) => $v !== '' && $v !== []));
}

/** Send a JSON answer with an HTTP status code and stop (used by the api/ files). */
function json_response(int $status, array $data): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}