<?php

namespace Reacao\Model;

use Broda\Model\AbstractModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMS;
use Reacao\Model\Role;
use Reacao\Model\Usuario;
use Reacao\Model\UsuarioRepository;
use Serializable;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Acme\UserBundle\Entity\User
 *
 * @ORM\Entity(repositoryClass="UsuarioRepository")
 * @ORM\InheritanceType("JOINED")
 * ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="usertype", type="string", length=128)
 * @ORM\DiscriminatorMap(
 *     {"autor"       = "\Reacao\Model\Usuario\Autor",
 *     "jornalista"  = "\Reacao\Model\Usuario\Jornalista",
 *     "colaborador" = "\Reacao\Model\Usuario\Colaborador",
 *     "admin"       = "\Reacao\Model\Usuario\Administrador"}
 * )
 */
class Usuario extends AbstractModel implements AdvancedUserInterface, EquatableInterface, Serializable
{

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=25, unique=true)
     *
     * @Assert\NotBlank
     *
     * @JMS\Type("string")
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=128)
     *
     * @Assert\NotBlank
     *
     * @JMS\Type("string")
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     *
     * @JMS\Exclude
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     *
     * @JMS\Type("string")
     */
    private $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     *
     * @JMS\Type("boolean")
     */
    private $isActive;

    /**
     * @var ArrayCollection
     *
     * @JMS\Type("ArrayCollection<string>")
     *
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="usuarios")
     */
    protected $roles;

    public function __construct()
    {
        $this->roles = new ArrayCollection();
        $this->isActive = true;
        // may not be needed, see section on salt below
        $this->salt = md5(uniqid(null, true));
    }

    public function getId()
    {
        return $this->id;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function setUsername($username)
    {
        $this->username = $username;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return $this->salt;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    public function setPassword($password)
    {
        $this->password = $password;
    }

    public function encodePassword(EncoderFactoryInterface $encoderFactory)
    {
        $this->password = $encoderFactory->getEncoder($this)->encodePassword($this->password, $this->salt);
    }

    public function isPasswordValid(EncoderFactoryInterface $encoderFactory, $inputPassword)
    {
        return $encoderFactory->getEncoder($this)->isPasswordValid($this->password, $inputPassword, $this->salt);
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }

    public function getRoles()
    {
        return $this->roles->toArray();
    }

    public function eraseCredentials()
    {

    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    /**
     * @see Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
            $this->username,
            $this->password,
            // see section on salt below
            $this->salt,
        ));
    }

    /**
     * @see Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
                $this->id,
                $this->username,
                $this->password,
                // see section on salt below
                $this->salt
                ) = unserialize($serialized);
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof Usuario) {
            return false;
        }

        if ($this->password !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->username !== $user->getUsername()) {
            return false;
        }

        return true;
    }

}
