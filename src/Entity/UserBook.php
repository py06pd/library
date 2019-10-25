<?php
/** src/App/Entity/UserBook.php */
namespace App\Entity;

use DateTime;
use DateTimeZone;
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
     * @var string
     * @ORM\Column(type="string", name="notes", nullable=true)
     */
    private $notes;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $stock;
    
    /**
     * @ORM\Column(type="integer", name="requestedtime", nullable=true)
     */
    private $requestedTime;
    
    /**
     * @ORM\Column(type="integer", name="borrowedtime", nullable=true)
     */
    private $borrowedTime;

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
     * Gets book
     * @return Book
     */
    public function getBook() : Book
    {
        return $this->book;
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
     * Gets borrowed from user
     * @return User
     */
    public function getBorrowedFrom()
    {
        return $this->borrowedFrom;
    }

    /**
     * Sets borrowed from user
     * @param User $user
     * @return UserBook
     */
    public function setBorrowedFrom(User $user = null) : UserBook
    {
        $this->borrowedFrom = $user;
        $this->requestedFrom = null;
        $this->requestedTime = null;
        if ($user == null) {
            $this->borrowedTime = null;
        }

        return $this;
    }

    /**
     * Gets borrowed time
     * @return DateTime
     */
    public function getBorrowedTime() : DateTime
    {
        return new DateTime("@" . $this->borrowedTime);
    }

    /**
     * Sets borrow time
     * @param DateTime $date
     * @return UserBook
     */
    public function setBorrowedTime(DateTime $date) : UserBook
    {
        $this->borrowedTime = $date->getTimestamp();
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
     * Sets gifted from user
     * @param User $user
     * @return UserBook
     */
    public function setGiftedFrom(User $user) : UserBook
    {
        $this->giftedFrom = $user;
        return $this;
    }

    /**
     * Gets notes
     * @return string|null
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Sets notes
     * @param string|null $notes
     * @return UserBook
     */
    public function setNotes(string $notes = null) : UserBook
    {
        $this->notes = (trim($notes) === '') ? null : $notes;
        return $this;
    }

    /**
     * Gets requested from user
     * @return User
     */
    public function getRequestedFrom()
    {
        return $this->requestedFrom;
    }

    /**
     * Sets requested from user
     * @param User $user
     * @return UserBook
     */
    public function setRequestedFrom(User $user = null) : UserBook
    {
        $this->requestedFrom = $user;
        if ($user == null) {
            $this->requestedTime = null;
        }
        
        return $this;
    }

    /**
     * Gets request time
     * @return DateTime
     */
    public function getRequestedTime() : DateTime
    {
        return new DateTime("@" . $this->requestedTime);
    }

    /**
     * Sets request time
     * @param DateTime $date
     * @return UserBook
     */
    public function setRequestedTime(DateTime $date) : UserBook
    {
        $this->requestedTime = $date->getTimestamp();
        return $this;
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
     * Gets amount owned
     * @return int
     */
    public function getStock() : int
    {
        return $this->stock ? $this->stock : 0;
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
            'borrowedFromId' => $this->borrowedFrom ? $this->getBorrowedFrom()->getId() : null,
            'borrowedFrom' => $this->borrowedFrom ? $this->getBorrowedFrom()->getName() : null,
            'borrowedTime' => $this->borrowedFrom ? $this->getBorrowedTime()
                ->setTimezone(new DateTimeZone('Europe/London'))->format('Y-m-d H:i:s') : null,
            'giftedFrom' => $this->giftedFrom ? $this->giftedFrom->getName() : null,
            'notes' => $this->notes,
            'owned' => $this->owned,
            'read' => $this->read,
            'requestedFromId' => $this->requestedFrom ? $this->getRequestedFrom()->getId() : null,
            'requestedFrom' => $this->requestedFrom ? $this->getRequestedFrom()->getName() : null,
            'requestedTime' => $this->requestedFrom ? $this->getRequestedTime()
                ->setTimezone(new DateTimeZone('Europe/London'))->format('Y-m-d H:i:s') : null,
            'stock' => $this->stock,
            'wishlist' => $this->wishlist
        ];
    }
}
