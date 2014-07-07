<?php

namespace Broda\Component\FileExtractor\Provider;

use Broda\Component\FileExtractor\FileExtractor;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Description of UploaderServiceProvider
 *
 * @author raphael
 */
class FileExtractorServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['extractor.options'] = array();

        $app['extractor'] = function() use ($app) {
            return new FileExtractor($app['extractor.options']);
        };
    }

}
