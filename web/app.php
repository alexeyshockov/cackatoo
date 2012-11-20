<?php

use Symfony\Component\HttpFoundation\Request;

if (extension_loaded('newrelic')) {
    newrelic_set_appname(getenv('NEWRELIC_APP_NAME') ?: 'Cackatoo');
    newrelic_name_transaction($_SERVER['REQUEST_URI']);
}

$loader = require_once __DIR__.'/../app/autoload.php';

$environment = getenv('SYMFONY_ENV') ?: 'prod';

require_once __DIR__.'/../app/AppKernel.php';

$kernel = new AppKernel($environment);
$kernel->loadClassCache();

$request  = Request::createFromGlobals();
$response = $kernel->handle($request);

$response->send();

$kernel->terminate($request, $response);
