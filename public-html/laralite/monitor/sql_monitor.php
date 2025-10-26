<?php
/**
 * SQL Query Monitor - LaraLite
 * 
 * Monitoreo simple y limpio de queries SQL
 * Acceso: /sql_monitor.php
 */

namespace App;

use App\Database\EloquentBootstrap;
use App\Utils\Autoloader;

require_once '../config/config.php';
require_once '../App/Core/autoload.php';
require_once '../App/Utils/Autoloader.php';

Autoloader::register();

global $DB;
$DB = EloquentBootstrap::init();
$DB->getConnection()->enableQueryLog();

$queries = [];

$DB->getConnection()->listen(function ($query) use (&$queries) {
    $queries[] = [
        'sql' => $query->sql,
        'bindings' => $query->bindings,
        'time' => $query->time,
        'timestamp' => microtime(true)
    ];
});

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LaraLite - SQL Monitor</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .header p {
            color: #666;
            font-size: 13px;
        }

        .toolbar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .input-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex: 1;
            min-width: 250px;
        }

        input[type="text"] {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 13px;
            font-family: inherit;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #333;
        }

        .btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            color: #333;
            border-radius: 4px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn:hover {
            background: #f9f9f9;
            border-color: #999;
        }

        .btn.active {
            background: #333;
            color: white;
            border-color: #333;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat {
            background: white;
            padding: 15px;
            border-radius: 4px;
            border-left: 3px solid #333;
        }

        .stat-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 600;
            color: #333;
        }

        .stat-unit {
            color: #999;
            font-size: 12px;
            margin-left: 5px;
        }

        .queries {
            background: #1e1e1e;
            border-radius: 4px;
            border: 1px solid #333;
            max-height: calc(100vh - 350px);
            overflow-y: auto;
        }

        .query {
            border-bottom: 1px solid #333;
            padding: 12px 15px;
        }

        .query:hover {
            background: #252525;
        }

        .query:last-child {
            border-bottom: none;
        }

        .query-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 10px;
        }

        .query-time {
            font-size: 12px;
            color: #888;
            white-space: nowrap;
        }

        .query-duration {
            display: inline-block;
            background: #333;
            color: #ddd;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 8px;
            border: 1px solid #555;
        }

        .query-sql {
            background: #0f0f0f;
            border: 1px solid #333;
            padding: 10px;
            border-radius: 3px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            color: #00ff00;
            white-space: pre-wrap;
            word-break: break-all;
            margin-bottom: 8px;
            overflow-x: auto;
            line-height: 1.4;
        }

        .query-bindings {
            font-size: 12px;
            color: #aaa;
        }

        .query-bindings strong {
            color: #ddd;
        }

        .binding {
            display: inline-block;
            background: #333;
            color: #bbb;
            padding: 2px 6px;
            border-radius: 2px;
            margin-right: 5px;
            margin-top: 3px;
            border: 1px solid #555;
            font-family: 'Monaco', 'Menlo', monospace;
            font-size: 11px;
        }

        .empty {
            text-align: center;
            padding: 40px;
            color: #666;
        }

        .empty h3 {
            font-size: 16px;
            margin-bottom: 8px;
            color: #888;
        }

        .empty p {
            font-size: 13px;
        }

        /* Scrollbar */
        .queries::-webkit-scrollbar {
            width: 8px;
        }

        .queries::-webkit-scrollbar-track {
            background: #1e1e1e;
        }

        .queries::-webkit-scrollbar-thumb {
            background: #444;
            border-radius: 4px;
        }

        .queries::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #333;
            border-radius: 50%;
            margin-right: 8px;
        }

        .indicator.active {
            background: #00ff00;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .indicator.active {
            animation: blink 2s infinite;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><span class="indicator" id="indicator"></span>LaraLite SQL Monitor</h1>
            <p>Monitoreo de queries SQL en tiempo real</p>
        </div>

        <div class="toolbar">
            <div class="input-group">
                <input type="text" id="filterInput" placeholder="Filtrar queries...">
            </div>
            <button class="btn" onclick="refreshQueries()">Actualizar</button>
            <button class="btn active" id="autoBtn" onclick="toggleAutoRefresh()">Auto: ON</button>
            <button class="btn" onclick="clearQueries()">Limpiar</button>
        </div>

        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total</div>
                <div class="stat-value" id="totalQueries">0</div>
            </div>
            <div class="stat">
                <div class="stat-label">Tiempo Total</div>
                <div class="stat-value">
                    <span id="totalTime">0</span>
                    <span class="stat-unit">ms</span>
                </div>
            </div>
            <div class="stat">
                <div class="stat-label">Promedio</div>
                <div class="stat-value">
                    <span id="avgTime">0</span>
                    <span class="stat-unit">ms</span>
                </div>
            </div>
            <div class="stat">
                <div class="stat-label">Más Lenta</div>
                <div class="stat-value">
                    <span id="slowestQuery">0</span>
                    <span class="stat-unit">ms</span>
                </div>
            </div>
        </div>

        <div class="queries" id="queriesContainer">
            <div class="empty">
                <h3>Sin queries</h3>
                <p>Realiza alguna acción para ver las queries aquí</p>
            </div>
        </div>
    </div>

    <script>
        let autoRefresh = true;
        let filterText = '';
        let allQueries = [];
        let lastQueryIds = new Set();

        startAutoRefresh();
        updateIndicator();

        function startAutoRefresh() {
            setInterval(() => {
                if (autoRefresh) {
                    fetchAndUpdate();
                }
            }, 2000);
        }

        function updateIndicator() {
            const indicator = document.getElementById('indicator');
            if (autoRefresh) {
                indicator.classList.add('active');
            } else {
                indicator.classList.remove('active');
            }
        }

        function toggleAutoRefresh() {
            autoRefresh = !autoRefresh;
            const btn = document.getElementById('autoBtn');
            btn.classList.toggle('active');
            btn.textContent = 'Auto: ' + (autoRefresh ? 'ON' : 'OFF');
            updateIndicator();
        }

        function fetchAndUpdate() {
            fetch('sql_monitor_api.php')
                .then(r => r.json())
                .then(data => {
                    updateStats(data.stats);
                    allQueries = data.queries.reverse();
                    renderQueries();
                })
                .catch(e => console.error('Error:', e));
        }

        function refreshQueries() {
            fetchAndUpdate();
        }

        function renderQueries() {
            const container = document.getElementById('queriesContainer');
            
            if (allQueries.length === 0) {
                container.innerHTML = '<div class="empty"><h3>Sin queries</h3><p>Realiza alguna acción para ver las queries aquí</p></div>';
                return;
            }

            let filtered = allQueries;
            if (filterText) {
                filtered = allQueries.filter(q => q.sql.toLowerCase().includes(filterText.toLowerCase()));
            }

            // Construir HTML sin animar
            let html = '';
            filtered.forEach(query => {
                const time = new Date(query.timestamp * 1000).toLocaleTimeString('es-MX');
                const bindings = query.bindings.length > 0 
                    ? query.bindings.map(b => `<span class="binding">${escapeHtml(JSON.stringify(b))}</span>`).join('')
                    : '<span class="binding">-</span>';

                html += `
                    <div class="query">
                        <div class="query-header">
                            <div>
                                <span class="query-time">${time}</span>
                                <span class="query-duration">${query.time.toFixed(2)}ms</span>
                            </div>
                        </div>
                        <div class="query-sql">${escapeHtml(query.sql)}</div>
                        <div class="query-bindings">
                            <strong>Parámetros:</strong> ${bindings}
                        </div>
                    </div>
                `;
            });

            container.innerHTML = html;
        }

        function updateStats(stats) {
            document.getElementById('totalQueries').textContent = stats.total;
            document.getElementById('totalTime').textContent = stats.totalTime.toFixed(2);
            document.getElementById('avgTime').textContent = stats.avgTime.toFixed(2);
            document.getElementById('slowestQuery').textContent = stats.slowest.toFixed(2);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function clearQueries() {
            if (confirm('¿Limpiar todas las queries?')) {
                fetch('sql_monitor_api.php?action=clear', { method: 'POST' })
                    .then(() => {
                        allQueries = [];
                        lastQueryIds.clear();
                        refreshQueries();
                    });
            }
        }

        document.getElementById('filterInput').addEventListener('input', e => {
            filterText = e.target.value;
            renderQueries();
        });

        fetchAndUpdate();
    </script>
</body>
</html>