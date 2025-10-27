<?php

namespace App\Middlewares;

use App\Utils\ResponseHelper;
use SimpleSAML\Auth\Simple;

class SamlMiddleware
{
  private $simple_lib_path = '/var/www/simplesaml/src/_autoload.php';

  /**
   * Verifica si el usuario está autenticado con SAML
   * @return bool
   */
  public function handle(): bool
  {
    try {
      require_once $this->simple_lib_path;
      $source_sp = getenv('SOURCE');
      $auth = new Simple($source_sp);

      // Si no está autenticado, redirige al IdP
      if (!$auth->isAuthenticated()) {
        $auth->requireAuth();
        return false;
      }

      return true;

    } catch (\Exception $e) {
      // En caso de error, denegar acceso
      error_log('SAML Middleware Error: ' . $e->getMessage());
      return false;
    }
  }
}