<?php

error_reporting(E_ALL);
ini_set('display_errors', '1');

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

// Check if app key is missing
$envFile = __DIR__ . '/../.env';
$appKeyMissing = true;

if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    if (preg_match('/^APP_KEY=base64:.+/m', $envContent)) {
        $appKeyMissing = false;
    }
}

if ($appKeyMissing) {
    // Redirect to setup page
    header('Location: setup.php');
    exit;
}

// Continue with normal Laravel bootstrap
define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
