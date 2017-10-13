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
    const REQUESTED = 3;
    const BORROWED = 4;
    
    /**
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     */
    public $userid;

    /**
     * @ORM\Column(type="integer")
     */
    public $status;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    public $datetime;
    
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
    
    public function init($id, $userId, $status, $old = null, $stock = 0, $otheruserid = null)
    {
        $this->id = $id;
        $this->userid = $userId;
        $this->datetime = new \DateTime();
        
        if ($old) {
            $this->status += $old->status + $status;
        } else {
            $this->status = $status;
        }
        
        $this->latest = true;
        if ($old) {
            $this->stock += $old->stock + $stock;
        } else {
            $this->stock = $stock;
        }
        $this->otheruserid = $otheruserid;
    }
}
