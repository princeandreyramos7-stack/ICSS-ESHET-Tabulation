<?php

/*
|--------------------------------------------------------------------------
| Hostinger front controller
|--------------------------------------------------------------------------
| Lives in public_html. The Laravel project itself is uploaded one level
| up, in a folder named "Icss-folder" (sibling of public_html), so nothing but
| this file and the static assets is web-accessible.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$projectPath = __DIR__ . '/../Icss-folder';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $projectPath . '/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $projectPath . '/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $projectPath . '/bootstrap/app.php';

// Tell Laravel that public_html is the public directory.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
