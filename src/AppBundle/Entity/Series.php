<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="series")
 */
class Series
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
     * @ORM\Column(type="string", length=16)
     */
    public $type;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $series;
}
