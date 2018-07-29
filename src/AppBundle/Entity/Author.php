<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="authors")
 */
class Author
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
     * @ORM\Column(type="string", length=256)
     */
    public $forename;
    
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    public $surname;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $alias;
    
    public $books = array();
}
