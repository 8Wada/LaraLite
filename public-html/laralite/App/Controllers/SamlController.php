<?php

namespace App\Controllers;

use App\Controllers\Controller;
use App\Utils\ResponseHelper;
use SimpleSAML\Auth\Simple;

class SamlController extends Controller
{
  private $simple_lib_path = '/var/www/simplesaml/src/_autoload.php';

  /**
   * Initialize and return SimpleSAML Auth instance.
   * @return Simple
   */
  private function getAuthInstance()
  {
    require_once $this->simple_lib_path;
    $source_sp = getenv('SOURCE');
    return new Simple($source_sp);
  }

  /**
   * Handle SAML authentication and return user attributes.
   * @return \App\Utils\ResponseHelper::ResponseRequest
   */
  public function index()
  {
    try {
      $auth = $this->getAuthInstance();
      
      if (!$auth->isAuthenticated()) {
        $auth->requireAuth();
      } else {
        $as = $auth->getAttributes();
        //Format $as to simple key-value pairs
        $formattedAs = [];
        foreach ($as as $key => $value) {
          $formattedAs[$key] = $value[0];
        }

        return ResponseHelper::successResponse('SAML Attributes retrieved successfully.', $formattedAs);
      }
    } catch (\Exception $e) {
      return ResponseHelper::operationErrorResponse('SAML Authentication failed: ' . $e->getMessage());
    }
  }

  /**
   * Handle SAML login.
   * @return \App\Utils\ResponseHelper::ResponseRequest
   */
  public function login()
  {
    try {
      $auth = $this->getAuthInstance();
      
      if (!$auth->isAuthenticated()) {
        $auth->requireAuth();
      } else {
        $as = $auth->getAttributes();
        return ResponseHelper::successResponse('User authenticated successfully.', $as);
      }
    } catch (\Exception $e) {
      return ResponseHelper::operationErrorResponse('SAML Login failed: ' . $e->getMessage());
    }
  }

  /**
   * Handle SAML logout.
   * @return \App\Utils\ResponseHelper::ResponseRequest
   */
  public function logout()
  {
    try {
      $auth = $this->getAuthInstance();
      
      if ($auth->isAuthenticated()) {
        $auth->logout();
        return ResponseHelper::successResponse('User logged out successfully.');
      } else {
        return ResponseHelper::successResponse('User is not authenticated.');
      }
    } catch (\Exception $e) {
      return ResponseHelper::operationErrorResponse('SAML Logout failed: ' . $e->getMessage());
    }
  }
}