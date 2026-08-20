<?php
/**
 * Production readiness check.
 *
 *   php tools/preflight.php                     # run every check
 *   php tools/preflight.php --mail you@you.com  # also send a real test email
 *
 * Exit code 0 = ready, 1 = at least one FAIL. WARNings do not fail the run.
 * Safe to run against a live installation: it reads state and, with --mail,
 * sends one message. It changes nothing.
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("preflight.php is a command-line tool.\n");
}

require dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;
use App\Core\Database;
use App\Core\Mailer;

App::boot();
require dirname(__DIR__) . '/app/Core/helpers.php';

$argvOpts = getopt('', ['mail::']) ?: [];
$mailTo   = $argvOpts['mail'] ?? null;

$results = [];
function check(string $area, string $name, string $state, string $detail = ''): void
{
    global $results;
    $results[] = compact('area', 'name', 'state', 'detail');
}
const PASS = 'PASS', FAIL = 'FAIL', WARN = 'WARN', INFO = 'INFO';

// ------------------------------------------------------------------ environment
check('env', 'PHP version', version_compare(PHP_VERSION, '8.1', '>=') ? PASS : FAIL, PHP_VERSION);

foreach (['pdo_mysql', 'mbstring', 'fileinfo', 'openssl', 'json'] as $ext) {
    check('env', 'extension ' . $ext, extension_loaded($ext) ? PASS : FAIL);
}

$uploadMax = (int)str_replace(['M', 'm'], '', (string)ini_get('upload_max_filesize'));
check('env', 'upload_max_filesize', $uploadMax >= 20 ? PASS : WARN,
      ini_get('upload_max_filesize') . ' (evidence uploads are capped at 20M)');
$postMax = (int)str_replace(['M', 'm'], '', (string)ini_get('post_max_size'));
check('env', 'post_max_size', $postMax >= $uploadMax ? PASS : WARN, (string)ini_get('post_max_size'));

// --------------------------------------------------------------- configuration
$debug = (bool)App::config('app.debug');
check('config', 'app.debug is off', $debug ? FAIL : PASS,
      $debug ? 'debug is ON — stack traces would be shown to visitors' : '');

$env = (string)App::config('app.env');
check('config', 'app.env is production', $env === 'production' ? PASS : WARN, $env);

$key = (string)App::config('security.app_key');
$keyIsDefault = str_contains($key, 'CHANGE_ME') || str_contains($key, '00000000');
check('config', 'security.app_key changed', $keyIsDefault ? FAIL : PASS,
      $keyIsDefault ? 'still the shipped placeholder — run: openssl rand -base64 32' : 'set');

check('config', 'config.local.php present',
      is_file(dirname(__DIR__) . '/config/config.local.php') ? PASS : WARN,
      'machine-specific settings belong there, not in config.php');

$https = (bool)App::config('app.force_https');
check('config', 'app.force_https', $https ? PASS : WARN, $https ? '' : 'enable once TLS is installed');
$cookie = (bool)App::config('security.cookie_secure');
check('config', 'security.cookie_secure', $cookie ? PASS : WARN, $cookie ? '' : 'enable under HTTPS');

// ------------------------------------------------------------------- database
try {
    $db = Database::instance();
    $ver = (string)$db->scalar('SELECT VERSION()');
    check('database', 'connection', PASS, $ver);

    $tables = (int)$db->scalar(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = "BASE TABLE"');
    check('database', 'schema loaded', $tables >= 23 ? PASS : FAIL, $tables . ' tables');

    $dbUser = (string)$db->scalar('SELECT CURRENT_USER()');
    check('database', 'not connecting as root', str_starts_with($dbUser, 'root@') ? WARN : PASS, $dbUser);

    // Demo accounts are the single most common thing left behind.
    $demo = $db->all("SELECT email FROM users WHERE email LIKE '%@hrrams.test' AND deleted_at IS NULL");
    check('security', 'demo accounts removed', $demo === [] ? PASS : FAIL,
          $demo === [] ? '' : count($demo) . ' still active: ' . implode(', ', array_column($demo, 'email')));

    $admins = (int)$db->scalar(
        'SELECT COUNT(*) FROM users u JOIN user_roles ur ON ur.user_id = u.id
           JOIN roles r ON r.id = ur.role_id
          WHERE r.slug = "admin" AND u.status = "active" AND u.deleted_at IS NULL');
    check('security', 'an active administrator exists', $admins > 0 ? PASS : FAIL, $admins . ' found');

    $weak = (int)$db->scalar('SELECT COUNT(*) FROM users WHERE password_hash NOT LIKE "$2y$%" AND deleted_at IS NULL');
    check('security', 'all passwords bcrypt-hashed', $weak === 0 ? PASS : FAIL, $weak . ' non-bcrypt');
} catch (\Throwable $e) {
    check('database', 'connection', FAIL, $e->getMessage());
}

// -------------------------------------------------------------------- storage
foreach (['uploads', 'logs', 'backups'] as $dir) {
    $path = App::config('app.storage') . '/' . $dir;
    $ok = is_dir($path) && is_writable($path);
    check('storage', 'storage/' . $dir . ' writable', $ok ? PASS : FAIL, $ok ? '' : $path);
}

// storage/ and config/ must not be reachable over HTTP. Presence of the deny
// rules is what we can check offline; the guide covers verifying it live.
foreach (['storage', 'config', 'app', 'database'] as $dir) {
    $ht = dirname(__DIR__) . '/' . $dir . '/.htaccess';
    check('security', $dir . '/.htaccess deny present', is_file($ht) ? PASS : WARN, $ht);
}

// ----------------------------------------------------------------------- mail
$driver = (string)App::config('mail.driver');
check('mail', 'driver', $driver === 'smtp' ? PASS : ($driver === 'log' ? FAIL : WARN),
      $driver === 'log'
        ? 'driver is "log" — verification links and password resets will NOT be delivered'
        : $driver);

if ($driver === 'smtp') {
    $smtp = (array)App::config('mail.smtp');
    check('mail', 'smtp.host set', !empty($smtp['host']) && $smtp['host'] !== 'localhost' ? PASS : WARN,
          (string)($smtp['host'] ?? ''));
    check('mail', 'smtp credentials set', !empty($smtp['user']) ? PASS : WARN,
          empty($smtp['user']) ? 'no username — fine only for an open local relay' : 'user set');
}

$fromEmail = (string)App::config('mail.from_email');
check('mail', 'from address is not the placeholder',
      str_contains($fromEmail, 'hrrams.test') ? FAIL : PASS, $fromEmail);

if ($mailTo !== null && $mailTo !== '') {
    $sent = Mailer::send($mailTo, 'HRRAMS preflight test',
        '<p>This is a test message from the HRRAMS preflight check.</p>'
        . '<p>If you are reading it, verification links and password resets will reach your users.</p>');
    check('mail', 'test message to ' . $mailTo, $sent ? PASS : FAIL,
          $sent ? ($driver === 'log' ? 'written to storage/logs/mail.log only' : 'accepted by the server')
                : (Mailer::lastError() ?? 'unknown failure'));
}

// ---------------------------------------------------------------------- report
$width = 0;
foreach ($results as $r) { $width = max($width, strlen($r['name'])); }

$colour = fn(string $s): string => match ($s) {
    PASS => "\033[32mPASS\033[0m",
    FAIL => "\033[31mFAIL\033[0m",
    WARN => "\033[33mWARN\033[0m",
    default => "\033[36mINFO\033[0m",
};
$tty = function_exists('posix_isatty') && @posix_isatty(STDOUT);

echo PHP_EOL, 'HRRAMS preflight — ', date('Y-m-d H:i:s'), PHP_EOL;
echo str_repeat('=', $width + 30), PHP_EOL;

$area = null;
$fails = $warns = 0;
foreach ($results as $r) {
    if ($r['area'] !== $area) {
        $area = $r['area'];
        echo PHP_EOL, strtoupper($area), PHP_EOL;
    }
    if ($r['state'] === FAIL) { $fails++; }
    if ($r['state'] === WARN) { $warns++; }
    printf("  [%s] %-{$width}s %s\n",
        $tty ? $colour($r['state']) : $r['state'], $r['name'], $r['detail']);
}

echo PHP_EOL, str_repeat('=', $width + 30), PHP_EOL;
printf("%d passed, %d warning(s), %d failure(s)\n",
    count($results) - $fails - $warns, $warns, $fails);
echo $fails === 0
    ? "READY — no blocking failures." . PHP_EOL
    : "NOT READY — resolve the FAIL items above before going live." . PHP_EOL;

exit($fails === 0 ? 0 : 1);
