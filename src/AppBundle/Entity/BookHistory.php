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
    
    public function borrow($fromId = null)
    {
        if (!$this->isBorrowed()) {
            $this->status += self::BORROWED;

            if ($fromId) {
                $this->otheruserid = $fromId;
            }

            if ($this->isRequested()) {
                $this->status -= self::REQUESTED;
            }
        }
        
        return $this;
    }
    
    public function double()
    {
        $item = new BookHistory();
        $item->id = $this->id;
        $item->userid = $this->userid;
        $item->status = $this->status;
        $item->timestamp = time();
        $item->latest = true;
        $item->stock = $this->stock;
        $item->otheruserid = $this->otheruserid;
        
        return $item;
    }
    
    public function init($id, $userId)
    {
        $this->id = $id;
        $this->userid = $userId;
        $this->timestamp = time();
        $this->status = 0;
        $this->stock = 0;
        $this->latest = true;
        
        return $this;
    }
    
    public function isBorrowed()
    {
        return $this->status & self::BORROWED;
    }
    
    public function isOwned()
    {
        return $this->status & self::OWNED;
    }
    
    public function isRead()
    {
        return $this->status & self::READ;
    }
    
    public function isRequested()
    {
        return $this->status & self::REQUESTED;
    }
    
    public function own()
    {
        if (!$this->isOwned()) {
            $this->status += self::OWNED;
            $this->stock += 1;
        }
        
        return $this;
    }
    
    public function read()
    {
        if (!$this->isRead()) {              
            $this->status += self::READ;
        }
        
        return $this;
    }
    
    public function request($fromId = null)
    {
        $this->status += self::REQUESTED;
        
        if ($fromId) {
            $this->otheruserid = $fromId;
        }
        
        return $this;
    }
    
    public function unborrow()
    {
        if ($this->isBorrowed()) {
            $this->status -= self::BORROWED;
            $this->otheruserid = null;
        }
        
        return $this;
    }
    
    public function unown()
    {
        if ($this->isOwned()) {
            $this->status -= self::OWNED;
            $this->stock -= 1;
        }
        
        return $this;
    }
    
    public function unread()
    {
        if ($this->isRead()) {
            $this->status -= self::READ;
        }
        
        return $this;
    }
    
    /**
     * @param int $userid - id of user that cancels request to differentiate between cancel and reject
     * @return \AppBundle\Entity\BookHistory
     */
    public function unrequest($userid)
    {
        if ($this->isRequested()) {
            $this->status -= self::REQUESTED;
            $this->otheruserid = $userid;
        }
        
        return $this;
    }
}
