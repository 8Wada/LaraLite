<?php

namespace App;

use App\Database\EloquentBootstrap;
use App\Router\Router;
use App\Utils\Autoloader;
use App\Utils\ResponseHelper;

// Load Composer autoloader
require_once 'App/Config/config.php';
require_once 'App/Core/autoload.php';
require_once 'App/Utils/Autoloader.php';

// Register custom autoloader
Autoloader::register();

// Initialize Eloquent database connection
global $DB, $r;
$DB = EloquentBootstrap::init();

// Initialize router with database instance
$r = new Router($DB);

// Load route definitions from directories
Autoloader::loadDirectory('App/Routes');

$r->default(function () {
    ResponseHelper::responseRequest(false, 'Route not found', 404);
});