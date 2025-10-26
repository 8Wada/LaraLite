#!/usr/bin/env php
<?php

require_once 'App/Core/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$command = $argv[1] ?? null;
$args = array_slice($argv, 2);

if (!$command) {
    displayHelp();
    exit(0);
}

[$group, $action] = explode(':', $command) + [1 => null];

$commands = [
    'make' => [
        'model' => fn($args) => makeModel($args[0] ?? null, $args[1] ?? null),
        'controller' => fn($args) => makeController($args[0] ?? null, $args[1] ?? null),
        'service' => fn($args) => makeService($args[0] ?? null, $args[1] ?? null),
        'route' => fn($args) => makeRoute($args[0] ?? null, $args[1] ?? null),
        'entity' => fn($args) => makeEntity($args[0] ?? null),
        'seed' => fn($args) => makeSeeder($args[0] ?? null),
        'migration' => fn($args) => makeMigration($args),
        'middleware' => fn($args) => makeMiddleware($args[0] ?? null),
        'command' => fn($args) => makeCommand($args[0] ?? null),
        'resource' => fn($args) => makeResource($args[0] ?? null),
    ],
    'migration' => [
        'up' => fn() => runMigrations(),
        'down' => fn($args) => rollbackMigrations((int)($args[0] ?? 1)),
        'status' => fn() => listMigrations(),
        'rollback' => fn($args) => rollbackMigrations((int)($args[0] ?? 1)),
        'undo' => fn($args) => undoSpecificMigration($args[0] ?? null),
    ],
    'db' => [
        'info' => fn() => print_r(\App\Database\EloquentBootstrap::getConnectionInfo()),
        'migrate' => fn() => runMigrations(),
        'rollback' => fn($args) => rollbackMigrations((int)($args[0] ?? 1)),
        'status' => fn() => listMigrations(),
        'seed' => fn() => runSeeders(),
        'undo' => fn($args) => undoSpecificMigration($args[0] ?? null),
    ],
];

if (!isset($commands[$group][$action])) {
    error("Command '{$group}:{$action}' is not defined.");
    exit(1);
}

$commands[$group][$action]($args);

