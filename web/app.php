<?php

use Symfony\Component\HttpFoundation\Request;

// Remove \Composer\Autoload\ClassLoader composer autoloader for Sf3.3: no need to use app/autoload.php file anymore!
require __DIR__.'/../vendor/autoload.php';

include_once __DIR__.'/../var/bootstrap.php.cache';

$kernel = new AppKernel('prod', false);
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
