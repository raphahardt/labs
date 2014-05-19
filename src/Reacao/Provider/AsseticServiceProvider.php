<?php

namespace Reacao\Provider;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\AssetReference;
use Assetic\AssetManager;
use Assetic\Extension\Twig\AsseticExtension;
use Assetic\Factory\AssetFactory;
use Assetic\Factory\LazyAssetManager;
use Assetic\FilterManager;
use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Classe AsseticServiceProvider
 *
 */
class AsseticServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {

        $app['assetic.assets'] = $app['assetic.filters'] = array();

        $app['assetic.asset_manager'] = $app->share(function () use ($app) {
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
        });

        $app['assetic.filter_manager'] = $app->share(function () use ($app) {
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
        });

        $app['assetic.factory'] = $app->share(function () use ($app) {
            $factory = new AssetFactory($app['assetic.dist_path']);
            $factory->setAssetManager($app['assetic.asset_manager']);
            $factory->setFilterManager($app['assetic.filter_manager']);
            $factory->setDebug(isset($app['debug']) ? $app['debug'] : false);
            $factory->setDefaultOutput($app['assetic.dist_path']);

            return $factory;
        });

        $app['assetic.lazy_asset_manager'] = $app->share(function () use ($app) {
            $am = new LazyAssetManager($app['assetic.factory']);
            return $am;
        });

        $app['assetic.asset_writer'] = $app->share(function () use ($app) {
            $am = new \Assetic\AssetWriter($app['assetic.factory']);
            return $am;
        });

        if (isset($app['twig'])) {
            $app['twig'] = $app->share($app->extend('twig', function ($twig, $app) {
                $twig->addExtension(new AsseticExtension($app['assetic.factory']));

                return $twig;
            }));
        }
    }

    public function boot(Application $app)
    {

    }

}