function displayHelp()
{
    $logo = <<<LOGO
\033[1;31m
  _                     _ _ _       
 | |                   | (_) |      
 | |     __ _ _ __ __ _| |_| |_ ___ 
 | |    / _` | '__/ _` | | | __/ _ \
 | |___| (_| | | | (_| | | | ||  __/
 |______\__,_|_|  \__,_|_|_|\__\___|
\033[0m
LOGO;

    echo $logo . "\n";
    echo "\033[33mLaralite Framework\033[0m \033[90m1.0.0\033[0m\n\n";
    
    echo "\033[33mUsage:\033[0m\n";
    echo "  command [options] [arguments]\n\n";
    
    echo "\033[33mAvailable commands:\033[0m\n\n";
    
    echo " \033[33mmake\033[0m\n";
    echo "  \033[32mmake:command\033[0m       Create a new command\n";
    echo "  \033[32mmake:controller\033[0m    Create a new controller class\n";
    echo "  \033[32mmake:entity\033[0m        Create a new entity with structure\n";
    echo "  \033[32mmake:middleware\033[0m    Create a new middleware class\n";
    echo "  \033[32mmake:migration\033[0m     Create a new migration file\n";
    echo "  \033[32mmake:model\033[0m         Create a new Eloquent model class\n";
    echo "  \033[32mmake:resource\033[0m      Create a new resource\n";
    echo "  \033[32mmake:route\033[0m         Create a new route file\n";
    echo "  \033[32mmake:seed\033[0m          Create a new seeder class\n";
    echo "  \033[32mmake:service\033[0m       Create a new service class\n\n";
    
    echo " \033[33mmigration\033[0m\n";
    echo "  \033[32mmigration:up\033[0m       Run the database migrations\n";
    echo "  \033[32mmigration:down\033[0m     Rollback migrations\n";
    echo "  \033[32mmigration:rollback\033[0m Rollback the last database migration\n";
    echo "  \033[32mmigration:status\033[0m   Show the status of each migration\n";
    echo "  \033[32mmigration:undo\033[0m     Undo a specific migration by filename\n\n";
    
    echo " \033[33mdb\033[0m\n";
    echo "  \033[32mdb:info\033[0m            Show database connection info\n";
    echo "  \033[32mdb:migrate\033[0m         Run the database migrations\n";
    echo "  \033[32mdb:rollback\033[0m        Rollback the last database migration\n";
    echo "  \033[32mdb:status\033[0m          Show the status of each migration\n";
    echo "  \033[32mdb:seed\033[0m            Seed the database with records\n";
    echo "  \033[32mdb:undo\033[0m            Undo a specific migration by filename\n\n";
}

function makeModel($fullName, $entity = null)
{
    if (!$fullName) return fail("Not enough arguments (missing: \"name\").");

    if (strpos($fullName, '/') !== false) {
        [$entityDetected, $className] = explode('/', $fullName, 2);
    } else {
        $entityDetected = $entity;
        $className = $fullName;
    }

    $basePath = $entityDetected ? "./$entityDetected/Models" : "./App/Models";
    $path = "$basePath/{$className}Model.php";

    if (file_exists($path)) return fail("Model already exists!");

    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entityNamespace = $entityDetected ? $entityDetected . "\\Models" : "App\\Models";
    $classNameLower = strtolower($className);

    file_put_contents($path, <<<PHP
<?php

namespace $entityNamespace;

use Illuminate\Database\Eloquent\Model;

class {$className}Model extends Model
{
    protected \$table = '{$classNameLower}_mains';
    protected \$guarded = [];
}
PHP);

    done("Model [\e[32m{$path}\e[0m] created successfully.");
}

function makeController($fullName, $entity = null)
{
    if (!$fullName) return fail("Not enough arguments (missing: \"name\").");

    if (strpos($fullName, '/') !== false) {
        [$entityDetected, $className] = explode('/', $fullName, 2);
    } else {
        $entityDetected = $entity;
        $className = $fullName;
    }

    $basePath = $entityDetected ? "./$entityDetected/Controllers" : "./App/Controllers";
    $path = "$basePath/{$className}Controller.php";

    if (file_exists($path)) return fail("Controller already exists!");

    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entityNamespace = $entityDetected ? $entityDetected . "\\Controllers" : "App\\Controllers";

    file_put_contents($path, <<<PHP
<?php

namespace $entityNamespace;
use App\Controllers\Controller;

class {$className}Controller extends Controller
{
    public function index()
    {
        // ...
    }
}
PHP);

    // done("Controller [\e[32m{$path}\e[0m] created successfully.");
    //Verificar si la creación fue exitosa
    if (file_exists($path)) {
        done("Controller [\e[32m{$path}\e[0m] created successfully.");
    } else {
        fail("Failed to create controller [\e[32m{$path}\e[0m].");
    }
}

function makeService($fullName, $entity = null)
{
    if (!$fullName) return fail("Not enough arguments (missing: \"name\").");

    if (strpos($fullName, '/') !== false) {
        [$entityDetected, $className] = explode('/', $fullName, 2);
    } else {
        $entityDetected = $entity;
        $className = $fullName;
    }

    $basePath = $entityDetected ? "./$entityDetected/Services" : "./App/Services";
    $path = "$basePath/{$className}Service.php";

    if (file_exists($path)) return fail("Service already exists!");

    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $entityNamespace = $entityDetected ? $entityDetected . "\\Services" : "App\\Services";

    file_put_contents($path, <<<PHP
<?php

namespace $entityNamespace;

class {$className}Service
{
    public function __construct()
    {
        // ...
    }
}
PHP
    );

    done("Service [\e[32m{$path}\e[0m] created successfully.");
}

function makeRoute($name, $entity = null)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");
    
    $basePath = $entity ? "./$entity/Routes" : "./App/Routes";
    $path = "$basePath/{$name}.php";
    $entityLower = 'App';
    
    if ($entity) {
        $entityLower = strtolower($entity);
    }

    if (file_exists($path)) return fail("Route file already exists!");

    if (!is_dir($basePath)) mkdir($basePath, 0755, true);
    
    file_put_contents($path, <<<PHP
<?php

global \$DB, \$r;
use {$entity}\\Controllers\\{$name}Controller;
\$ctlr = new {$name}Controller(\$DB);
\$r->setBasePath('{$entityLower}');
\$r->setRouteVersion('v1');
// Aquí puedes definir las rutas
\$r->get('/', fn(\$req) => \$ctlr->index());
PHP);
    
    done("Route file [\e[32m{$path}\e[0m] created successfully.");
}

function makeEntity($name)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");

    $nameLower = strtolower($name);

    echo "\n  \033[44;97m RUNNING \033[0m Creating entity structure...\n";
    
    $folders = ['Controllers', 'Models', 'Routes'];
    foreach ($folders as $folder) {
        $path = "./$name/$folder";
        if (!is_dir($path)) mkdir($path, 0755, true);
    }

    $modelPath = "./$name/Models/{$name}Model.php";
    if (!file_exists($modelPath)) {
        file_put_contents($modelPath, <<<PHP
<?php

namespace $name\Models;

use Illuminate\Database\Eloquent\Model;

class {$name}Model extends Model
{
    protected \$table = '{$nameLower}_mains';
    protected \$guarded = [];
}
PHP);
        echo "  \033[32m✓\033[0m Model created\n";
    }

    $controllerPath = "./$name/Controllers/{$name}Controller.php";
    if (!file_exists($controllerPath)) {
        file_put_contents($controllerPath, <<<PHP
<?php

namespace $name\Controllers;
use App\Controllers\Controller;

class {$name}Controller extends Controller
{
    public function index()
    {
        return 'Index del controlador principal de $name';
    }
}
PHP);
        echo "  \033[32m✓\033[0m Controller created\n";
    }

    $routesPath = "./$name/Routes/web.php";
    if (!file_exists($routesPath)) {
        file_put_contents($routesPath, <<<PHP
<?php

global \$DB, \$r;

use $name\Controllers\\{$name}Controller;
\$ctlr = new {$name}Controller(\$DB);

\$r->setBasePath('$nameLower');
\$r->setRouteVersion('v1');

\$r->get('/', fn(\$req) => \$ctlr->index());

PHP);
        echo "  \033[32m✓\033[0m Routes created\n";
    }

    done("Entity [\e[32m{$name}\e[0m] created successfully.");
}

function makeSeeder($name)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");
    
    $path = __DIR__ . "/App/Database/Seeders/{$name}Seeder.php";
    if (file_exists($path)) return fail("Seeder already exists!");

    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    
    file_put_contents($path, <<<PHP
<?php

namespace App\Database\Seeders;

use App\Models\\{$name};

class {$name}Seeder
{
    public function run()
    {
        {$name}::create([
            'name' => 'Example'
        ]);
    }
}
PHP);
    
    done("Seeder [\e[32m{$path}\e[0m] created successfully.");
}

function makeMigration($args)
{
    if (empty($args)) return fail("Not enough arguments (missing: \"name\").");

    $name = $args[0];
    $options = [];
    for ($i = 1; $i < count($args); $i++) {
        if (strpos($args[$i], '--') === 0) {
            $opt = explode('=', substr($args[$i], 2));
            $options[$opt[0]] = $opt[1] ?? true;
        }
    }

    if (isset($options['create'])) {
        $table = $options['create'];
        $type = 'create';
    } elseif (isset($options['table'])) {
        $table = $options['table'];
        $type = 'modify';
    } else {
        return fail("Expected --create or --table option to be present.");
    }

    $timestamp = date('Y_m_d_His');
    $className = 'Migration_' . $timestamp . '_' . str_replace('_', '', ucwords($name, '_'));
    $fileName = "{$timestamp}_{$name}.php";
    $path = __DIR__ . "/App/Database/Migrations/{$fileName}";

    if (file_exists($path)) return fail("Migration already exists!");

    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);

    if ($type === 'create') {
        $upCode = "Capsule::schema()->create('$table', function (Blueprint \$table) {\n            \$table->id();\n            \$table->string('name');\n            \$table->timestamps();\n        });";
        $downCode = "Capsule::schema()->dropIfExists('$table');";
    } else {
        $upCode = "Capsule::schema()->table('$table', function (Blueprint \$table) {\n            // TODO: Agregar columnas o modificaciones\n        });";
        $downCode = "// TODO: Implementar la reversión";
    }

    file_put_contents($path, <<<PHP
<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class {$className} extends Migration
{
    public function up()
    {
        $upCode
    }

    public function down()
    {
        $downCode
    }
}
PHP);

    done("Migration [\e[32m{$fileName}\e[0m] created successfully.");
}

