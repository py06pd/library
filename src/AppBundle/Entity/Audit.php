<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit")
 */
class Audit
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    public $logid;

    /**
     * @ORM\Column(type="integer")
     */
    public $userid;

    /**
     * @ORM\Column(type="integer")
     */
    public $timestamp;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $itemid;

    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    public $itemname;
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    public $description;
    
    /**
     * @ORM\Column(type="json_array", length=1024, nullable=true)
     */
    public $details;
}
