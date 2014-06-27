<?php

namespace Broda\Component\Rest;

use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use Silex\Application;
use Symfony\Component\HttpFoundation\Request;

/**
 * Classe RestService
 *
 * @author Raphael Hardt <raphael.hardt@gmail.com>
 */
class RestService
{

    /**
     *
     * @var Application
     */
    private $app;

    /**
     *
     * @var Resource[]
     */
    private $resources;

    /**
     *
     * @var Serializer
     */
    protected $serializer;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->resources = array();
        $this->serializer = isset($app['rest.serializer']) ? $app['rest.serializer'] : SerializerBuilder::create()->build();
    }

    public function resource($path, $controller = null)
    {
        if (!isset($this->resources[$path])) {
            $this->resources[$path] = new Resource($this->app, $path, $controller);
        }
        return $this->resources[$path];
    }

    public function formatOutput($data, $format)
    {
        return $this->serializer->serialize($data, $format);
    }

    public function createObjectFromRequest(Request $request, $class)
    {
        // we must get the raw content, since the deserialization need it raw
        $data = $request->getContent();
        return $this->createObject($data, $class, $request->getContentType());
    }

    public function createObject($data, $class, $format = 'json')
    {
        return $this->serializer->deserialize($data, $class, $format);
    }

    public function filter($query)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->app['orm.em'];
        $request = $this->app['request'];

        $q = null;
        if (is_string($query)) {
            // pode ser um DQL ou uma classe
            if (class_exists($query, false)) {
                // é uma classe
                $q = $em->getRepository($query)->createQueryBuilder('a')->getQuery();
            } else {
                // é um dql
                $q = $em->createQuery($query);
            }
        } elseif ($query instanceof \Doctrine\ORM\QueryBuilder) {
            // querybuider
            $q = $query->getQuery();

        } elseif (!($query instanceof \Doctrine\ORM\Query)) {
            // só aceita os acima ou Query
            throw new \InvalidArgumentException('Not supported');
        }

        $q->setFirstResult($request->query->get('offset', null))
          ->setMaxResults(min(30,(int)$request->query->get('limit', 20)));

        $result = $q->getResult();

        /*if ($after = (int)$request->query->get('after')) {
            $result = $q->andWhere('a.id > '.$after)->getResult();
        }/**/

        return $result;
    }

}
