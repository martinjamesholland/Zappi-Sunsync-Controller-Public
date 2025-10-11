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

<<<<<<< Updated upstream
// Bootstrap Laravel and handle the request...
/** @var Application $app */
=======
/*
|--------------------------------------------------------------------------
| Check For Environment File
|--------------------------------------------------------------------------
|
| Before bootstrapping the application, check if the .env file exists.
| If it doesn't, redirect to the setup wizard to guide the user through
| the initial configuration process.
|
*/

if (!file_exists(__DIR__.'/../.env')) {
    // Redirect to setup wizard
    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Determine base path
    $basePath = str_replace('/index.php', '', $scriptName);
    $basePath = rtrim($basePath, '/');
    
    // Check if we're not already on a setup page
    if (!str_contains($requestUri, '/setup')) {
        header('Location: ' . $basePath . '/setup');
        exit;
    }
    
    // For setup pages, create a minimal .env file so Laravel can boot
    // Generate a temporary APP_KEY
    $tempKey = 'base64:' . base64_encode(random_bytes(32));
    
    $minimalEnv = "APP_NAME=Laravel\n";
    $minimalEnv .= "APP_ENV=local\n";
    $minimalEnv .= "APP_KEY=" . $tempKey . "\n";
    $minimalEnv .= "APP_DEBUG=true\n";
    $minimalEnv .= "APP_TIMEZONE=UTC\n";
    $minimalEnv .= "APP_URL=http://localhost\n\n";
    $minimalEnv .= "LOG_CHANNEL=stack\n";
    $minimalEnv .= "LOG_LEVEL=debug\n\n";
    $minimalEnv .= "SESSION_DRIVER=file\n";
    $minimalEnv .= "SESSION_LIFETIME=120\n";
    $minimalEnv .= "CACHE_STORE=file\n";
    
    file_put_contents(__DIR__.'/../.env', $minimalEnv);
}

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

>>>>>>> Stashed changes
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
