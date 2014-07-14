<?php

use Symfony\Component\Debug\ErrorHandler;
use Symfony\Component\Debug\ExceptionHandler;
use Symfony\Component\HttpFoundation\Request;

$loader = require __DIR__.'/../vendor/autoload.php';

$request = Request::createFromGlobals();
$env = $request->getHost() === 'localhost' ? 'dev' : 'prod';

$app = require __DIR__.'/src/app.php';
require __DIR__.'/cfg/'.$env.'.php';

Request::enableHttpMethodParameterOverride();
ErrorHandler::register();
ExceptionHandler::register($app['debug']);

$app['controller.rest'] = function () use ($app) {
    return new \Proj\Controller\TestRestfulController($app['rest']);
};

$app->get('/', function () use ($app) {
    return $app['twig']->render('bootstrap.twig');
});

$app->get('/unzip', function () use ($app) {
    /* @var $extractor Broda\Component\FileExtractor\FileExtractor */
    $extractor = $app['extractor'];

    $response = '';

    $files = $extractor->open(new \SplFileInfo(__DIR__.'/public/test.zip'))->extract();

    /*$response .= '<pre>';
    $response .= print_r($files, true);
    $response .= '</pre>';*/
    foreach ($files as $f) {
        $response .= $f->getPathname() . '<br>';
    }

    return $response;
});

$app->get('/prefetch', function () use ($app) {
    return new \Symfony\Component\HttpFoundation\JsonResponse(array(
        array('num' => 'two'),
        array('num' => 'twenty'),
        array('num' => 'thirthen'),
        array('num' => 'fourteen'),
        array('num' => 'fiveteen'),
    ));
});

$app->post('/ajax', function (Request $request) use ($app) {

    $data = require __DIR__.'/testdata.php';
    $total = count($data);
    $totalfiltered = $total;

    $result = $app['rest']->filter($data, $request->request);

    return new Broda\Component\Rest\RestResponse(array(
        'draw' => (int)$request->request->get('draw'),
        'recordsTotal' => $total,
        'recordsFiltered' => $totalfiltered,
        'data' => $result
    ));
});

$app->get('/remote/{query}', function ($query = null) use ($app) {
    $response = array(
        array('num' => 'one'),
        array('num' => 'thousand'),
        array('num' => 'hundred'),
        array('num' => 'a thousand'),
    );
    $response = array_filter($response, function ($val) use ($query) {
        return false !== strpos($val['num'], $query);
    });
    return new \Symfony\Component\HttpFoundation\JsonResponse($response);
});

$app->error(function (\Doctrine\ORM\ORMException $e, $code) use ($app) {
    if (isset($app['logger'])) {
        $app['logger']->alert($e);
    }
});

$app->error(function (Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return new Response($e->getMessage(), $code);
});

$app->run($request);