function makeMiddleware($name)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");
    
    $path = __DIR__ . "/App/Middlewares/{$name}.php";
    if (file_exists($path)) return fail("Middleware already exists!");

    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    
    file_put_contents($path, <<<PHP
<?php

namespace App\Middlewares;

class {$name}
{
    public function handle(\$request, \$next)
    {
        // Lógica del middleware aquí
        return \$next(\$request);
    }
}
PHP);
    
    done("Middleware [\e[32m{$path}\e[0m] created successfully.");
}

function makeCommand($name)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");
    
    $path = __DIR__ . "/App/Commands/{$name}Command.php";
    if (file_exists($path)) return fail("Command already exists!");

    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    
    file_put_contents($path, <<<PHP
<?php

namespace App\Commands;

class {$name}Command
{
    public function execute(\$args)
    {
        echo "Ejecutando comando {$name} con argumentos: " . implode(', ', \$args) . "\\n";
    }
}
PHP);
    
    done("Command [\e[32m{$path}\e[0m] created successfully.");
}

function makeResource($name)
{
    if (!$name) return fail("Not enough arguments (missing: \"name\").");
    
    $path = __DIR__ . "/App/Resources/{$name}Resource.php";
    if (file_exists($path)) return fail("Resource already exists!");

    if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
    
    file_put_contents($path, <<<PHP
<?php

namespace App\Resources;

class {$name}Resource
{
    public function toArray(\$data)
    {
        return [
            'id' => \$data->id,
            'name' => \$data->name,
            // Agregar más campos según sea necesario
        ];
    }
}
PHP);
    
    done("Resource [\e[32m{$path}\e[0m] created successfully.");
}

