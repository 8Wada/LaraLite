<?php
// ----[Debug Mode Configuration]----
$debugMode = filter_var(
    getenv('DEBUG_MODE') !== false ? getenv('DEBUG_MODE') : false,
    FILTER_VALIDATE_BOOLEAN
);
define('DEBUG_MODE', $debugMode);

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
// ----[CORS Configuration]----
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, Access-Control-Allow-Credentials");

// ----[Allowed Origins]----
$allowed_origins = [
  'http://localhost:5173',
  'http://localhost:5174',
  'http://localhost:3000',
  'http://localhost:3001',
  'https://pre-datacenter.ucol.mx',
  'https://datacenter.ucol.mx',
];

// ----[Set Access-Control-Allow-Origin]----
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowed_origins)) {
  header("Access-Control-Allow-Origin: " . $origin);
}

// Permitir preflight
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  die();
}
