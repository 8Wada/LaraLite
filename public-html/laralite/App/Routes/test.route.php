<?php

use App\Utils\ResponseHelper;
use App\Database\EloquentBootstrap;

global $r, $DB;

// ================================================================
// ROOT & DATABASE
// ================================================================

$r->get('/', function () {
  ResponseHelper::responseRequest(false, 'API is working', 200, [
    "name"        => "LaraLite API",
    "version"     => "1.0.0",
    "description" => "Backend API powered by LaraLite Framework",
    "author"      => "LaraLite Team"
  ]);
});

$r->get('/test-db', function () {
  try {
    $connectionInfo = EloquentBootstrap::getConnectionInfo();
    ResponseHelper::responseRequest(false, "Conexión exitosa", 200, $connectionInfo);
  } catch (Exception $e) {
    ResponseHelper::responseRequest(true, "Error al conectar a la base de datos: " . $e->getMessage(), 500);
  }
});

// ================================================================
// HTTP METHODS TEST
// ================================================================

$r->get('/test-methods', function () {
  ResponseHelper::responseRequest(false, "GET funcionando correctamente. Prueba POST, PUT y DELETE en /test-methods", 200);
});

$r->post('/test-methods', function () {
  ResponseHelper::responseRequest(false, "POST recibido correctamente", 200);
});

$r->put('/test-methods', function () {
  ResponseHelper::responseRequest(false, "PUT recibido correctamente", 200);
});

$r->delete('/test-methods', function () {
  ResponseHelper::responseRequest(false, "DELETE recibido correctamente", 200);
});