function runMigrations()
{
    require_once __DIR__ . '/App/Database/EloquentBootstrap.php';
    \App\Database\EloquentBootstrap::init();

    $migrationDir = __DIR__ . '/App/Database/Migrations';
    $files = glob($migrationDir . '/*.php');

    if (empty($files)) {
        info("Nothing to migrate.");
        return;
    }

    // Asegura que exista la tabla migration_history
    if (!Capsule::schema()->hasTable('migration_history')) {
        try {
            Capsule::schema()->create('migration_history', function ($table) {
                $table->increments('id');
                $table->string('migration_file')->unique();
                $table->timestamp('executed_at')->default(Capsule::raw('CURRENT_TIMESTAMP'));
            });
            echo "  \033[32m✓\033[0m Table 'migration_history' created\n";
        } catch (Exception $e) {
            return fail("Could not create migration_history table: " . $e->getMessage());
        }
    }

    $appliedMigrations = Capsule::table('migration_history')->pluck('migration_file')->toArray();
    
    $pending = [];
    foreach ($files as $file) {
        $migrationFile = basename($file);
        if (!in_array($migrationFile, $appliedMigrations)) {
            $pending[] = $file;
        }
    }
    
    if (empty($pending)) {
        info("Nothing to migrate.");
        return;
    }

    echo "\n  \033[44;97m RUNNING \033[0m Running migrations...\n\n";

    foreach ($pending as $file) {
        $migrationFile = basename($file);

        try {
            require_once $file;
            $basename = basename($file, '.php');
            $parts = explode('_', $basename);
            $timestamp = implode('_', array_slice($parts, 0, 4));
            $name = implode('_', array_slice($parts, 4));
            $studlyName = str_replace('_', '', ucwords($name, '_'));
            $className = "App\\Database\\Migrations\\Migration_{$timestamp}_{$studlyName}";

            if (!class_exists($className)) {
                echo "  \033[31m✘\033[0m Class not found: $className\n";
                continue;
            }

            $migration = new $className();
            $migration->up();

            Capsule::table('migration_history')->insert([
                'migration_file' => $migrationFile,
                'executed_at' => date('Y-m-d H:i:s'),
            ]);

            // echo "  \033[32m✓\033[0m {$migrationFile}\n";
            done($migrationFile);
        } catch (Throwable $e) {
            fail("Migration failed: {$migrationFile}\n  " . $e->getMessage());
        }
    }

    echo "\n";
}

