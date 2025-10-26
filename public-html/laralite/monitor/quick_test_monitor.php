<?php
/**
 * Test SQL Monitor - Diagnóstico Completo
 */

require_once '../App/Config/config.php';
require_once '../App/Core/autoload.php';
require_once '../App/Utils/Autoloader.php';

use App\Database\EloquentBootstrap;
use Illuminate\Database\Capsule\Manager as DB;

App\Utils\Autoloader::register();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Test SQL Monitor</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f8f9fa;
            padding: 20px;
            line-height: 1.5;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { 
            font-size: 24px;
            margin-bottom: 20px;
            color: #000;
            font-weight: 600;
        }
        .box { 
            background: white;
            padding: 16px;
            margin-bottom: 12px;
            border-radius: 6px;
            border-left: 3px solid #000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .box h2 { 
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #000;
        }
        .box p { font-size: 13px; margin: 6px 0; color: #495057; }
        .success { color: #28a745; font-weight: 500; }
        .error { color: #dc3545; font-weight: 500; }
        .warning { color: #ffc107; font-weight: 500; }
        .info { color: #6c757d; }
        pre { 
            background: #f8f9fa;
            padding: 12px;
            border-radius: 4px;
            font-size: 11px;
            overflow-x: auto;
            border: 1px solid #e9ecef;
            margin: 8px 0;
            max-height: 300px;
        }
        .summary {
            background: #000;
            color: white;
            padding: 20px;
            border-radius: 6px;
        }
        .summary h3 { font-size: 18px; margin-bottom: 12px; }
        .summary ul { 
            list-style: none;
            margin: 12px 0;
            padding-left: 0;
        }
        .summary li { 
            padding: 6px 0;
            font-size: 13px;
        }
        .summary a {
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.3);
            transition: border-color 0.2s;
        }
        .summary a:hover { border-bottom-color: #fff; }
        .badge { 
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            background: rgba(255,255,255,0.2);
            margin-left: 6px;
        }
        .code-inline {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            border: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Test SQL Monitor</h1>

        <?php
        // 1. Inicializar
        echo "<div class='box'><h2>1. Inicialización</h2>";
        $DB = EloquentBootstrap::init();
        echo "<p class='success'>✓ Eloquent iniciado</p>";
        echo "<p>Driver: <span class='code-inline'>{$DB->getConnection()->getDriverName()}</span> | ";
        echo "DB: <span class='code-inline'>{$DB->getConnection()->getDatabaseName()}</span></p>";
        
        // Verificar si el logging está habilitado
        $loggingEnabled = $DB->getConnection()->logging();
        echo "<p>Query Log: <span class='" . ($loggingEnabled ? 'success' : 'error') . "'>";
        echo $loggingEnabled ? '✓ HABILITADO' : '✗ DESHABILITADO';
        echo "</span></p>";
        echo "</div>";

        // 2. Verificar archivo y permisos
        echo "<div class='box'><h2>2. Verificación de Archivo</h2>";
        $queriesFile = __DIR__ . '/logs/sql_queries.json';
        
        if (!file_exists(dirname($queriesFile))) {
            mkdir(dirname($queriesFile), 0777, true);
            echo "<p class='warning'>⚠ Directorio creado</p>";
        }
        
        file_put_contents($queriesFile, '[]');
        echo "<p class='success'>✓ Archivo limpiado</p>";
        echo "<p>Path: <span class='code-inline'>$queriesFile</span></p>";
        echo "<p>Permisos: <span class='code-inline'>" . substr(sprintf('%o', fileperms($queriesFile)), -4) . "</span></p>";
        echo "</div>";

        // 3. Ejecutar query SIN usar Eloquent directamente
        echo "<div class='box'><h2>3. Ejecución de Query</h2>";
        
        // Limpiar el log antes de la prueba
        DB::flushQueryLog();
        
        try {
            $start = microtime(true);
            
            // Ejecutar query que DEBE ser capturada
            $result = DB::select("SELECT 'TEST_MONITOR' as test, NOW() as timestamp, ? as param", ['valor_test']);
            
            $time = round((microtime(true) - $start) * 1000, 2);
            echo "<p class='success'>✓ Query ejecutada en {$time}ms</p>";
            echo "<pre>" . json_encode($result, JSON_PRETTY_PRINT) . "</pre>";
            
            // Verificar si quedó en el log
            $queryLog = DB::getQueryLog();
            $logged = count($queryLog) > 0;
            
            echo "<p>Queries en log interno: <span class='" . ($logged ? 'success' : 'error') . "'>";
            echo $logged ? "✓ " . count($queryLog) : "✗ 0";
            echo "</span></p>";
            
            if ($logged) {
                echo "<p class='info'>Query capturada en memoria:</p>";
                echo "<pre>" . json_encode($queryLog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            } else {
                echo "<p class='error'>✗ Query NO capturada en el log interno</p>";
                echo "<p class='warning'>⚠ El logging puede estar deshabilitado o el listener no funciona</p>";
            }
            
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error: {$e->getMessage()}</p>";
        }
        echo "</div>";

        // 4. Forzar guardado
        echo "<div class='box'><h2>4. Guardado de Queries</h2>";
        try {
            EloquentBootstrap::saveQueriesFromLog();
            echo "<p class='success'>✓ saveQueriesFromLog() ejecutado</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Error al guardar: {$e->getMessage()}</p>";
        }
        echo "</div>";

        // 5. Verificar archivo
        echo "<div class='box'><h2>5. Verificación Final</h2>";
        $queries = [];
        if (file_exists($queriesFile)) {
            $content = file_get_contents($queriesFile);
            $queries = json_decode($content, true);
            $count = is_array($queries) ? count($queries) : 0;
            
            echo "<p>Tamaño: <span class='code-inline'>" . filesize($queriesFile) . " bytes</span></p>";
            echo "<p>Queries guardadas: <span class='" . ($count > 0 ? 'success' : 'error') . "'>";
            echo $count > 0 ? "✓ $count" : "✗ 0";
            echo "</span></p>";
            
            if ($count > 0) {
                echo "<p class='success'>✓ Sistema funcionando correctamente</p>";
                echo "<p class='info'>Última query capturada:</p>";
                echo "<pre>" . json_encode($queries[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            } else {
                echo "<p class='error'>✗ Archivo vacío - las queries no se están guardando</p>";
                echo "<p>Contenido raw:</p>";
                echo "<pre>" . htmlspecialchars($content) . "</pre>";
            }
        } else {
            echo "<p class='error'>✗ Archivo no existe</p>";
        }
        echo "</div>";

        // 6. Diagnóstico
        echo "<div class='box'><h2>6. Diagnóstico del Sistema</h2>";
        echo "<ul style='list-style: none; padding-left: 0;'>";
        
        // Check 1: Logging habilitado
        $check1 = $DB->getConnection()->logging();
        echo "<li><span class='" . ($check1 ? 'success' : 'error') . "'>";
        echo $check1 ? '✓' : '✗';
        echo "</span> Query logging " . ($check1 ? 'habilitado' : 'DESHABILITADO') . "</li>";
        
        // Check 2: Queries en log
        $check2 = isset($queryLog) && count($queryLog) > 0;
        echo "<li><span class='" . ($check2 ? 'success' : 'error') . "'>";
        echo $check2 ? '✓' : '✗';
        echo "</span> Queries capturadas en memoria</li>";
        
        // Check 3: Archivo escribible
        $check3 = is_writable($queriesFile);
        echo "<li><span class='" . ($check3 ? 'success' : 'error') . "'>";
        echo $check3 ? '✓' : '✗';
        echo "</span> Archivo escribible</li>";
        
        // Check 4: Queries guardadas
        $check4 = count($queries) > 0;
        echo "<li><span class='" . ($check4 ? 'success' : 'error') . "'>";
        echo $check4 ? '✓' : '✗';
        echo "</span> Queries guardadas en archivo</li>";
        
        echo "</ul>";
        
        // Recomendaciones
        if (!$check1) {
            echo "<p class='error' style='margin-top: 12px;'>⚠ PROBLEMA: El query logging está deshabilitado</p>";
            echo "<p class='info' style='font-size: 12px;'>Verifica que <span class='code-inline'>enableQueryLog()</span> se llame en EloquentBootstrap</p>";
        }
        
        if ($check1 && !$check2) {
            echo "<p class='error' style='margin-top: 12px;'>⚠ PROBLEMA: Queries no se capturan en memoria</p>";
            echo "<p class='info' style='font-size: 12px;'>El listener puede no estar funcionando correctamente</p>";
        }
        
        if ($check2 && !$check4) {
            echo "<p class='error' style='margin-top: 12px;'>⚠ PROBLEMA: Queries en memoria pero no se guardan</p>";
            echo "<p class='info' style='font-size: 12px;'>Verifica el método <span class='code-inline'>saveQueriesFromLog()</span></p>";
        }
        
        echo "</div>";

        // 7. Resumen
        $allWorking = $check1 && $check2 && $check3 && $check4;
        ?>

        <div class="summary">
            <?php if ($allWorking): ?>
                <h3>✓ Sistema Operativo</h3>
                <ul>
                    <li>Queries capturadas: <span class="badge"><?= count($queries) ?></span></li>
                    <li>Logging: <span class="badge">ACTIVO</span></li>
                    <li>Archivo: <span class="badge">OK</span></li>
                </ul>
                <p style="margin-top: 16px; font-size: 13px;">
                    <a href='sql_monitor.php' target='_blank'>→ Abrir Monitor Visual</a> |
                    <a href='test_sql_monitor.php' target='_blank'>→ Más Pruebas</a>
                </p>
            <?php else: ?>
                <h3>✗ Sistema con Problemas</h3>
                <ul>
                    <?php if (!$check1): ?>
                        <li>Habilitar query logging en EloquentBootstrap</li>
                    <?php endif; ?>
                    <?php if ($check1 && !$check2): ?>
                        <li>Revisar configuración de listeners</li>
                    <?php endif; ?>
                    <?php if (!$check3): ?>
                        <li>Verificar permisos del directorio logs/</li>
                    <?php endif; ?>
                    <?php if ($check2 && !$check4): ?>
                        <li>Revisar método saveQueriesFromLog()</li>
                    <?php endif; ?>
                </ul>
                <p style="margin-top: 16px; font-size: 13px;">
                    <a href='sql_monitor_diagnostics.php'>→ Ejecutar Diagnóstico Completo</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>