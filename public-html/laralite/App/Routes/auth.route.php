<?php

global $r, $DB;

use App\Controllers\SamlController;


$r->setBasePath('auth');
$r->get('/session', [SamlController::class, 'index']);
$r->get('/login', [SamlController::class, 'login']);
$r->get('/logout', [SamlController::class, 'logout']);
