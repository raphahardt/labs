<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Reacao\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

/**
 * Classe ImagineServiceProvider
 *
 * @author Sistema13 <sistema13@furacao.com.br>
 */
class ImagineServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        if (!isset($app['imagine.factory'])) {
            $app['imagine.factory'] = function () {
                if (extension_loaded('imagick') && class_exists('\Imagick')) {
                    return 'Imagick';
                }
                elseif (class_exists('\Gmagick')) {
                    return 'Gmagick';
                }
                return 'Gd';
            };
        }

        $app['imagine'] = $app->share(function ($app) {
            $class = sprintf('\Imagine\%s\Imagine', $app['imagine.factory']);
            return new $class();
        });
    }

    public function boot(Application $app)
    {

    }

}
