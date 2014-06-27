<?php

namespace Broda\Component\Doctrine\Provider;

use Broda\Component\Doctrine\Registry;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DoctrineRegistryServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        if (!isset($app['db'])) {
            throw new \RuntimeException('Register DoctrineServiceProvider first.');
        }

        $app['doctrine_registry'] = function () use ($app) {
            return new Registry($app);
        };

    }

}
