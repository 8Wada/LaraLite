<?php
	$envSource=getenv("SOURCE");
	$envSimple=getenv("SIMPLE");
	$envServer=$_SERVER["SERVER_NAME"];
    $config = array(
    // This is a authentication source which handles admin authentication.
    'admin' => [
        // The default is to use core:AdminPassword, but it can be replaced with
        // any authentication source.

        'core:AdminPassword',
    ],
        //auth edocencia
         $envSource => array(
           'saml:SP',
           'host' => $envServer,
           'entityID' => "https://$envServer/$envSimple/",
           'idp' => 'http://wayf.ucol.mx',


            'certificate' => 'localhost.crt',
            'privatekey' => 'localhost.key',

            // All communications are encrypted and signed
            'redirect.sign' => TRUE,
            'redirect.validate' => TRUE,
            'assertion.encryption' => TRUE,
            'name'=>$envSource,
        ),


);