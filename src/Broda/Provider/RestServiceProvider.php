<?php

namespace Broda\Provider;

use Broda\Rest\Resource;
use Broda\Rest\ResourceManager;
use Silex\Application;
use Silex\ServiceControllerResolver;
use Silex\ServiceProviderInterface;

class RestServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        if (!($app['resolver'] instanceof ServiceControllerResolver)) {
            throw new \RuntimeException('Register ServiceControllerServiceProvider first.');
        }

        foreach (array('all', 'post', 'get', 'put', 'patch', 'delete') as $method) {
            $app['rest.methods.' . $method] = $method;
        }

        $app['rest'] = $app->share(function($app) {
            Resource::$defaultMethods = array(
                'all' => $app['rest.methods.all'],
                'post' => $app['rest.methods.post'],
                'get' => $app['rest.methods.get'],
                'put' => $app['rest.methods.put'],
                'patch' => $app['rest.methods.patch'],
                'delete' => $app['rest.methods.delete'],
            );

            return new ResourceManager($app);
        });
    }

    public function boot(Application $app)
    {

    }

}
