<?php

namespace Broda\Component\Doctrine\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ArrayCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;

/**
 * Classe DoctrineAnnotationServiceProvider
 *
 */
class DoctrineAnnotationServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

    public function register(Container $app)
    {
        $app['annotation.loaders'] = array();

        $app['annotation.global_ignores_names'] = array();

        $app['annotation.cache'] = function () use ($app) {
            return new ArrayCache();
        };

        $app['annotation.reader'] = function () use ($app) {
            return new AnnotationReader();
        };
    }

    public function boot(Application $app)
    {
        foreach ($app['annotation.loaders'] as $loader) {
            if (is_object($loader) && method_exists($loader, 'loadClass')) {
                // support for composer/symfony's autoloader
                AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
            } elseif (is_callable($loader)) {
                AnnotationRegistry::registerLoader($loader);
            } elseif (is_array($loader) && is_string($loader[0])) {
                AnnotationRegistry::registerAutoloadNamespace($loader[0], $loader[1]);
            } elseif (is_array($loader) && is_string(key($loader))) {
                AnnotationRegistry::registerAutoloadNamespace(key($loader), reset($loader));
            } elseif (is_string($loader)) {
                AnnotationRegistry::registerFile($loader);
            } else {
                throw new \LogicException('Not a valid Annotation loader. Must be a Composer Autoloader instance, a callable, a array with namespace/path format or a string with path to a register file');
            }
        }

        foreach ($app['annotation.global_ignores_names'] as $annotation) {
            AnnotationReader::addGlobalIgnoredName($annotation);
        }

        if (!$app['debug']) {
            $app->extend('annotation.reader', function($reader) use ($app) {
                return new CachedReader($reader, $app['annotation.cache']);
            });
        }
    }

}
