<?php

use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../src/autoload.php';

$request = Request::createFromGlobals();
$env = $request->getHost() === 'localhost' ? 'dev' : 'prod';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../cfg/'.$env.'.php';
$app->boot();

$a = $app['assetic.factory']->createAsset('@jquery', 'jsmin');
$response = new \Symfony\Component\HttpFoundation\Response($a->dump());
$response->headers->set('Content-Type', 'text/javascript');
$response->send();