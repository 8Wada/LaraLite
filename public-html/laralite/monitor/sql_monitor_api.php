<?php
/**
 * SQL Monitor API
 * 
 * API para el monitor de SQL queries
 * Proporciona datos en formato JSON para el frontend
 */

namespace App;

use App\Database\EloquentBootstrap;
use App\Utils\Autoloader;

// Load configuración
require_once '../App/Config/config.php';
require_once '../App/Core/autoload.php';
require_once '../App/Utils/Autoloader.php';

Autoloader::register();

// Inicializar Eloquent
global $DB;
$DB = EloquentBootstrap::init();

// Headers para JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Archivo para almacenar queries persistentes
$queriesFile = '../logs/sql_queries.json';

// Crear directorio de logs si no existe
if (!is_dir('../logs')) {
    mkdir('../logs', 0777, true);
}

// Manejar acción de limpiar
if (isset($_GET['action']) && $_GET['action'] === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    file_put_contents($queriesFile, json_encode([]));
    echo json_encode(['success' => true, 'message' => 'Queries limpiadas']);
    exit;
}

// Cargar queries existentes del archivo
$allQueries = [];
if (file_exists($queriesFile)) {
    $content = file_get_contents($queriesFile);
    $allQueries = json_decode($content, true) ?: [];
}

// Calcular estadísticas
$stats = [
    'total' => count($allQueries),
    'totalTime' => 0,
    'avgTime' => 0,
    'slowest' => 0
];

if (count($allQueries) > 0) {
    $times = array_column($allQueries, 'time');
    $stats['totalTime'] = array_sum($times);
    $stats['avgTime'] = $stats['totalTime'] / count($allQueries);
    $stats['slowest'] = max($times);
}

// Invertir orden para mostrar las más recientes primero
$allQueries = array_reverse($allQueries);

// Respuesta
echo json_encode([
    'success' => true,
    'timestamp' => time(),
    'stats' => $stats,
    'queries' => $allQueries
], JSON_PRETTY_PRINT);
