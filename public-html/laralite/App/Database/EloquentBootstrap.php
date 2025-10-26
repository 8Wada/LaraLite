<?php

namespace App\Database;

use Illuminate\Database\Capsule\Manager as Capsule;

class EloquentBootstrap
{
  private static $capsule = null;

  public static function init()
  {
    if (self::$capsule === null) {
      self::$capsule = new Capsule;

      self::$capsule->addConnection([
        'driver'    => getenv('DB_DRIVER') ?: 'mysql',
        'host'      => getenv('DB_HOST') ?: getenv('ENDPOINT') ?: '127.0.0.1',
        'database'  => getenv('DB_DATABASE') ?: getenv('DATABASE') ?: '',
        'port'      => getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: '3306',
        'username'  => getenv('DB_USERNAME') ?: getenv('USERD') ?: '',
        'password'  => getenv('DB_PASSWORD') ?: getenv('PASSD') ?: '',
        'options'   => [
          \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
          \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
          \PDO::ATTR_EMULATE_PREPARES => false,
        ],
      ]);

      self::$capsule->setAsGlobal();
      self::$capsule->bootEloquent();

      // Habilitar query logging para el monitor SQL
      self::$capsule->getConnection()->enableQueryLog();

      // Registrar shutdown function para guardar queries al final de la request
      register_shutdown_function([self::class, 'saveQueriesFromLog']);
    }

    return self::$capsule;
  }

  /**
   * Get all queries from the log and save them
   * Call this method periodically or at the end of request
   */
  public static function saveQueriesFromLog()
  {
    if (self::$capsule === null) {
      return;
    }

    try {
      $queries = self::$capsule->getConnection()->getQueryLog();

      if (empty($queries)) {
        return;
      }

      $queriesFile = __DIR__ . '/../../logs/sql_queries.json';

      // Crear directorio si no existe
      $logsDir = dirname($queriesFile);
      if (!is_dir($logsDir)) {
        @mkdir($logsDir, 0777, true);
      }

      // Cargar queries existentes
      $existingQueries = [];
      if (file_exists($queriesFile)) {
        $content = @file_get_contents($queriesFile);
        if ($content !== false) {
          $existingQueries = json_decode($content, true) ?: [];
        }
      }

      // Agregar nuevas queries con timestamp
      foreach ($queries as $query) {
        $newQuery = [
          'sql' => $query['query'] ?? '',
          'bindings' => $query['bindings'] ?? [],
          'time' => $query['time'] ?? 0,
          'timestamp' => microtime(true)
        ];

        array_unshift($existingQueries, $newQuery);
      }

      // Limitar a 100 queries
      $existingQueries = array_slice($existingQueries, 0, 100);

      // Guardar
      @file_put_contents($queriesFile, json_encode($existingQueries));

      // Limpiar el log después de guardar para evitar duplicados
      self::$capsule->getConnection()->flushQueryLog();
    } catch (\Exception $e) {
      error_log("SQL Monitor Error: " . $e->getMessage());
    }
  }

  public static function getConnectionInfo()
  {
    try {
      $DB = self::init();
      $connection = $DB->getConnection();
      $config = $connection->getConfig();

      // Probar la conexión
      $connection->getPdo();

      return [
        "message"   => "Database connection is working correctly.",
        "driver"    => $connection->getDriverName(),
        "host"      => $config['host'] ?? 'N/A',
        "database"  => $connection->getDatabaseName(),
        "username"  => $config['username'] ?? 'N/A',
        "charset"   => $config['charset'] ?? 'N/A',
        "collation" => $config['collation'] ?? 'N/A',
        "prefix"    => $config['prefix'] ?? '',
        "status"    => "Connected successfully"
      ];
    } catch (\Exception $e) {
      $config = self::$capsule?->getConnection()?->getConfig() ?? [];

      // En caso de que la conexión ni siquiera se inicialice correctamente
      $host     = $config['host'] ?? (getenv('DB_HOST') ?: getenv('ENDPOINT') ?: '127.0.0.1');
      $database = $config['database'] ?? (getenv('DB_DATABASE') ?: getenv('DATABASE') ?: '');
      $user     = $config['username'] ?? (getenv('DB_USERNAME') ?: getenv('USERD') ?: '');


      echo json_encode([
        "status"         => "error",
        "message"        => "Error al conectar a la base de datos.",
        "server"         => $host,
        "database"       => $database,
        "user"           => $user,
        "error_details"  => $e->getMessage()
      ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

      // Si deseas cortar ejecución
      exit;
    }
  }
}
