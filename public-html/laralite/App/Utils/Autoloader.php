<?php

namespace App\Utils;

/**
 * Autoload utility functions for loading PHP files.
 * This class provides methods to autoload specific files or all files in a directory.
 */
class Autoloader
{
    private static array $namespacePaths = [];

    /**
     * Registra el autoloader PSR-4 para el namespace App\
     */
    public static function register(): void
    {
        self::discoverNamespaces();
        spl_autoload_register([self::class, 'loadClass']);
    }

    /**
     * Autodescubre namespaces basándose en la estructura de carpetas
     */
    private static function discoverNamespaces(): void
    {
        $rootPath = dirname(__DIR__, 2);
        $directories = glob($rootPath . '/*', GLOB_ONLYDIR);

        foreach ($directories as $dir) {
            $dirName = basename($dir);
            
            // Saltar carpetas del sistema
            if (in_array($dirName, ['vendor', 'node_modules', '.git', 'public', 'storage', 'cache', 'config'])) {
                continue;
            }

            // Crear namespace basado en el nombre de la carpeta
            $namespace = ucfirst($dirName) . '\\';
            self::$namespacePaths[$namespace] = $dir . '/';
        }
    }

    /**
     * Autoloader PSR-4 dinámico
     */
    private static function loadClass(string $class): bool
    {
        foreach (self::$namespacePaths as $namespace => $basePath) {
            if (strpos($class, $namespace) === 0) {
                $classPath = str_replace('\\', '/', $class);
                $classPath = substr($classPath, strlen($namespace));
                $file = $basePath . $classPath . '.php';

                if (file_exists($file)) {
                    require_once $file;
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Loads specific files from a given path.
     * 
     * @param string $path Ruta del directorio
     * @param array $files Array de nombres de archivos
     * 
     * Usage: Autoloader::loadFiles('path/to/directory', ['file1.php', 'file2.php']);
     */
    public static function loadFiles(string $path, array $files): void
    {
        foreach ($files as $file) {
            $fullPath = "{$path}/{$file}";
            if (file_exists($fullPath)) {
                require_once $fullPath;
            }
        }
    }

    /**
     * Carga automáticamente todos los archivos de un directorio.
     * Útil para cargar controladores, modelos, etc.
     *
     * @param string $path Ruta del directorio a cargar
     * 
     * Usage: Autoloader::loadDirectory('path/to/directory');
     */
    public static function loadDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $files = scandir($path);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullPath = "{$path}/{$file}";

            // Solo cargar archivos .php
            if (is_file($fullPath) && pathinfo($fullPath, PATHINFO_EXTENSION) === 'php') {
                // Cargar solo si no ha sido incluido antes
                require_once $fullPath;
            }
        }
    }
}