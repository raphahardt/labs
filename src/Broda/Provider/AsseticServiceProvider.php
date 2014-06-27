<?php

namespace Broda\Provider;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\AssetManager;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Extension\Twig\TwigFormulaLoader;
use Assetic\Extension\Twig\TwigResource;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;
use Assetic\FilterManager;
use Broda\Provider\Assetic\AssetWriter;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\Finder\Finder;

/**
 * Classe AsseticServiceProvider
 *
 */
class AsseticServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{

    public function register(Container $app)
    {

        $app['assetic.assets'] = $app['assetic.filters'] = $app['assetic.workers'] = array();

        $app['assetic.asset_manager'] = function () use ($app) {
            $am = new AssetManager();
            if (isset($app['assetic.assets'])) {
                $assets = $app['assetic.assets'];
                if (!is_array($assets)) {
                    $assets = array($assets);
                }

                foreach ($assets as $name => $asset) {
                    if (!is_array($asset)) {
                        // não collection, transformar em collection
                        $asset = array($asset);
                    }
                    $col = new AssetCollection();
                    foreach ($asset as $a) {
                        if (is_string($a)) {
                            // é referencia
                            $a = new AssetReference($am, $a);
                        }
                        if (!$a instanceof AssetInterface) {
                            throw new \InvalidArgumentException("'assetic.assets' precisa ser um array de AssetInterface");
                        }
                        $col->add($a);
                    }
                    $am->set($name, $col);
                }
            }
            return $am;
        };

        $app['assetic.filter_manager'] = function () use ($app) {
            $fm = new FilterManager();
            if (isset($app['assetic.filters'])) {
                $filters = $app['assetic.filters'];
                if (!is_array($filters)) {
                    $filters = array($filters);
                }

                foreach ($filters as $name => $filter) {
                    $fm->set($name, $filter);
                }
            }
            return $fm;
        };

        $app['assetic.factory'] = function () use ($app) {
            $factory = new AssetFactory($app['assetic.dist_path']);
            $factory->setAssetManager($app['assetic.asset_manager']);
            $factory->setFilterManager($app['assetic.filter_manager']);
            $factory->setDebug(isset($app['debug']) ? $app['debug'] : false);
            $factory->setDefaultOutput($app['assetic.dist_path']);
            if (isset($app['assetic.workers']) && is_array($app['assetic.workers'])) {
                foreach ($app['assetic.workers'] as $worker) {
                    $factory->addWorker($worker);
                }
            }

            return $factory;
        };

        $app['assetic.lazy_asset_manager'] = function () use ($app) {
            $am = new LazyAssetManager($app['assetic.factory']);

            if (isset($app['twig'])) {
                // carrega os assets pelo twig
                $am->setLoader('twig', new TwigFormulaLoader($app['twig']));

                $loader = $app['twig.loader.filesystem'];
                $namespaces = $loader->getNamespaces();

                foreach ($namespaces as $ns) {
                    if ( count($loader->getPaths($ns)) > 0 ) {
                        $iterator = Finder::create()->files()->in($loader->getPaths($ns));

                        foreach ($iterator as $file) {
                            $resource = new TwigResource($loader, '@' . $ns . '/' . $file->getRelativePathname());
                            $am->addResource($resource, 'twig');
                        }
                    }
                }
            }
            return $am;
        };

        $app['assetic.asset_writer'] = function () use ($app) {
            return new AssetWriter($app['assetic.dist_path']);
        };

        if (isset($app['twig'])) {
            $app['twig'] = $app->extend('twig', function ($twig, $app) {
                $functions = array(
                    'cssrewrite' => array(
                        'options' => array(
                            //'root' => $app['assetic.dist_path'],
                            //'name' => 'core',
                            //'debug' => isset($app['debug']) ? $app['debug'] : false,
                            'combine' => true,
                        )
                    )
                );
                $twig->addExtension(new AsseticExtension($app['assetic.factory'], $functions));

                return $twig;
            });
        }
    }

    public function boot(Application $app)
    {
        if (isset($app['debug']) && $app['debug']) {
            $app->after(function () use ($app) {
                $app['assetic.asset_writer']->writeManagerAssets($app['assetic.lazy_asset_manager']);
            });
        }
    }

}
