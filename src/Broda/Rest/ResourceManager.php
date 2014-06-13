<?php

namespace Broda\Rest;

use Silex\Application;

/**
 * Classe ResourceManager
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class ResourceManager
{

    /**
     *
     * @var Application
     */
    protected $app;
    protected $resources;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->resources = new \Pimple();
    }

    public function resource($path, $controller = null)
    {
        if (!isset($this->resources[$path])) {
            $app = $this->app;
            $this->resources[$path] = $this->resources->share(function () use ($app, $path, $controller) {
                return new Resource($app, $path, $controller);
            });
        }
        return $this->resources[$path];
    }

}
