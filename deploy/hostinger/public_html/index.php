<?php

/*
|--------------------------------------------------------------------------
| Hostinger front controller (web root copy)
|--------------------------------------------------------------------------
| Copy this file to the web root of the subdomain. The Laravel project lives
| OUTSIDE the web root, so the path below is absolute on purpose: a relative
| "../" would silently boot whichever copy of the project happens to sit next
| to the web root (there are several old copies on this account).
|
| Edit ONLY the constant below if the project is ever moved.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

const ICSS_PROJECT_PATH = '/home/u988863428/domains/icss-eshet-tabulation.pitonmain.com/Icss-folder';

if (! is_file(ICSS_PROJECT_PATH . '/vendor/autoload.php') || ! is_file(ICSS_PROJECT_PATH . '/bootstrap/app.php')) {
    // Log the real reason for the server admin; never print server paths to visitors.
    error_log('ICSS front controller: Laravel project not found at ' . ICSS_PROJECT_PATH);
    http_response_code(500);
    exit('The application is not installed correctly. Please contact the administrator.');
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = ICSS_PROJECT_PATH . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require ICSS_PROJECT_PATH . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once ICSS_PROJECT_PATH . '/bootstrap/app.php';

// This directory (not <project>/public) is what the web server exposes, so
// Vite's build/manifest.json and the storage link are looked up here.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
