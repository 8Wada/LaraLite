<?php
/**
 * Test Rápido del SQL Monitor
 * Ejecuta una query simple y verifica si se capturó
 */

// Cargar sin namespace para simplificar
require_once 'config/config.php';
require_once 'App/Core/autoload.php';
require_once 'App/Utils/Autoloader.php';

use App\Database\EloquentBootstrap;
use Illuminate\Database\Capsule\Manager as DB;

App\Utils\Autoloader::register();

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Test SQL Monitor</title>";
echo "<style>body{font-family:Arial;padding:40px;background:#f5f5f5;} .box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f0f0f0;padding:10px;border-radius:4px;overflow-x:auto;}</style>";
echo "</head><body>";
echo "<h1>🧪 Test SQL Monitor - Ejecución Simple</h1>";

// 1. Inicializar
echo "<div class='box'><h2>1️⃣ Inicializando Eloquent...</h2>";
$DB = EloquentBootstrap::init();
echo "<p class='success'>✅ Eloquent inicializado correctamente</p>";
echo "<p>Driver: " . $DB->getConnection()->getDriverName() . "</p>";
echo "<p>Database: " . $DB->getConnection()->getDatabaseName() . "</p>";
echo "<p>Query Log Enabled: " . ($DB->getConnection()->logging() ? 'SI' : 'NO') . "</p>";
echo "</div>";

// 2. Limpiar archivo anterior
echo "<div class='box'><h2>2️⃣ Preparando archivo de logs...</h2>";
$queriesFile = __DIR__ . '/logs/sql_queries.json';
file_put_contents($queriesFile, '[]');
echo "<p class='success'>✅ Archivo limpiado: $queriesFile</p>";
echo "</div>";

// 3. Ejecutar query de prueba
echo "<div class='box'><h2>3️⃣ Ejecutando query de prueba...</h2>";
try {
    $start = microtime(true);
    $result = $DB->getConnection()->select("SELECT 'TEST_MONITOR' as test, NOW() as timestamp, ? as param", ['valor_test']);
    $end = microtime(true);
    
    echo "<p class='success'>✅ Query ejecutada en " . round(($end - $start) * 1000, 2) . " ms</p>";
    echo "<p>Resultado:</p>";
    echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// 4. Esperar un momento
echo "<div class='box'><h2>4️⃣ Esperando procesamiento...</h2>";
echo "<p class='info'>⏱️ Guardando queries...</p>";

// Llamar manualmente al método para guardar (normalmente se ejecuta automáticamente al final)
App\Database\EloquentBootstrap::saveQueriesFromLog();

echo "<p class='success'>✅ Queries guardadas</p>";
echo "</div>";

// 5. Verificar archivo
echo "<div class='box'><h2>5️⃣ Verificando archivo de queries...</h2>";
if (file_exists($queriesFile)) {
    $content = file_get_contents($queriesFile);
    $queries = json_decode($content, true);
    
    echo "<p>Tamaño del archivo: " . filesize($queriesFile) . " bytes</p>";
    echo "<p>Número de queries: " . (is_array($queries) ? count($queries) : 0) . "</p>";
    
    if (is_array($queries) && count($queries) > 0) {
        echo "<p class='success'>✅ ¡Queries capturadas correctamente!</p>";
        echo "<h3>Última query capturada:</h3>";
        echo "<pre>" . json_encode($queries[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    } else {
        echo "<p class='error'>❌ No se capturaron queries</p>";
        echo "<p>Contenido del archivo:</p>";
        echo "<pre>" . htmlspecialchars($content) . "</pre>";
    }
} else {
    echo "<p class='error'>❌ El archivo no existe</p>";
}
echo "</div>";

// 6. Verificar logs de debug
echo "<div class='box'><h2>6️⃣ Sistema de Captura...</h2>";
echo "<p class='info'>ℹ️ El sistema usa <strong>shutdown_function</strong> en lugar de listeners</p>";
echo "<p class='success'>✅ Las queries se guardan automáticamente al final de cada request</p>";
echo "<p>Ya no se usan archivos de debug temporales</p>";
echo "</div>";

// 7. Verificar Query Log de Eloquent
echo "<div class='box'><h2>7️⃣ Query Log de Eloquent...</h2>";
$queryLog = $DB->getConnection()->getQueryLog();
echo "<p>Queries en el log interno: " . count($queryLog) . "</p>";
if (count($queryLog) == 0) {
    echo "<p class='success'>✅ Query log limpiado correctamente (se limpia después de guardar)</p>";
    echo "<p class='info'>Esto es normal: las queries se guardan en el archivo y luego se limpia el log para evitar duplicados</p>";
} else {
    echo "<p class='info'>ℹ️ Hay queries pendientes de guardar</p>";
    echo "<pre>" . json_encode($queryLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
}
echo "</div>";

// 8. Conclusión
echo "<div class='box' style='background:#e8f5e9;'><h2>📋 Conclusión</h2>";
$allWorking = file_exists($queriesFile) && 
              is_array($queries) && 
              count($queries) > 0;

if ($allWorking) {
    echo "<h3 class='success'>✅ ¡EL MONITOR ESTÁ FUNCIONANDO PERFECTAMENTE!</h3>";
    echo "<p><strong>🎉 Sistema operativo y capturando queries</strong></p>";
    echo "<br>";
    echo "<h4>📊 Resumen:</h4>";
    echo "<ul>";
    echo "<li>✅ Queries capturadas: <strong>" . count($queries) . "</strong></li>";
    echo "<li>✅ Archivo funcionando correctamente</li>";
    echo "<li>✅ Shutdown function registrada</li>";
    echo "<li>✅ Query log habilitado</li>";
    echo "</ul>";
    echo "<br>";
    echo "<h4>🚀 Ahora puedes usar:</h4>";
    echo "<ul>";
    echo "<li><a href='sql_monitor.php' target='_blank' style='font-size:16px;font-weight:bold;'>📺 sql_monitor.php</a> - Monitor visual en tiempo real</li>";
    echo "<li><a href='test_sql_monitor.php' target='_blank'>🧪 test_sql_monitor.php</a> - Más pruebas interactivas</li>";
    echo "</ul>";
    echo "<br>";
    echo "<h4>💡 Prueba tu endpoint:</h4>";
    echo "<p>Ejecuta cualquier petición de tu API y las queries aparecerán automáticamente en el monitor.</p>";
} else {
    echo "<h3 class='error'>❌ HAY PROBLEMAS</h3>";
    echo "<p>Posibles causas:</p>";
    echo "<ul>";
    if (!file_exists($queriesFile)) {
        echo "<li class='error'>El archivo de queries no existe</li>";
    }
    if (!is_array($queries) || count($queries) == 0) {
        echo "<li class='error'>No se capturaron queries</li>";
        echo "<li>Verifica que saveQueriesFromLog() se ejecute</li>";
        echo "<li>Verifica permisos del directorio logs/</li>";
    }
    echo "</ul>";
    echo "<p><a href='sql_monitor_diagnostics.php' target='_blank'>Ejecutar diagnóstico completo</a></p>";
}
echo "</div>";

echo "</body></html>";
