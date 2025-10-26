<?php

namespace App\Router;

/**
 * Clase Router
 * Maneja las rutas de la API, permitiendo definir y ejecutar rutas para diferentes métodos HTTP.
 * Incluye soporte para middlewares, parámetros en las rutas, grupos, y un sistema de versión.
 * Soporta tanto callbacks como arrays [Clase::class, 'metodo']
 * Soporta parámetros en formato :id, {id} y {id:regex}
 */
class Router
{
  private array $routes = [];
  private string $version = 'v1';
  private bool $useVersion = true;
  private string $basePath = '';
  private array $params = [];
  private array $paramPatterns = [];
  private bool $routeFound = false;
  private $body;
  private string $errorStr = 'API not found';
  private int $errorCode = 404;
  private bool $hasAccess = true;
  
  // Propiedades para grupos
  private string $groupPrefix = '';
  private array $groupMiddlewares = [];

  public function __construct()
  {
    $this->routeFound = false;
  }

  /**
   * Configura la versión de las rutas
   */
  public function setRouteVersion(string $version = 'v1'): void
  {
    $this->version = $version;
  }

  /**
   * Activa o desactiva el uso de versión en las rutas
   */
  public function setUseVersion(bool $useVersion): void
  {
    $this->useVersion = $useVersion;
  }

  /**
   * Configura el path base para todas las rutas
   */
  public function setBasePath(string $basePath = ''): void
  {
    if (!empty($basePath) && !str_starts_with($basePath, '/')) {
      $basePath = '/' . $basePath;
    }
    $this->basePath = $basePath;
  }

  /**
   * Obtiene el path base actual
   */
  public function getBasePath(): string
  {
    return $this->basePath;
  }

  /**
   * Limpia el path base
   */
  public function clearBasePath(): void
  {
    $this->basePath = '';
  }

  /**
   * Valida los middlewares y determina si se tiene acceso
   */
  private function middlewares(array $middlewares): bool
  {
    if (count($middlewares) == 0) {
      return true;
    }
    
    $access = array_search(true, $middlewares);
    
    if ($access === false) {
      $this->hasAccess = false;
      $this->errorStr = 'Usuario sin acceso';
      $this->errorCode = 403;
    }
    
    return $access !== false;
  }

  /**
   * Ejecuta el callback, ya sea función anónima o array [Clase::class, 'método']
   */
  private function executeCallback($callback, object $req)
  {
    if (is_array($callback) && count($callback) === 2) {
      [$className, $methodName] = $callback;

      global $DB;
      $instance = new $className($DB);

      return $instance->$methodName($req);
    }
    
    return $callback($req);
  }

  /**
   * Normaliza los parámetros de ruta para soportar :param, {param} y {param:regex}
   */
  private function normalizeRouteParams(string $route): string
  {
    // Guardar patrones regex personalizados {param:regex}
    $route = preg_replace_callback(
      '/\{([a-zA-Z_][a-zA-Z0-9_]*):([^}]+)\}/',
      function ($matches) {
        $this->paramPatterns[$matches[1]] = $matches[2];
        return ':' . $matches[1];
      },
      $route
    );
    
    // Convertir {param} a :param
    return preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', ':$1', $route);
  }

  /**
   * Verifica si un segmento de URL es un parámetro
   */
  private function isParameter(string $segment): bool
  {
    return str_starts_with($segment, ':') || 
           (str_starts_with($segment, '{') && str_ends_with($segment, '}'));
  }

  /**
   * Extrae el nombre del parámetro de un segmento
   */
  private function getParameterName(string $segment): ?string
  {
    if (str_starts_with($segment, ':')) {
      return substr($segment, 1);
    }
    
    if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
      return substr($segment, 1, -1);
    }
    
