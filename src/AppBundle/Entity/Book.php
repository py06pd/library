<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="books")
 */
class Book {
    
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
    public $type;
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $authors;
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $genres;
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $series;
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $owners;
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $read;
}
