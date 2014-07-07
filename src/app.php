<?php

use Broda\Application;
use Broda\Component\Doctrine\Provider\DoctrineOrmServiceProvider;
use Broda\Component\Doctrine\Provider\DoctrineRegistryServiceProvider;
use Broda\Component\File\Provider\UploaderServiceProvider;
use Broda\Component\FileExtractor\Provider\FileExtractorServiceProvider;
use Broda\Component\Rest\Provider\RestServiceProvider;
use Broda\Component\Validator\Provider\ValidatorExtendedServiceProvider;
use Broda\Provider\AsseticServiceProvider;
use Broda\Provider\ImagineServiceProvider;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\SerializerServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;


$app = new Application();

$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new ImagineServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ValidatorExtendedServiceProvider());

$app->register(new DoctrineServiceProvider());
$app->register(new DoctrineOrmServiceProvider());
$app->register(new DoctrineRegistryServiceProvider());

$app->register(new SecurityServiceProvider());

$app->register(new AsseticServiceProvider());

$app->register(new RestServiceProvider());

$app->register(new SerializerServiceProvider());

$app->register(new UploaderServiceProvider());
$app->register(new FileExtractorServiceProvider());

/*$app['twig'] = $app->share($app->extend('twig', function ($twig) use ($app) {
    /* @var $twig \Twig_Environment * /
    $function = new Twig_SimpleFunction('include_html', function (Twig_Environment $env, $file) {

    }, array('needs_environment' => true));
    $twig->addFunction($function);
    return $twig;
}));*/

/*
 * para tratar POSTs que vem como json
 * ver: http://silex.sensiolabs.org/doc/cookbook/json_request_body.html
 */
$app->before(function (Request $request) {
    if (0 === strpos($request->headers->get('Content-Type'), 'application/json')) {
        $data = json_decode($request->getContent(), true);
        $request->request->replace(is_array($data) ? $data : array());
    }
});

return $app;
