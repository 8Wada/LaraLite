<?php
use App\Utils\Autoloader;

/**
 * Función helper global para mantener compatibilidad
 */
function autoload(string $path, array $files): void
{
    Autoloader::loadFiles($path, $files);
}

/**
 * Función helper global para mantener compatibilidad
 */
function autoloadDirectory(string $path): void
{
    Autoloader::loadDirectory($path);
}

/**
 * Función helper global para respuestas HTTP
 */
function responseRequest(bool $isError, string $message, bool $finishConnection, int $code, array $data = []): void
{
    header('Content-Type: application/json');
    http_response_code($code);
    echo json_encode(["error" => $isError, "message" => $message, "data" => $data]);
    if ($finishConnection) {
        die();
    }
}