function rollbackMigrations($count = 1)
{
    require_once __DIR__ . '/App/Database/EloquentBootstrap.php';
    \App\Database\EloquentBootstrap::init();

    if (!Capsule::schema()->hasTable('migration_history')) {
        return info("Nothing to rollback.");
    }

    $migrations = Capsule::table('migration_history')
        ->orderBy('id', 'desc')
        ->limit($count)
        ->get();

    if ($migrations->isEmpty()) {
        return info("Nothing to rollback.");
    }

    echo "\n  \033[44;97m RUNNING \033[0m Rolling back migrations...\n\n";

    foreach ($migrations as $migration) {
        try {
            $file = __DIR__ . "/App/Database/Migrations/{$migration->migration_file}";

            if (!file_exists($file)) {
                echo "  \033[33m⚠\033[0m  Migration file not found: {$migration->migration_file}\n";
                continue;
            }

            require_once $file;
            $basename = basename($file, '.php');
            $parts = explode('_', $basename);
            $timestamp = implode('_', array_slice($parts, 0, 4));
            $name = implode('_', array_slice($parts, 4));
            $className = "App\\Database\\Migrations\\Migration_{$timestamp}_" . str_replace('_', '', ucwords($name, '_'));

            $migrationInstance = new $className();
            $migrationInstance->down();

            Capsule::table('migration_history')->where('migration_file', basename($file))->delete();

            echo "  \033[33m⟲\033[0m {$migration->migration_file}\n";
        } catch (Throwable $e) {
            fail("Rollback failed: {$migration->migration_file}\n  " . $e->getMessage());
        }
    }

    echo "\n";
}

function undoSpecificMigration($filename)
{
    if (!$filename) {
        fail("Please provide a migration filename.\n  Usage: php cli.php migration:undo <migration_filename>");
        return;
    }

    require_once __DIR__ . '/App/Database/EloquentBootstrap.php';
    \App\Database\EloquentBootstrap::init();

    if (!Capsule::schema()->hasTable('migration_history')) {
        return info("No migrations have been run yet.");
    }

    // Normalizar el nombre del archivo (con o sin extensión .php)
    $filename = str_replace('.php', '', $filename);
    $filename = basename($filename);

    // Buscar la migración en el historial
    $migration = Capsule::table('migration_history')
        ->where('migration_file', $filename . '.php')
        ->orWhere('migration_file', $filename)
        ->first();

    if (!$migration) {
        fail("Migration '{$filename}' not found in migration history.\n  Use 'migration:status' to see applied migrations.");
        return;
    }

    $migrationFile = $migration->migration_file;
    $file = __DIR__ . "/App/Database/Migrations/{$migrationFile}";

    if (!file_exists($file)) {
        fail("Migration file not found: {$migrationFile}");
        return;
    }

    echo "\n  \033[44;97m UNDOING \033[0m Rolling back specific migration...\n\n";

    try {
        require_once $file;
        
        // Construir el nombre de la clase
        $basename = basename($file, '.php');
        $parts = explode('_', $basename);
        $timestamp = implode('_', array_slice($parts, 0, 4));
        $name = implode('_', array_slice($parts, 4));
        $className = "App\\Database\\Migrations\\Migration_{$timestamp}_" . str_replace('_', '', ucwords($name, '_'));

        // Verificar que la clase existe
        if (!class_exists($className)) {
            fail("Migration class '{$className}' not found in {$migrationFile}");
            return;
        }

        // Ejecutar el método down()
        $migrationInstance = new $className();
        
        if (!method_exists($migrationInstance, 'down')) {
            fail("Migration '{$migrationFile}' does not have a down() method.");
            return;
        }

        $migrationInstance->down();

        // Eliminar del historial
        Capsule::table('migration_history')
            ->where('migration_file', $migrationFile)
            ->delete();

        echo "  \033[32m✓\033[0m {$migrationFile} \033[90m(rolled back)\033[0m\n";
        echo "\n  \033[42;97m SUCCESS \033[0m Migration undone successfully!\n\n";

    } catch (Throwable $e) {
        fail("Failed to undo migration: {$migrationFile}\n  " . $e->getMessage() . "\n  File: " . $e->getFile() . " (Line " . $e->getLine() . ")");
    }
}

