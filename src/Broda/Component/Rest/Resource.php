<?php

namespace Broda\Component\Rest;

use Silex\Application;

/**
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class Resource
{

    /**
     *
     * @var Application
     */
    protected $app;
    protected $path;
    protected $idName;
    protected $format = '';
    protected $routes;
    public static $defaultMethods = array(
        'all' => 'all',
        'post' => 'post',
        'get' => 'get',
        'put' => 'put',
        'patch' => 'patch',
        'delete' => 'delete',
    );

    public function __construct(Application $app, $path, $controller = null,
            $idName = 'id')
    {
        $controller = $this->createServiceForController($controller);

        $pathParts = explode('.', $path);
        $path = array_shift($pathParts);
        if (count($pathParts)) {
            $this->format = '.'.reset($pathParts);
        }

        $this->app = $app;
        $this->path = $path;
        $this->idName = $idName;
        $this->routes = array();

        if (null !== $controller) {
            $defaultMethods = self::$defaultMethods;

            foreach ($defaultMethods as $routeName => $method) {
                $this->match($routeName, sprintf('%s:%s', $controller, $method));
            }
        }
    }

    protected function itemPath()
    {
        return sprintf('%s/{%s}%s', $this->path, $this->idName, $this->format);
    }

    public function path($method)
    {
        switch (strtolower($method)) {
            case 'get':
            case 'put':
            case 'patch':
            case 'delete':
                return $this->itemPath();
            default:
                return $this->path.$this->format;
        }
    }

    public function subresource($path, $controller = null, $idName = null)
    {
        if (null === $idName) {
            $idName = $this->idName . 'd';
        }

        if ($idName === $this->idName) {
            throw new \InvalidArgumentException('The REST path '.$this->itemPath() . $path.' can not use '.$idName.' as \'id\'');
        }

        return new Resource($this->app, $this->itemPath() . $path, $controller, $idName);
    }

    public function match($method, $controller)
    {
        if (isset($this->routes[$method])) {
            throw new \LogicException(sprintf('%s route is already set', $method));
        }
        $this->routes[$method] = $this->app->match($this->path($method), $controller)->method($method === 'all' ? 'get' : $method);
        return $this;
    }

    public function all($controller)
    {
        return $this->match('all', $controller);
    }

    public function post($controller)
    {
        return $this->match('post', $controller);
    }

    public function get($controller)
    {
        return $this->match('get', $controller);
    }

    public function put($controller)
    {
        return $this->match('put', $controller);
    }

    public function patch($controller)
    {
        return $this->match('patch', $controller);
    }

    public function delete($controller)
    {
        return $this->match('delete', $controller);
    }

    public function before($routeName, $closure)
    {
        if (array_key_exists($routeName, $this->routes)) {
            $this->routes[$routeName]->before($closure);
        }

        return $this;
    }

    public function after($routeName, $closure)
    {
        if (array_key_exists($routeName, $this->routes)) {
            $this->routes[$routeName]->after($closure);
        }

        return $this;
    }

    public function assertId($constraint)
    {
        $routesWithId = array('get', 'put', 'delete', 'patch');

        foreach ($this->routes as $routeName => $route) {
            if (in_array($routeName, $routesWithId)) {
                $route->assert($this->idName, $constraint);
            }
        }

        return $this;
    }

    public function convert($variable, $closure)
    {
        $routesWithId = array('get', 'put', 'delete', 'patch');

        foreach ($this->routes as $routeName => $route) {
            if (in_array($routeName, $routesWithId)) {
                $route->convert($variable, $closure);
            }
        }

        return $this;
    }

    private function createServiceForController($controller)
    {
        if (is_object($controller) || class_exists($controller, false)) {
            $ctrlServiceName = $this->classServiceName($controller);

            // cria um serviço temporario para a classe, já que o resource
            // só suporta controllers-serviços
            $this->app[$ctrlServiceName] = $this->app->share(function () use ($controller) {
                return is_string($controller) ? new $controller : $controller;
            });

            $controller = $ctrlServiceName;
        }
        return $controller;
    }

    private function classServiceName($className)
    {
        if (is_object($className)) {
            $className = get_class($className);
        }
        $className = preg_replace('/([^A-Z])([A-Z])/', "$1_$2", $className);
        $className = str_replace('\\', '.', $className);
        $className = strtolower($className);

        return $className;
    }

}
