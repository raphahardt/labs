<?php

namespace Reacao\Model\Usuario;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;
use Reacao\Model\Role;
use Reacao\Model\Usuario;

/**
 * Classe Autor
 *
 *
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class Administrador extends Usuario
{

    /**
     *
     * @param Usuario $user
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostLoad
     */
    public function postLoad(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        $role = $em->getRepository(get_class(new Role))->findOneBy(array('role' => 'ROLE_ADMIN'));

        if (null !== $role && !$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

}