function listMigrations()
{
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/App/Database/EloquentBootstrap.php';
    \App\Database\EloquentBootstrap::init();

    $files = glob(__DIR__ . '/App/Database/Migrations/*.php');
    sort($files);

    if (empty($files)) {
        return info("No migrations found.");
    }

    if (!Capsule::schema()->hasTable('migration_history')) {
      foreach ($files as $file) {
        $migrationFile = basename($file);
        echo "[ ] $migrationFile\n";
      }
      return;
    }

    $appliedMigrations = Capsule::table('migration_history')->pluck('migration_file')->toArray();

    echo "\n  \033[44;97m RUNNING \033[0m Migration status:\n\n";

    $allApplied = true;
    foreach ($files as $file) {
      $migrationFile = basename($file);
      $isApplied = in_array($migrationFile, $appliedMigrations);
      if (!$isApplied) {
        $allApplied = false;
        break;
      }
    }
    if ($allApplied) {
      done("All migrations are up to date. No pending migrations.");
      return;
    }
    foreach ($files as $file) {
      $migrationFile = basename($file);
      $isApplied = in_array($migrationFile, $appliedMigrations);
      $status = $isApplied
        ? "\033[42;97m YES \033[0m"
        : "\033[41;97m NO \033[0m";
      echo "$status $migrationFile\n";
    }
}

function runSeeders()
{
    require_once __DIR__ . '/App/Database/EloquentBootstrap.php';
    \App\Database\EloquentBootstrap::init();

    $files = glob(__DIR__ . '/App/Database/Seeders/*Seeder.php');

    if (empty($files)) {
        return info("No seeders found.");
    }

    echo "\n  \033[44;97m RUNNING \033[0m Seeding database...\n\n";

    foreach ($files as $file) {
        require_once $file;
        $basename = basename($file, '.php');
        $className = "App\\Database\\Seeders\\{$basename}";

        try {
            $seeder = new $className();
            $seeder->run();
            echo "  \033[32m✓\033[0m {$basename}\n";
        } catch (Exception $e) {
            echo "  \033[31m✘\033[0m Seeding failed: {$basename}\n";
            echo "    " . $e->getMessage() . "\n";
        }
    }

    echo "\n";
}

function fail($msg)
{
    echo "\n  \033[41;97m ERROR \033[0m \033[91m{$msg}\033[0m\n\n";
    exit(1);
}

function done($msg)
{
    echo "\n  \033[42;97m DONE \033[0m {$msg}\n\n";
}

function info($msg)
{
    echo "\n  \033[46;97m INFO \033[0m {$msg}\n\n";
}

function error($msg)
{
    echo "\n  \033[41;97m ERROR \033[0m \033[91m{$msg}\033[0m\n\n";
}

function truncate($string, $length)
{
    if (strlen($string) <= $length) {
        return $string;
    }
    return substr($string, 0, $length - 3) . '...';
}