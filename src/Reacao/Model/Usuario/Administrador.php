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
     * @param LifecycleEventArgs $event
     *
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function presetRoles(LifecycleEventArgs $event)
    {
        $em = $event->getEntityManager();
        /* @var $role Role */
        $role = $em->getRepository(get_class(new Role))->findOneBy(array('role' => 'ROLE_ADMIN'));

        if (null !== $role && !$this->roles->contains($role)) {
            $this->roles->add($role);
            $role->getUsuarios()->add($this);
        }
    }

}
