<?php

namespace Broda\Component\Validator\Provider;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcCache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\Common\Cache\MemcacheCache;
use Doctrine\Common\Cache\MemcachedCache;
use Doctrine\Common\Cache\RedisCache;
use Doctrine\Common\Cache\XcacheCache;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Validator\Mapping\ClassMetadataFactory;
use Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Validator\Mapping\Loader\LoaderChain;
use Symfony\Component\Validator\Mapping\Loader\StaticMethodLoader;
use Symfony\Component\Validator\Mapping\Loader\XmlFileLoader;
use Symfony\Component\Validator\Mapping\Loader\YamlFileLoader;
use Symfony\Component\Validator\Mapping\Cache\DoctrineCache;

/**
 * Classe ValidatorExtendedServiceProvider
 *
 */
class ValidatorExtendedServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        if (!isset($app['validator'])) {
            throw new \LogicException('Register ValidatorServiceProvider first');
        }

        $app['validator.default_options'] = array(
            'mapping' => array(
                'loader' => array('annotation', 'static_method'),
                'cache' => false,
            )
        );

        $app['validator.options'] = array();

        $app['validator.options.initializer'] = $app->protect(function () use ($app) {
            static $initialized = false;

            if ($initialized) {
                return;
            }

            $initialized = true;

            $tmp = $app['validator.options'];
            foreach ($tmp as &$options) {
                $options = array_merge($app['validator.default_options'], (array)$options);
            }
            $app['validator.options'] = $tmp;
        });

        $app['validator.doctrine.cache.factory.array'] = $app->protect(function() {
            return new ArrayCache;
        });

        $app['validator.doctrine.cache.factory.filesystem'] = $app->protect(function($cacheOptions) {
            if (empty($cacheOptions['path'])) {
                throw new \RuntimeException('FilesystemCache path not defined');
            }
            return new FilesystemCache($cacheOptions['path']);
        });

        $app['validator.doctrine.cache.factory'] = $app->protect(function($driver, $cacheOptions) use ($app) {
            switch ($driver) {
                case 'array':
                    return $app['validator.doctrine.cache.factory.array']();
                /*case 'apc':
                    return $app['validator.doctrine.cache.factory.apc']();
                case 'xcache':
                    return $app['validator.doctrine.cache.factory.xcache']();
                case 'memcache':
                    return $app['validator.doctrine.cache.factory.memcache']($cacheOptions);
                case 'memcached':
                    return $app['validator.doctrine.cache.factory.memcached']($cacheOptions);*/
                case 'filesystem':
                    return $app['validator.doctrine.cache.factory.filesystem']($cacheOptions);
                /*case 'redis':
                    return $app['validator.doctrine.cache.factory.redis']($cacheOptions);*/
                default:
                    throw new \RuntimeException("Unsupported cache type '$driver' specified");
            }
        });

        $app['validator.mapping.loader'] = function () use ($app) {
            $app['validator.options.initializer']();

            $options = $app['validator.options']['mapping'];

            if (!is_array($options['loader'])) {
                $options['loader'] = array($options['loader']);
            }

            $loaders = array();
            foreach ($options['loader'] as $loader) {
                if (is_string($loader)) {
                    $loader = array(
                        'type' => $loader
                    );
                }

                switch($loader['type']) {
                    case 'annotation':
                        if (isset($app['annotation.reader'])) {
                            $reader = $app['annotation.reader'];
                        } else {
                            $reader = new CachedReader(new AnnotationReader, new ArrayCache);
                        }
                        $loaders[] = new AnnotationLoader($reader);
                    case 'xml':
                        if (!$loader['path']) {
                            throw new \RuntimeException('File path required for xml validator reader');
                        }
                        $loaders[] = new XmlFileLoader($loader['path']);
                    case 'yaml':
                        if (!$loader['path']) {
                            throw new \RuntimeException('File path required for yaml validator reader');
                        }
                        $loaders[] = new YamlFileLoader($loader['path']);
                    case 'static_method':
                        $loaders[] = new StaticMethodLoader();
                    default:
                        throw new \RuntimeException('Unsupported "'.$loader['type'].'" loader for validator');
                }
            }

            return new LoaderChain($loaders);
        };

        $app['validator.mapping.cache'] = function () use ($app) {
            $app['validator.options.initializer']();

            $options = $app['validator.options']['mapping'];

            if (!$options['cache']) {
                return null; // no cache
            }

            if (is_string($options['cache'])) {
                $options['cache'] = array(
                    'type' => $options['cache']
                );
            }

            if (!$options['cache']['type']) {
                return null; // no cache
            }

            $cache = $app['validator.doctrine.cache.factory']($options['cache']['type'], $options['cache']);

            return new DoctrineCache($cache);
        };

        // replace the mapping metedata_factory
        $app['validator.mapping.class_metadata_factory'] = function () use ($app) {
            return new ClassMetadataFactory($app['validator.mapping.loader'], $app['validator.mapping.cache']);
        };
    }

}
