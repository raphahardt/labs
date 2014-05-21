<?php

use Symfony\Component\HttpFoundation\Request;

require __DIR__.'/../src/autoload.php';

$request = Request::createFromGlobals();
$env = $request->getHost() === 'localhost' ? 'dev' : 'prod';

$app = require __DIR__.'/../src/app.php';
require __DIR__.'/../cfg/'.$env.'.php';
$app->boot();

/* @var $a Assetic\Asset\AssetCollection */
/*$a = $app['assetic.factory']->createAsset(array('@bootstrap_css', '@blueimp_fileupload_css'), array(), array(
    'output'=> 'css/*.css', 'combine' => true, 'debug' => false
));
foreach ($a as $b) {
    //echo '=============================================================================';
    //echo $b->dump();
}*/

var_dump($app['assetic.lazy_asset_manager']->getNames());
foreach ($app['assetic.lazy_asset_manager']->getNames() as $name) {
    $a = $app['assetic.lazy_asset_manager']->get($name);
    foreach ($a as $b) {
        echo '===AAAA======';
        echo $b->dump();
    }
    echo '=====bbbbb======';
}

//$app['assetic.asset_writer']->writeManagerAssets($app['assetic.lazy_asset_manager']);

/*$response = new \Symfony\Component\HttpFoundation\Response($a->dump());
$response->headers->set('Content-Type', 'text/javascript');
$response->send();*/