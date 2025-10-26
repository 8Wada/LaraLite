<?php

namespace App\Utils;

class ResponseHelper
{
    /**
     * Respuesta genérica
     */
    public static function responseRequest(bool $isError, string $message, int $code, array $data = [], array $debug = []): void
    {
        header('Content-Type: application/json');
        http_response_code($code);
        
        $response = [
            "error" => $isError, 
            "message" => $message, 
            "data" => $data
        ];

        // Agregar información de debug solo si DEBUG_MODE está activo y hay información de debug
        if (defined('DEBUG_MODE') && DEBUG_MODE && !empty($debug)) {
            $response['_debug'] = $debug;
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        // Finalizar la conexión si es necesario
        exit();
    }

    /**
     * Respuesta exitosa
     */
    public static function successResponse(string $message, array $data = []): void
    {
        self::responseRequest(false, $message,  200, $data);
    }

    /**
     * Respuesta de creación exitosa
     */
    public static function createdResponse(string $entity, array $data = []): void
    {
        self::responseRequest(false, "$entity created",  201, $data);
    }

    /**
     * Respuesta de solicitud incorrecta
     */
    public static function badRequestResponse(string $message = 'Bad request', array $debug = []): void
    {
        $finalMessage = defined('DEBUG_MODE') && DEBUG_MODE ? $message : 'Solicitud incorrecta';
        self::responseRequest( true,   $finalMessage,   400, [], $debug);
    }

    /**
     * Respuesta de no autorizado
     */
    public static function unauthorizedResponse(string $entity = ""): void
    {
        $message = $entity ? "$entity, unauthorized" : "Unauthorized";
        self::responseRequest( true,   $message,   401);
    }

    /**
     * Respuesta de recurso no encontrado
     */
    public static function notFoundResponse(string $entity): void
    {
        self::responseRequest( true,  "$entity not found",   404);
    }

    /**
     * Respuesta de conflicto (recurso ya existe)
     */
    public static function conflictResponse(string $entity = "Record"): void
    {
        self::responseRequest( true,  "$entity already exists",   409);
    }

    /**
     * Respuesta de error en operación
     * En modo producción y desarrollo muestra un mensaje genérico y limpio
     * Los detalles técnicos se envían en _debug cuando DEBUG_MODE está activo
     */
    public static function operationErrorResponse(string $context = 'Operation error', \Throwable $exception = null): void
    {
        $debug = [];
        
        // Si hay una excepción, extraer información de debug
        if ($exception !== null) {
            $debug = self::buildDebugInfo($exception);
        }

        // Mensaje genérico para el usuario (sin información técnica)
        $userMessage = "Error en la operación: {$context}";

        self::responseRequest( true, $userMessage,   500, [], $debug);
    }

    /**
     * Construir información de debug desde una excepción
     * Esta información solo se incluye cuando DEBUG_MODE está activo
     */
    private static function buildDebugInfo(\Throwable $exception): array
    {
        $debug = [
            'type' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ];

        // Intentar extraer SQL de excepciones de base de datos
        // QueryException de Illuminate tiene una propiedad sql
        if ($exception instanceof \Illuminate\Database\QueryException) {
            $reflection = new \ReflectionClass($exception);
            
            // Intentar obtener la propiedad sql
            if ($reflection->hasProperty('sql')) {
                $sqlProperty = $reflection->getProperty('sql');
                $sqlProperty->setAccessible(true);
                $debug['sql'] = $sqlProperty->getValue($exception);
            }
            
            // Intentar obtener los bindings
            if ($reflection->hasProperty('bindings')) {
                $bindingsProperty = $reflection->getProperty('bindings');
                $bindingsProperty->setAccessible(true);
                $debug['bindings'] = $bindingsProperty->getValue($exception);
            }
        }

        // Agregar el trace solo en modo debug (limitado para no saturar la respuesta)
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            $debug['trace'] = array_slice($exception->getTrace(), 0, 5); // Solo primeras 5 líneas del trace
        }

        return $debug;
    }

    /**
     * Respuesta de error de base de datos
     * Mensaje principal siempre genérico (sin SQL, tablas o información técnica)
     * Los detalles técnicos se envían en _debug solo cuando DEBUG_MODE está activo
     */
    public static function databaseErrorResponse(string $context = 'en la base de datos', \Throwable $exception = null): void
    {
        $debug = [];
        
        if ($exception !== null) {
            $debug = self::buildDebugInfo($exception);
            
            // Intentar extraer más información del mensaje de error SQL
            $errorMessage = $exception->getMessage();
            
            // Detectar tipo de error común y agregar al debug
            if (strpos($errorMessage, "doesn't exist") !== false) {
                $debug['error_type'] = 'TABLE_NOT_FOUND';
                $debug['suggestion'] = 'Verificar que las migraciones se hayan ejecutado correctamente';
            } elseif (strpos($errorMessage, 'Duplicate entry') !== false) {
                $debug['error_type'] = 'DUPLICATE_ENTRY';
                $debug['suggestion'] = 'El registro ya existe en la base de datos';
            } elseif (strpos($errorMessage, 'foreign key constraint') !== false) {
                $debug['error_type'] = 'FOREIGN_KEY_CONSTRAINT';
                $debug['suggestion'] = 'Verificar las relaciones entre tablas';
            } elseif (strpos($errorMessage, 'Connection refused') !== false || strpos($errorMessage, 'Access denied') !== false) {
                $debug['error_type'] = 'CONNECTION_ERROR';
                $debug['suggestion'] = 'Verificar la conexión y credenciales de la base de datos';
            } else {
                $debug['error_type'] = 'SQL_ERROR';
                $debug['suggestion'] = 'Error en la consulta SQL';
            }
        }

        // Mensaje genérico y limpio para el usuario (SIN información técnica, SQL o nombres de tablas)
        $userMessage = "Error {$context}";

        self::responseRequest(  true, $userMessage,   500, [], $debug);
    }

    /**
     * Respuesta de error de validación
     */
    public static function validationErrorResponse(array $errors): void
    {
        $message = 'Error de validación';
        self::responseRequest(  true, $message,  422, ['errors' => $errors]);
    }
}
