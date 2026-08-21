<?php
/**
 * HRRAMS front controller — the single entry point for every request.
 */
declare(strict_types=1);

/*
 * Where is the rest of the application?
 *
 * Normally it sits one level above this file and nothing needs configuring.
 *
 * Some shared hosts — Hostinger among them — do not let you move the document
 * root off public_html. There, keep the application outside the web root and
 * copy only the CONTENTS of public/ into public_html, then point this file at
 * it by either of these means:
 *
 *   1. hrrams-path.php next to this file, returning the path:
 *          <?php return '/home/u123456789/hrrams';
 *      Works everywhere, needs no Apache modules. Preferred.
 *
 *   2. An environment variable, if the host allows mod_env:
 *          SetEnv HRRAMS_APP_ROOT /home/u123456789/hrrams
 *
 * See docs/HOSTING-HOSTINGER.md or docs/HOSTING-CPANEL.md.
 */
$appRoot   = null;
$attempted = [];

$isAppDir = static fn($p): bool => is_string($p) && $p !== '' && is_file(rtrim($p, '/') . '/app/Core/App.php');

// 1. Pointer file — host-independent.
$pointer = __DIR__ . '/hrrams-path.php';
if (is_file($pointer)) {
    $candidate = require $pointer;
    $attempted[] = 'hrrams-path.php';
    if ($isAppDir($candidate)) {
        $appRoot = rtrim((string)$candidate, '/');
    }
}

// 2. Environment variable, from SetEnv or the FPM pool config.
if ($appRoot === null) {
    $candidate = getenv('HRRAMS_APP_ROOT') ?: ($_SERVER['HRRAMS_APP_ROOT'] ?? '');
    if ($candidate !== '') {
        $attempted[] = 'HRRAMS_APP_ROOT';
        if ($isAppDir($candidate)) {
            $appRoot = rtrim((string)$candidate, '/');
        }
    }
}

// 3. The normal layout: application one level up from the document root.
if ($appRoot === null) {
    $candidate = dirname(__DIR__);
    $attempted[] = 'parent directory';
    if ($isAppDir($candidate)) {
        $appRoot = $candidate;
    }
}

if ($appRoot === null) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "HRRAMS is not installed correctly.\n\n";
    echo "The application directory could not be found. Checked: "
       . ($attempted ? implode(', ', $attempted) : 'nothing') . ".\n\n";
    echo "Create hrrams-path.php next to index.php containing:\n\n";
    echo "    <?php return '/home/YOURUSER/hrrams';\n\n";
    echo "It must be the folder that contains app/, config/ and storage/.\n";
    echo "See docs/HOSTING-HOSTINGER.md.\n";
    exit;
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
