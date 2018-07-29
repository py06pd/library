<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="userbook")
 */
class UserBook
{
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
     * @ORM\Column(type="boolean")
     */
    public $owned = false;
    
    /**
     * @ORM\Column(type="boolean")
     */
    public $read = false;
    
    /**
     * @ORM\Column(type="boolean")
     */
    public $wishlist = false;
    
    /**
     * @ORM\Column(type="integer")
     */
    public $requestedfromid = 0;

    /**
     * @ORM\Column(type="integer")
     */
    public $borrowedfromid = 0;

    /**
     * @ORM\Column(type="integer")
     */
    public $giftfromid = 0;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    public $notes;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $stock;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $requestedtime;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    public $borrowedtime;
}
