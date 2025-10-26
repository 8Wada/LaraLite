<?php

namespace App\Controllers;

use App\Utils\ResponseHelper;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\QueryException;

class Controller
{
  // ============ Propiedades ============
  protected Capsule $DB;
  protected string $moduleUuid = "3049381E-9453-4440-BFD3-FB49548891B9";

  public function __construct(Capsule $DB)
  {
    $this->DB = $DB;
  }

  // ==========================================================================
  // ============ MÉTODOS DE AUTENTICACIÓN Y VALIDACIÓN DE SESIÓN ============
  // ==========================================================================

  protected function validateSession(): void
  {
    $simulateSessionData = [
      "uuid" => "9AF09F45-ECC2-4556-8949-FC8E346D0D8B",
      "email" => "admin@carq.mx",
      "first_name" => "Admin",
      "last_name" => "Colegio",
      "role" => 0,
      "origin_uuid" => $this->moduleUuid,
      "services" => []
    ];

    $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
    $requestHost = $_SERVER['HTTP_HOST'] ?? '';

    if (
      in_array(parse_url($requestOrigin, PHP_URL_HOST), ['localhost', '127.0.0.1']) &&
      strpos($requestHost, 'qa.colegioarquitectos.mx') !== false
    ) {
      $_SESSION["auth_{$this->moduleUuid}"] ??= $simulateSessionData;
      return;
    }

    if (!isset($_SESSION["auth_{$this->moduleUuid}"])) {
      $this->notFoundResponse("Session");
    }

    if (empty($_SESSION["auth_{$this->moduleUuid}"]["email"])) {
      $this->unauthorizedResponse("Email not found in session");
    }
  }

  protected function validateUserLogged(): bool
  {
    $this->validateSession();

    $email = $_SESSION["auth_{$this->moduleUuid}"]["email"] ?? '';
    if (empty($email)) {
      $this->unauthorizedResponse("Invalid email in session");
    }

    $user = $this->getRecordFromDb("ca_auth_users", "email", $email, ["rol" => 1]);
    if (!$user) {
      $this->notFoundResponse("User");
    }

    return true;
  }

  protected function validateUserRol(array $roles): void
  {
    $this->validateSession();
    $email = $_SESSION["auth_{$this->moduleUuid}"]["email"];

    foreach ($roles as $rol) {
      $user = $this->getRecordFromDb("ca_auth_users", "email", $email, ["rol" => $rol]);
      if ($user) return;
    }

    $this->unauthorizedResponse("Invalid user role");
  }

  protected function getLoggedUser(): array
  {
    $email = $_SESSION["auth_{$this->moduleUuid}"]["email"];
    return $this->getRecordFromDb("ca_auth_users", "email", $email, ["rol" => 1]) ?? [];
  }

  public function validateUuid($req): bool
  {
    return !empty($req->params->crud_uuid) && $req->params->crud_uuid !== '1';
  }

  // ==========================================================================
  // ===================== MÉTODOS DE BASE DE DATOS ============================
  // ==========================================================================

  protected function getRecordFromDb(string $table, string $key, string $value, array $where = []): ?array
  {
    try {
      $query = $this->DB->table($table)->where($key, $value)->where('status', '!=', 0);
      foreach ($where as $field => $val) $query->where($field, $val);
      $result = $query->first();
      return $result ? (array) $result : null;
    } catch (QueryException $e) {
      error_log("Error in getRecordFromDb: " . $e->getMessage());
      return null;
    }
  }

  protected function isExistRecord(string $table, string $column, string $value, array $where = []): bool
  {
    try {
      $query = $this->DB->table($table)->where($column, $value)->where('status', '!=', 0);
      foreach ($where as $field => $val) $query->where($field, $val);
      return $query->count() > 0;
    } catch (QueryException $e) {
      error_log("Error in isExistRecord: " . $e->getMessage());
      return false;
    }
  }

  protected function isDeletedRecord(string $table, string $key, string $value): bool
  {
    try {
      return $this->DB->table($table)->where($key, $value)->where('status', 0)->count() > 0;
    } catch (QueryException $e) {
      error_log("Error in isDeletedRecord: " . $e->getMessage());
      return false;
    }
  }

  // ==========================================================================
  // ======================== MÉTODOS DE VALIDACIÓN ============================
  // ==========================================================================

  protected function validateRequiredFields(array $fields): void
  {
    foreach ($fields as $field) {
      if (empty($field)) {
        $this->badRequestResponse("Missing or invalid fields");
      }
    }
  }

  protected function validateEmptyBody(object $body, array $schema): bool
  {
    foreach ($schema as $field) {
      if (!property_exists($body, $field)) return false;
      $value = $body->$field;
      if (empty($value) && $value !== 0) return false;
    }
    return true;
  }

  protected function validateRecordExists(string $table, string $column, string $value): void
  {
    if (!$this->isExistRecord($table, $column, $value)) {
      $this->notFoundResponse("Record in $table");
    }
  }

  protected function validateDBResponse($res, string $errorMessage): void
  {
    if ($res['status'] === 'error' || empty($res['data'][0])) {
      throw new \Exception($errorMessage);
    }
  }

  // ==========================================================================
  // ======================== UTILIDADES GENERALES ============================
  // ==========================================================================

  public static function generateUuid(): string
  {
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
  }

  protected function sanitizeValue($value): string
  {
    return htmlspecialchars(is_scalar($value) ? (string) $value : json_encode($value), ENT_QUOTES, 'UTF-8');
  }

