<?php

namespace Broda\Component\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\PersistentObject;
use Doctrine\ORM\Configuration;
use Pimple\Container;

/**
 * Classe Registry
 *
 * Esta classe é como a 'dbs' ou 'orm.ems': ela guarda todas as conexões e managers
 * do Doctrine num registro.
 *
 * Serve principalmente para integraçao com algum bundle do Symfony, já que
 * no Symfony o service 'doctrine' é um Registry
 * Para bundles que precisam de Registry (no caso, o JMS\Serializer)
 *
 */
class Registry implements ManagerRegistry
{

    /**
     *
     * @var Container
     */
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function getAliasNamespace($alias)
    {
        foreach ($this->getManagerNames() as $name) {
            try {
                $config = $this->getManager($name)->getConfiguration();

                if ($config instanceof Configuration) {
                    return $config->getEntityNamespace($alias);
                }

            } catch (\Exception $e) {
            }
        }

        throw new \InvalidArgumentException('Alias namespace not found');
    }

    public function getConnection($name = null)
    {
        return $name ? $this->app['dbs'][$name] : $this->app['db'];
    }

    public function getConnectionNames()
    {
        return $this->app['dbs']->keys();
    }

    public function getConnections()
    {
        return $this->app['dbs'];
    }

    public function getDefaultConnectionName()
    {
        return $this->app['dbs.default'];
    }

    public function getDefaultManagerName()
    {
        return $this->app['orm.ems.default'];
    }

    public function getManager($name = null)
    {
        return $name ? $this->app['orm.ems'][$name] : $this->app['orm.em'];
    }

    public function getManagerForClass($class)
    {
        if (is_subclass_of($class, 'Doctrine\Common\Persistence\PersistentObject')) {
            return PersistentObject::getObjectManager();
        } else {
            foreach ($this->getManagerNames() as $id) {
                $manager = $this->getManager($id);

                if (!$manager->getMetadataFactory()->isTransient($class)) {
                    return $manager;
                }
            }
        }
        return null;
    }

    public function getManagerNames()
    {
        return $this->app['orm.ems']->keys();
    }

    public function getManagers()
    {
        return $this->app['orm.ems'];
    }

    public function getRepository($persistentObject, $persistentManagerName = null)
    {
        return $this->getManager($persistentManagerName)->getRepository($persistentObject);
    }

    public function resetManager($name = null)
    {
        /*unset($this->app['orm.ems'][$name ?: $this->getDefaultManagerName()]);
        if (null !== $name) {
            // unset the alias too
            unset($this->app['orm.em']);
        }*/
    }

}
