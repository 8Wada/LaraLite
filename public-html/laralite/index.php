<?php

namespace App;

use App\Database\EloquentBootstrap;
use App\Router\Router;
use App\Utils\Autoloader;
use App\Utils\ResponseHelper;
use App\Middlewares\SamlMiddleware;

// Load configuration and autoloaders
require_once 'App/Config/config.php';
require_once 'App/Core/autoload.php';
require_once 'App/Utils/Autoloader.php';

Autoloader::register();

// Initialize dependencies
global $DB, $r;
$DB = EloquentBootstrap::init();
$r = new Router($DB);

// Initialize middlewares
$samlMDW = new SamlMiddleware();

// Load application routes
Autoloader::loadDirectory('App/Routes');

// Public routes
$r->get('/public-info', function () {
    ResponseHelper::responseRequest(false, 'This is public information', 200);
});

// Protected routes
$r->get('/protected-info', function () {
    ResponseHelper::responseRequest(false, 'This is protected information', 200);
}, [$samlMDW->handle()]);

// Default 404 handler
$r->default(function () {
    ResponseHelper::responseRequest(false, 'Route not found', 404);
});