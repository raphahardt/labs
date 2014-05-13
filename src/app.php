<?php

use Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Reacao\Provider\ImagineServiceProvider;
use Silex\Application;
use Silex\Provider\DoctrineServiceProvider;
use Silex\Provider\SecurityServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\SessionServiceProvider;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\UrlGeneratorServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

$app = new Application();

$app->register(new UrlGeneratorServiceProvider());
$app->register(new SessionServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new ImagineServiceProvider());
$app->register(new ValidatorServiceProvider());
$app['validator.mapping.class_metadata_factory'] = $app->share(function ($app) {
    $reader = new CachedReader(new AnnotationReader(), new ArrayCache());
    return new ClassMetadataFactory(new AnnotationLoader($reader));
});

$app->register(new DoctrineServiceProvider());
$app->register(new DoctrineOrmServiceProvider());

$app->register(new SecurityServiceProvider());

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
