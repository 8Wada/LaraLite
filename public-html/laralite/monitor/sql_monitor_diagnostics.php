<?php
/**
 * Diagnóstico del SQL Monitor
 * 
 * Este archivo verifica el estado del sistema de monitoreo
 */

namespace App;

use App\Database\EloquentBootstrap;
use App\Utils\Autoloader;

require_once '../config/config.php';
require_once '../App/Core/autoload.php';
require_once '../App/Utils/Autoloader.php';

Autoloader::register();

// Inicializar Eloquent
global $DB;
$DB = EloquentBootstrap::init();

header('Content-Type: application/json');

$diagnostics = [];

// 1. Verificar directorio logs
$logsDir = '../logs';
$diagnostics['logs_directory'] = [
    'path' => $logsDir,
    'exists' => is_dir($logsDir),
    'writable' => is_writable($logsDir),
    'permissions' => is_dir($logsDir) ? substr(sprintf('%o', fileperms($logsDir)), -4) : 'N/A'
];

// 2. Verificar archivo de queries
$queriesFile = $logsDir . '/sql_queries.json';
$diagnostics['queries_file'] = [
    'path' => $queriesFile,
    'exists' => file_exists($queriesFile),
    'writable' => file_exists($queriesFile) ? is_writable($queriesFile) : is_writable($logsDir),
    'size' => file_exists($queriesFile) ? filesize($queriesFile) : 0,
    'permissions' => file_exists($queriesFile) ? substr(sprintf('%o', fileperms($queriesFile)), -4) : 'N/A'
];

// 3. Leer contenido actual
if (file_exists($queriesFile)) {
    $content = file_get_contents($queriesFile);
    $queries = json_decode($content, true);
    $diagnostics['current_queries'] = [
        'count' => is_array($queries) ? count($queries) : 0,
        'json_valid' => json_last_error() === JSON_ERROR_NONE,
        'last_error' => json_last_error_msg(),
        'latest_query' => is_array($queries) && count($queries) > 0 ? $queries[0] : null
    ];
} else {
    $diagnostics['current_queries'] = [
        'count' => 0,
        'message' => 'File does not exist yet'
    ];
}

// 4. Verificar estado de Eloquent
$diagnostics['eloquent'] = [
    'initialized' => $DB !== null,
    'query_log_enabled' => $DB->getConnection()->logging(),
    'driver' => $DB->getConnection()->getDriverName(),
    'database' => $DB->getConnection()->getDatabaseName()
];

// 5. Ejecutar query de prueba
try {
    $testStart = microtime(true);
    $result = $DB->getConnection()->select('SELECT ? as test, NOW() as timestamp', ['SQL_MONITOR_TEST']);
    $testEnd = microtime(true);
    
    $diagnostics['test_query'] = [
        'executed' => true,
        'duration_ms' => round(($testEnd - $testStart) * 1000, 2),
        'result' => $result
    ];
    
    // Esperar un momento y verificar si se guardó
    sleep(1);
    
    if (file_exists($queriesFile)) {
        $content = file_get_contents($queriesFile);
        $queries = json_decode($content, true);
        
        // Buscar la query de prueba
        $testFound = false;
        if (is_array($queries)) {
            foreach ($queries as $query) {
                if (strpos($query['sql'], 'SQL_MONITOR_TEST') !== false) {
                    $testFound = true;
                    break;
                }
            }
        }
        
        $diagnostics['test_query']['logged'] = $testFound;
        $diagnostics['test_query']['total_queries_after'] = is_array($queries) ? count($queries) : 0;
    }
    
} catch (\Exception $e) {
    $diagnostics['test_query'] = [
        'executed' => false,
        'error' => $e->getMessage()
    ];
}

// 6. Verificar listener
$diagnostics['event_listeners'] = [
    'message' => 'Event listeners are configured in EloquentBootstrap::init()',
    'method' => 'Connection->listen()'
];

// 7. Información del sistema
$diagnostics['system'] = [
    'php_version' => PHP_VERSION,
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'
];

// 8. Recomendaciones
$diagnostics['recommendations'] = [];

if (!$diagnostics['logs_directory']['exists']) {
    $diagnostics['recommendations'][] = 'Create logs directory: mkdir -p ' . $logsDir;
}

if (!$diagnostics['logs_directory']['writable']) {
    $diagnostics['recommendations'][] = 'Make logs directory writable: chmod 777 ' . $logsDir;
}

if (!$diagnostics['eloquent']['query_log_enabled']) {
    $diagnostics['recommendations'][] = 'Query logging is not enabled. Check EloquentBootstrap::init()';
}

if (isset($diagnostics['test_query']['logged']) && !$diagnostics['test_query']['logged']) {
    $diagnostics['recommendations'][] = 'Test query was not logged. Check logQuery() method and error_log';
}

// Resultado final
$allGood = 
    $diagnostics['logs_directory']['exists'] &&
    $diagnostics['logs_directory']['writable'] &&
    $diagnostics['eloquent']['initialized'] &&
    $diagnostics['eloquent']['query_log_enabled'] &&
    isset($diagnostics['test_query']['logged']) &&
    $diagnostics['test_query']['logged'];

$diagnostics['status'] = [
    'all_checks_passed' => $allGood,
    'message' => $allGood 
        ? '✅ SQL Monitor está funcionando correctamente' 
        : '⚠️ Hay problemas con el SQL Monitor. Revisa las recomendaciones.'
];

echo json_encode($diagnostics, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
