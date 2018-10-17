<?php
/** src/AppBundle/Entity/UserBook.php */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UserBook entity
 * @ORM\Entity
 * @ORM\Table(name="userbook")
 */
class UserBook implements JsonSerializable
{
   /**
     * Book
     * @var Book
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="users")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $book;

    /**
     * User
     * @var User
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="userid", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="boolean")
     */
    private $owned;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $read;
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $wishlist;

    /**
     * User requested from
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="requestedfromid", referencedColumnName="id", nullable=true)
     */
    private $requestedFrom;

    /**
     * User borrowed from
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="borrowedfromid", referencedColumnName="id", nullable=true)
     */
    private $borrowedFrom;

    /**
     * User gifted from
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="giftfromid", referencedColumnName="id", nullable=true)
     */
    private $giftedFrom;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $notes;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stock;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $requestedtime;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $borrowedtime;

    /**
     * UserBook constructor.
     * @param User $user
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->owned = false;
        $this->read = false;
        $this->wishlist = false;
    }

    /**
     * Sets book
     * @param Book $book
     * @return UserBook
     */
    public function setBook(Book $book) : UserBook
    {
        $this->book = $book;
        return $this;
    }

    /**
     * Gets user
     * @return User
     */
    public function getUser() : User
    {
        return $this->user;
    }

    /**
     * Clears gifted from user
     * @return UserBook
     */
    public function clearGiftedFrom() : UserBook
    {
        $this->giftedFrom = null;
        return $this;
    }

    /**
     * Gets gifted from user
     * @return User
     */
    public function getGiftedFrom()
    {
        return $this->giftedFrom;
    }

    /**
     * Gets owned flag
     * @return bool
     */
    public function isOwned() : bool
    {
        return $this->owned;
    }

    /**
     * Sets owned flag
     * @param bool $owned
     * @return UserBook
     */
    public function setOwned(bool $owned) : UserBook
    {
        $this->owned = $owned;
        if ($this->owned && $this->stock == 0) {
            $this->wishlist = false;
            $this->stock = 1;
        } else {
            $this->stock = null;
        }

        return $this;
    }

    /**
     * Gets read flag
     * @return bool
     */
    public function isRead() : bool
    {
        return $this->read;
    }

    /**
     * Sets read flag
     * @param bool $read
     * @return UserBook
     */
    public function setRead(bool $read) : UserBook
    {
        $this->read = $read;
        return $this;
    }

    /**
     * Gets wishlist flag
     * @return bool
     */
    public function onWishlist()
    {
        return $this->wishlist;
    }

    /**
     * Sets wishlist flag
     * @param bool $wishlist
     * @return UserBook
     */
    public function setWishlist(bool $wishlist) : UserBook
    {
        $this->wishlist = $wishlist;
        return $this;
    }

    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'bookId' => $this->book->getId(),
            'userId' => $this->user->getId(),
            'name' => $this->user->getName(),
            'giftedFrom' => $this->giftedFrom ? $this->giftedFrom->getName() : null,
            'notes' => $this->notes,
            'owned' => $this->owned,
            'read' => $this->read,
            'stock' => $this->stock,
            'wishlist' => $this->wishlist
        ];
    }
}