    return null;
  }

  /**
   * Define un grupo de rutas con prefijo y middlewares compartidos
   * @param array $attributes ['prefix' => '/admin', 'middleware' => [...]]
   * @param callable $callback
   */
  public function group(array $attributes, callable $callback): void
  {
    $prevPrefix = $this->groupPrefix;
    $prevMiddlewares = $this->groupMiddlewares;

    if (isset($attributes['prefix'])) {
      $this->groupPrefix .= '/' . trim($attributes['prefix'], '/');
    }

    if (isset($attributes['middleware'])) {
      $this->groupMiddlewares = array_merge(
        $this->groupMiddlewares,
        (array) $attributes['middleware']
      );
    }

    $callback($this);

    $this->groupPrefix = $prevPrefix;
    $this->groupMiddlewares = $prevMiddlewares;
  }

  /**
   * Método base para registrar rutas HTTP (DRY)
   */
  private function registerRoute(string $method, string $URL, $callback, array $middlewares = []): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
      return;
    }

    $allMiddlewares = array_merge($this->groupMiddlewares, $middlewares);

    if (!$this->middlewares($allMiddlewares)) {
      return;
    }

    $routeIndex = $this->setRoute($URL, $callback);
    
    if ($this->executeThisURL($routeIndex)) {
      $req = $this->buildRequest($method);
      $this->executeCallback($callback, $req);
    }
  }

  /**
   * Construye el objeto request con toda la información necesaria
   */
  private function buildRequest(string $method): object
  {
    $req = [
      'params' => (object) $this->params,
      'query' => (object) $_GET,
      'method' => $method,
      'path' => $this->getCurrentURL(),
      'headers' => function_exists('getallheaders') ? getallheaders() : []
    ];

    // Agregar body para métodos que lo soportan
    if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'])) {
      $req['body'] = $this->body;
    }

    return (object) $req;
  }

  /**
   * Registra una ruta GET
   */
  public function get(string $URL, $callback, array $middlewares = []): void
  {
    $this->registerRoute('GET', $URL, $callback, $middlewares);
  }

  /**
   * Registra una ruta POST
   */
  public function post(string $URL, $callback, array $middlewares = []): void
  {
    $this->registerRoute('POST', $URL, $callback, $middlewares);
  }

  /**
   * Registra una ruta PUT
   */
  public function put(string $URL, $callback, array $middlewares = []): void
  {
    $this->registerRoute('PUT', $URL, $callback, $middlewares);
  }

  /**
   * Registra una ruta PATCH
   */
  public function patch(string $URL, $callback, array $middlewares = []): void
  {
    $this->registerRoute('PATCH', $URL, $callback, $middlewares);
  }

  /**
   * Registra una ruta DELETE
   */
  public function delete(string $URL, $callback, array $middlewares = []): void
  {
    $this->registerRoute('DELETE', $URL, $callback, $middlewares);
  }

  /**
   * Callback por defecto cuando no se encuentra una ruta
   */
  public function default($callback): void
  {
    if ($this->routeFound == false) {
      $callback(
        (object) [
          "message" => $this->errorStr,
          "statusCode" => $this->errorCode,
          "hasAccess" => $this->hasAccess
        ]
      );
    }
  }

  /**
   * Registra un recurso con rutas RESTful estándar
   * Crea automáticamente: index, show, store, update, destroy
   * 
   * @param string $path Ruta base del recurso (ej: '/centro-costos')
   * @param string $controller Clase del controlador
   * @param array $middlewares Middlewares opcionales para todas las rutas
   */
  public function crud(string $path, string $controller, array $middlewares = []): void
  {
    $this->get($path, [$controller, 'index'], $middlewares);
    $this->get($path . '/{id}', [$controller, 'show'], $middlewares);
    $this->post($path, [$controller, 'store'], $middlewares);
    $this->put($path . '/{id}', [$controller, 'update'], $middlewares);
    $this->delete($path . '/{id}', [$controller, 'destroy'], $middlewares);
  }

  /**
   * Ejecuta la URL actual
   */
  private function executeThisURL($routeIndex = null): bool
  {
    return $this->start();
  }

  /**
   * Registra una nueva ruta en el sistema
   */
  public function setRoute(string $routeName, $callback = null): int
  {
    $routeName = trim($routeName, '/');
    $routeName = $this->normalizeRouteParams($routeName);
    
    $fullRoute = $this->buildFullRoute($routeName);

    if ($this->existsRoute($fullRoute) !== false) {
      throw new \Error("This URL: $fullRoute already exists");
    }

    $routeData = (object) [
      "url" => $fullRoute,
      "callback" => $callback
    ];

    $this->routes[] = $routeData;

    return count($this->routes) - 1;
  }

  /**
   * Construye la ruta completa con basePath, versión y prefijo de grupo
   */
  private function buildFullRoute(string $routeName): string
  {
    $parts = array_filter([
      trim($this->basePath, '/'),
      $this->useVersion ? $this->version : null,
      trim($this->groupPrefix, '/'),
      $routeName
    ]);

    return implode('/', $parts);
  }

  /**
   * Verifica si una ruta ya existe
   */
  private function existsRoute(string $routeName)
  {
    return array_search($routeName, array_column($this->routes, 'url'));
  }

  /**
   * Verifica que todos los parámetros tengan valores válidos
   */
  private function hasSameParams(array $currentURLSplited, array $routeUrlSplited): bool
  {
    foreach ($routeUrlSplited as $key => $item) {
      if (!$this->isParameter($item)) {
        continue;
      }

      $valueParam = $currentURLSplited[$key] ?? '';
      
      if (empty($valueParam)) {
        return false;
      }

      // Validar contra regex personalizado si existe
      $paramName = $this->getParameterName($item);
      if (isset($this->paramPatterns[$paramName])) {
        $pattern = '#^' . $this->paramPatterns[$paramName] . '$#';
        if (!preg_match($pattern, $valueParam)) {
          return false;
        }
      }
    }
    
    return true;
  }

  /**
   * Verifica si dos URLs coinciden en estructura
   */
  private function isSameURLString(array $currentURLSplited, array $routeUrlSplited): bool
  {
    $normalizedCurrent = $currentURLSplited;
    
    foreach ($routeUrlSplited as $key => $item) {
      if ($this->isParameter($item)) {
        $normalizedCurrent[$key] = $item;
      }
    }
    
    return count(array_diff($normalizedCurrent, $routeUrlSplited)) == 0;
  }

  /**
   * Obtiene la URL actual desde la solicitud
   * Soporta tanto URLs limpias (?path=) como acceso directo (index.php/)
   */
  private function getCurrentURL(): string
  {
    // Prioridad 1: Parámetro 'path' del .htaccess
    if (isset($_GET['path']) && !empty($_GET['path'])) {
      return trim($_GET['path'], '/');
    }

    // Prioridad 2: REQUEST_URI
    $requestUri = $_SERVER['REQUEST_URI'] ?? '';
    
    if (empty($requestUri)) {
      return '';
    }

    // Remover query string
    if (($queryPos = strpos($requestUri, '?')) !== false) {
      $requestUri = substr($requestUri, 0, $queryPos);
    }

    // Obtener el directorio base del script
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
    $scriptDir = dirname($scriptName);
    
    // Normalizar el directorio base
    if ($scriptDir === '/' || $scriptDir === '\\') {
      $scriptDir = '';
    }

    // Remover el directorio base del REQUEST_URI
    if (!empty($scriptDir) && str_starts_with($requestUri, $scriptDir)) {
      $requestUri = substr($requestUri, strlen($scriptDir));
    }

    // Remover /index.php si está presente
    $requestUri = preg_replace('#^/index\.php/?#', '', $requestUri);

    return trim($requestUri, '/');
  }

  /**
   * Inicia el proceso de matching de rutas
   */
  public function start(): bool
  {
    $currentURL = $this->getCurrentURL();

    if (empty($currentURL)) {
      $this->routeFound = false;
      return false;
    }

    $urlSplited = explode('/', $currentURL);
    $sizeURL = count($urlSplited);

    foreach ($this->routes as $route) {
      $routeUrlSplited = explode('/', $route->url);
      $routeSize = count($routeUrlSplited);

      if ($sizeURL !== $routeSize) {
        continue;
      }

      if (!$this->hasSameParams($urlSplited, $routeUrlSplited)) {
        continue;
      }

      if (!$this->isSameURLString($urlSplited, $routeUrlSplited)) {
        continue;
      }

      // Extraer parámetros de la URL
      $this->extractParams($urlSplited, $routeUrlSplited);

      unset($_GET['path']);
      $this->params['extraParams'] = $_GET;
      $this->body = json_decode(file_get_contents('php://input'));
      $this->routeFound = true;
      
      return true;
    }

    $this->routeFound = false;
    return false;
  }

  /**
   * Extrae los parámetros de la URL actual
   */
  private function extractParams(array $urlSplited, array $routeUrlSplited): void
  {
    foreach ($routeUrlSplited as $key => $item) {
      if ($this->isParameter($item)) {
        $valueParam = $urlSplited[$key];
        $nameParam = $this->getParameterName($item);
        $this->params[$nameParam] = $valueParam;
      }
    }
  }

  /**
   * Método de debug para troubleshooting
   */
  public function debug(): array
  {
    return [
      'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'NO_SCRIPT_NAME',
      'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'NO_REQUEST_URI',
      'GET_path' => $_GET['path'] ?? 'NO_PATH',
      'URL_limpia' => $this->getCurrentURL(),
      'Rutas_registradas' => array_column($this->routes, 'url'),
      'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'] ?? 'NO_METHOD'
    ];
  }
}