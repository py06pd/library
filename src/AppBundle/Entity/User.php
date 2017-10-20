<?php

namespace AppBundle\Entity;

use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User implements UserInterface
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=256)
     */
    public $name;
    
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    public $facebookToken;
    
    /**
     * @ORM\Column(type="string", length=16)
     */
    public $role;
    
    /**
     * @ORM\Column(type="string", length=32)
     */
    public $username;
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    public $password;
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    public $sessionid;
    
    public function getSalt()
    {
        return null;
    }
    
    public function getUsername()
    {
        return $this->username;
    }
    
    public function getPassword()
    {
        return $this->password;
    }
    
    public function getRoles()
    {
        return array($this->role);
    }
    
    public function eraseCredentials()
    {
        return null;
    }
}
