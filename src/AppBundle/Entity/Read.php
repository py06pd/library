<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="read")
 */
class Read
{
    /**
     * @ORM\Column(type="integer")
     */
    public $bookId;

    /**
     * @ORM\Column(type="integer")
     */
    public $userId;
    
    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $dateTime;
}
