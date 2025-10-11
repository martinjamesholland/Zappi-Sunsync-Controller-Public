<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Enable error reporting
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

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

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
