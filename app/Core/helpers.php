<?php
declare(strict_types=1);

use App\Core\App;
use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;

/** Escape for HTML output. Every dynamic value in a view goes through this. */
function e(mixed $value): string
{
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Escape for use inside a JS string / JSON blob. */
function ejson(mixed $value): string
{
    return htmlspecialchars(
        (string)json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
        ENT_QUOTES, 'UTF-8'
    );
}

function url(string $path = ''): string
{
    return App::url($path);
}

function asset(string $path): string
{
    return App::url('assets/' . ltrim($path, '/'));
}

function csrf(): string
{
    return Csrf::field();
}

function old(array $bag, string $key, mixed $default = ''): string
{
    return e($bag[$key] ?? $default);
}

function errorFor(array $errors, string $key): string
{
    if (empty($errors[$key])) {
        return '';
    }
    return '<div class="invalid-feedback d-block">' . e($errors[$key][0]) . '</div>';
}

function invalid(array $errors, string $key): string
{
    return empty($errors[$key]) ? '' : ' is-invalid';
}

function can(string $permission): bool
{
    return Auth::can($permission);
}

function activeIf(string $prefix): string
{
    return str_starts_with(Request::path(), '/' . trim($prefix, '/')) ? ' active' : '';
}

/** Human-readable status label. */
function statusLabel(string $status): string
{
    return ucwords(str_replace('_', ' ', $status));
}

/** Bootstrap contextual colour for a case/report status. */
function statusColor(string $status): string
{
    return match ($status) {
        'submitted'            => 'secondary',
        'under_review'         => 'info',
        'assigned'             => 'primary',
        'investigating'        => 'primary',
        'awaiting_information' => 'warning',
        'escalated'            => 'danger',
        'resolved'             => 'success',
        'closed'               => 'dark',
        'rejected'             => 'danger',
        default                => 'secondary',
    };
}

function urgencyColor(string $urgency): string
{
    return match ($urgency) {
        'low'      => 'success',
        'medium'   => 'info',
        'high'     => 'warning',
        'critical' => 'danger',
        default    => 'secondary',
    };
}

function dt(?string $value, string $format = 'd M Y, H:i'): string
{
    if ($value === null || $value === '' || str_starts_with($value, '0000')) {
        return '—';
    }
    $ts = strtotime($value);
    return $ts === false ? '—' : date($format, $ts);
}

function d(?string $value): string
{
    return dt($value, 'd M Y');
}

function ago(?string $value): string
{
    if ($value === null) {
        return '—';
    }
    $ts = strtotime($value);
    if ($ts === false) {
        return '—';
    }
    $diff = time() - $ts;
    return match (true) {
        $diff < 60      => 'just now',
        $diff < 3600    => intdiv($diff, 60) . ' min ago',
        $diff < 86400   => intdiv($diff, 3600) . ' h ago',
        $diff < 2592000 => intdiv($diff, 86400) . ' d ago',
        default         => date('d M Y', $ts),
    };
}

function bytes(int $size): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    $value = (float)$size;
    while ($value >= 1024 && $i < count($units) - 1) {
        $value /= 1024;
        $i++;
    }
    return round($value, $i === 0 ? 0 : 1) . ' ' . $units[$i];
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $out = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $out .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $out === '' ? '?' : $out;
}

function slugify(string $text): string
{
    $text = preg_replace('/[^\p{L}\p{Nd}]+/u', '-', $text) ?? '';
    return trim(mb_strtolower($text), '-') ?: 'item';
}

/** Preserve current query string while changing one parameter. */
function queryWith(array $overrides): string
{
    $params = array_merge($_GET, $overrides);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    return $params === [] ? '' : '?' . http_build_query($params);
}

/**
 * Render editor-authored rich text. Only a small, safe subset of tags survives,
 * every attribute is dropped, and anything else is escaped — so stored content
 * can never introduce script into a page.
 */
function safeHtml(?string $html): string
{
    $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><h2><h3><h4><blockquote>';
    $clean   = strip_tags((string)$html, $allowed);
    // Remove every attribute from the surviving tags.
    $clean   = preg_replace('/<([a-z0-9]+)\s[^>]*>/i', '<$1>', $clean) ?? '';
    return $clean;
}

/** Truncate for table cells. */
function excerpt(?string $text, int $length = 90): string
{
    $text = trim((string)$text);
    return mb_strlen($text) <= $length ? $text : mb_substr($text, 0, $length - 1) . '…';
}
