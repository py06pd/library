<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bookhistory")
 */
class BookHistory {
    
    const OWNED = 1;
    const READ = 2;
    const REQUESTED = 4;
    const BORROWED = 8;
    
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $userid;

    /**
     * @ORM\Column(type="integer")
     */
    public $status;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $timestamp;
    
    /**
     * @ORM\Column(type="boolean")
     */
    public $latest;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $stock;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $otheruserid;
    
    public function borrowed()
    {
        return $this->status & self::BORROWED;
    }
    
    public function owned()
    {
        return $this->status & self::OWNED;
    }
    
    public function read()
    {
        return $this->status & self::READ;
    }
    
    public function requested()
    {
        return $this->status & self::REQUESTED;
    }
}
