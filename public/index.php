<?php
/**
 * HRRAMS front controller — the single entry point for every request.
 */
declare(strict_types=1);

/*
 * Normally the application lives one level above this file, and pointing the
 * document root at public/ is all that is needed.
 *
 * Some shared hosts will not let you move the document root off public_html. In
 * that case, keep the application outside the web root and copy only the
 * contents of public/ into public_html, then tell this file where the rest is:
 *
 *   SetEnv HRRAMS_APP_ROOT /home/youruser/hrrams      # in .htaccess
 *
 * See docs/HOSTING-CPANEL.md for the full procedure.
 */
$appRoot = getenv('HRRAMS_APP_ROOT') ?: ($_SERVER['HRRAMS_APP_ROOT'] ?? '');
if (!is_string($appRoot) || $appRoot === '' || !is_dir($appRoot . '/app/Core')) {
    $appRoot = dirname(__DIR__);
}
if (!is_file($appRoot . '/app/Core/App.php')) {
    http_response_code(500);
    exit('HRRAMS is not installed correctly: the application directory could not be found. '
       . 'See docs/HOSTING-CPANEL.md.');
}

require $appRoot . '/app/Core/App.php';

use App\Core\App;
use App\Core\Audit;

App::boot();

require $appRoot . '/app/Core/helpers.php';

if (App::config('app.force_https') && !App::isSecure()) {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

$router = require $appRoot . '/config/routes.php';

Audit::activity('Request');
$router->dispatch();
