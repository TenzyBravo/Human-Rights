<?php
/**
 * HRRAMS front controller — the single entry point for every request.
 */
declare(strict_types=1);

require dirname(__DIR__) . '/app/Core/App.php';

use App\Core\App;
use App\Core\Audit;

App::boot();

require dirname(__DIR__) . '/app/Core/helpers.php';

if (App::config('app.force_https') && !App::isSecure()) {
    header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
    exit;
}

$router = require dirname(__DIR__) . '/config/routes.php';

Audit::activity('Request');
$router->dispatch();
