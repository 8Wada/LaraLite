<?php 
//mostrar errores
$saml_lib_path = '/var/www/simplesaml/src/_autoload.php';
// echo __DIR__ . $saml_lib_path;
require_once($saml_lib_path);

// Fuente de autenticacion definida en el authsources del SP ej, default-sp
$SP_ORIGEN = getenv('SOURCE');

// Se crea la instancia del saml, pasando como parametro la fuente de autenticacion.
$as = new \SimpleSAML\Auth\Simple($SP_ORIGEN);
$as->requireAuth();
$attributes = $as->getAttributes();


//Ruta actual de SAML
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
echo "URL actual: " . $actual_link . "<br><br>";

echo "Atributos del usuario autenticado:<br>";

//obtener cada atributo con foreach
foreach ($attributes as $key => $value) {
    echo $key . " => " . $value[0] . "<br>";
}

//Mostrar el valor de todas las variables de sesion
echo "<br>Variables de sesion:<br>";
session_start();
foreach ($_SESSION as $key => $value) {
    echo $key . " => " . $value . "<br>";
}
session_write_close();

//Mostrar las getenvs
echo "<br>Variables de entorno (getenv):<br>";
foreach ($_ENV as $key => $value) {
    echo $key . " => " . $value . "<br>";
}
?>