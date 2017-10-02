<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User {
    
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
}
