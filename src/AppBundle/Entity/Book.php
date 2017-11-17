<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="books")
 */
class Book
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    public $id = -1;

    /**
     * @ORM\Column(type="string", length=256)
     */
    public $name = '';
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    public $type = '';
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $authors = array();
    
    /**
     * @ORM\Column(type="json_array", length=1024)
     */
    public $genres = array();
    
    public $series = array();
}
