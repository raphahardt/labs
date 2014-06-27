<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Broda\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Classe ImagineServiceProvider
 *
 */
class ImagineServiceProvider implements ServiceProviderInterface
{

    public function register(Container $app)
    {
        if (!isset($app['imagine.factory'])) {
            $app['imagine.factory'] = $app->factory(function () {
                if (extension_loaded('imagick') && class_exists('\Imagick')) {
                    return 'Imagick';
                }
                elseif (class_exists('\Gmagick')) {
                    return 'Gmagick';
                }
                return 'Gd';
            });
        }

        $app['imagine'] = function ($app) {
            $class = sprintf('\Imagine\%s\Imagine', $app['imagine.factory']);
            return new $class();
        };
    }

}
