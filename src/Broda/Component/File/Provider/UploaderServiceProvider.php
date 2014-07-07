<?php

namespace Broda\Component\File\Provider;

use Broda\Component\File\Uploader;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Description of UploaderServiceProvider
 *
 * @author raphael
 */
class UploaderServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['uploader.path'] = '';
        
        $app['uploader'] = function() use ($app) {
            $request = isset($app['request']) ? $app['request'] : Request::createFromGlobals();
            return new Uploader($request, $app['uploader.path']);
        };
    }

}