  protected function formatDateFields(array $data, array $dateFields, bool $onlyDate = false): array
  {
    return array_map(function ($record) use ($dateFields, $onlyDate) {
      foreach ($dateFields as $field) {
        if (isset($record[$field])) {
          $timestamp = strtotime($record[$field]);
          if ($timestamp !== false) {
            $format = $onlyDate ? 'Y-m-d' : 'Y-m-d H:i:s';
            $record[$field] = date($format, $timestamp);
          }
        }
      }
      return $record;
    }, $data);
  }

  protected function validate(array $data, array $rules, array $messages): array
  {
    $errors = [];

    foreach ($rules as $field => $ruleString) {
      $rulesArr = explode('|', $ruleString);
      $value = $data[$field] ?? null;
      $fieldExists = array_key_exists($field, $data);

      foreach ($rulesArr as $rule) {
        // Validación required
        if ($rule === 'required' && ($value === null || $value === '')) {
          $errors[$field][] = $messages["{$field}.required"] ?? 'Este campo es obligatorio.';
          continue; // Si es required y falla, no validar el resto
        }

        // Si el campo no existe y no es required, saltamos las demás validaciones
        if (!$fieldExists && $rule !== 'required') {
          continue;
        }

        // Si el valor es null o vacío y no es required, saltamos las demás validaciones
        if (($value === null || $value === '') && $rule !== 'required') {
          continue;
        }

        // Validación string
        if ($rule === 'string' && !is_string($value)) {
          $errors[$field][] = $messages["{$field}.string"] ?? 'Debe ser una cadena.';
        }

        // Validación integer
        if ($rule === 'integer' && !filter_var($value, FILTER_VALIDATE_INT)) {
          $errors[$field][] = $messages["{$field}.integer"] ?? 'Debe ser un número entero.';
        }

        // Validación numeric
        if ($rule === 'numeric' && !is_numeric($value)) {
          $errors[$field][] = $messages["{$field}.numeric"] ?? 'Debe ser numérico.';
        }

        // Validación boolean
        if ($rule === 'boolean' && !in_array($value, [true, false, 0, 1, '0', '1'], true)) {
          $errors[$field][] = $messages["{$field}.boolean"] ?? 'Debe ser booleano.';
        }

        // Validación max (para strings e números)
        if (str_starts_with($rule, 'max:')) {
          $max = (int) explode(':', $rule)[1];
          if (is_string($value) && strlen($value) > $max) {
            $errors[$field][] = $messages["{$field}.max"] ?? "No debe exceder $max caracteres.";
          } elseif (is_numeric($value) && $value > $max) {
            $errors[$field][] = $messages["{$field}.max"] ?? "No debe ser mayor a $max.";
          }
        }

        // Validación min (CORREGIDA para strings y números)
        if (str_starts_with($rule, 'min:')) {
          $min = (int) explode(':', $rule)[1];
          if (is_string($value) && strlen($value) < $min) {
            $errors[$field][] = $messages["{$field}.min"] ?? "Debe tener al menos $min caracteres.";
          } elseif (is_numeric($value) && $value < $min) {
            $errors[$field][] = $messages["{$field}.min"] ?? "Debe ser al menos $min.";
          }
        }
      }
    }

    return $errors;
  }

  // ==========================================================================
  // ======================== RESPUESTAS HTTP ESTÁNDAR ========================
  // ==========================================================================

  protected function successResponse(string $message, array $data = []): void
  {
    ResponseHelper::ResponseRequest(false, $message, true, 200, $data);
  }

  protected function createdResponse(string $entity, array $data = []): void
  {
    ResponseHelper::ResponseRequest(false, "$entity created", true, 201, $data);
  }

  protected function validationFailedResponse(array $errors): void
  {
    ResponseHelper::ResponseRequest(true, "Validation failed", true, 422, ['validationErrors' => $errors]);
  }

  protected function badRequestResponse(string $message = "Bad request", array $data = []): void
  {
    ResponseHelper::ResponseRequest(true, $message, true, 400, $data);
  }

  protected function unauthorizedResponse(string $message = "Unauthorized"): void
  {
    ResponseHelper::ResponseRequest(true, $message, true, 401);
    exit;
  }

  protected function notFoundResponse(string $entity = "Resource not found"): void
  {
    ResponseHelper::ResponseRequest(true, "$entity", true, 200);
  }

  protected function notFoundResourceResponse(string $entity = "Resource"): void
  {
    ResponseHelper::ResponseRequest(true, "$entity not found", true, 404);
  }

  protected function noContentResponse(string $entity = "Record"): void
  {
    ResponseHelper::ResponseRequest(false, "$entity not found", true, 204);
  }

  protected function forbiddenResponse(string $entity = "Record"): void
  {
    ResponseHelper::ResponseRequest(true, "$entity forbidden", true, 403);
  }

  protected function conflictResponse(string $entity = "Record"): void
  {
    ResponseHelper::ResponseRequest(true, "$entity already exists", true, 409);
  }

  protected function operationErrorResponse(string $message = "Operation error", \Throwable $exception = null): void
  {
    ResponseHelper::operationErrorResponse($message, $exception);
  }

  protected function databaseErrorResponse(string $context = "database operation", \Throwable $exception = null): void
  {
    ResponseHelper::databaseErrorResponse($context, $exception);
  }
}
