<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repositories\BookRepository")
 * @ORM\Table(name="books")
 */
class Book implements JsonSerializable
{
    /**
     * Book id
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    private $bookId;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $name;
    
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $type;
    
    /**
     * @ORM\Column(type="json_array", length=1024, nullable=true)
     */
    private $genres;
    
    /**
     * Authors
     * @var BookAuthor[]
     * @ORM\OneToMany(targetEntity="BookAuthor", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $authors;
    
    /**
     * Series
     * @var BookSeries[]
     * @ORM\OneToMany(targetEntity="BookSeries", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $series;
    
    /**
     * Users
     * @var UserBook[]
     * @ORM\OneToMany(targetEntity="UserBook", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $users;
    
    /**
     * Book constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->authors = new ArrayCollection();
        $this->series = new ArrayCollection();
        $this->users = new ArrayCollection();
    }
    
    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->bookId;
    }
    
    /**
     * Sets book id
     * @param int $bookId
     * @return Book
     */
    public function setId(int $bookId = null)
    {
        $this->bookId = $bookId;
        return $this;
    }
    
    /**
     * Gets name
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Sets name
     * @param string $name
     * @return Book
     */
    public function setName(string $name) : Book
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets type
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Sets type
     * @param string|null $type
     * @return Book
     */
    public function setType(string $type = null) : Book
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Gets genres
     * @return array
     */
    public function getGenres()
    {
        if ($this->genres) {
            return $this->genres;
        }
        
        return [];
    }
    
    /**
     * Sets genres
     * @param array|null $genres
     * @return Book
     */
    public function setGenres(array $genres = null) : Book
    {
        $this->genres = $genres;
        return $this;
    }
    
    /**
     * Adds author to book authors
     * @param Author $author
     * @return Book
     */
    public function addAuthor(Author $author) : Book
    {
        $this->authors->add(new BookAuthor($this, $author));
        return $this;
    }
    
    /**
     * Gets authors
     * @return Author[]|ArrayCollection
     */
    public function getAuthors()
    {
        $authors = [];
        if ($this->authors) {
            foreach ($this->authors as $author) {
                $authors[] = $author->getAuthor();
            }
        }
        
        return new ArrayCollection($authors);
    }
    
    /**
     * Checks if book is by an author
     * @param Author $author
     * @return bool
     */
    public function hasAuthor(Author $author) : bool
    {
        foreach ($this->authors as $bookAuthor) {
            if ($bookAuthor->getAuthor() == $author) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remove author from book
     * @param Author $author
     * @return $this
     */
    public function removeAuthor(Author $author)
    {
        foreach ($this->authors as $bookAuthor) {
            if ($bookAuthor->getAuthor() == $author) {
                $this->authors->removeElement($bookAuthor);
            }
        }
        
        return $this;
    }
    
    /**
     * Adds series to book series
     * @param Series $series
     * @param int $number
     * @return Book
     */
    public function addSeries(Series $series, int $number = null) : Book
    {
        $this->series->add(new BookSeries($this, $series, $number));
        return $this;
    }
    
    /**
     * Gets series
     * @return BookSeries[]
     */
    public function getSeries()
    {
        return $this->series;
    }
    
    /**
     * Gets series by id
     * @param int $seriesId
     * @return BookSeries
     */
    public function getSeriesById(int $seriesId)
    {
        foreach ($this->series as $bookSeries) {
            if ($bookSeries->getSeries()->getId() == $seriesId) {
                return $bookSeries;
            }
        }
        
        return null;
    }
    
    /**
     * Checks if a book is in a series
     * @param Series $series
     * @return bool
     */
    public function inSeries(Series $series) : bool
    {
        foreach ($this->series as $bookSeries) {
            if ($bookSeries->getSeries() == $series) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Remove book from series
     * @param Series $series
     * @return $this
     */
    public function removeSeries(Series $series)
    {
        foreach ($this->series as $bookSeries) {
            if ($bookSeries->getSeries() == $series) {
                $this->series->removeElement($bookSeries);
            }
        }
        
        return $this;
    }

    /**
     * Adds user book mapping
     * @param UserBook $user
     * @return Book
     */
    public function addUser(UserBook $user) : Book
    {
        $user->setBook($this);
        $this->users->add($user);
        return $this;
    }

    /**
     * Gets user book mapping
     * @param int $userId
     * @return UserBook|null
     */
    public function getUserById(int $userId)
    {
        foreach ($this->users as $user) {
            if ($user->getUser()->getId() == $userId) {
                return $user;
            }
        }

        return null;
    }

    /**
     * Gets all user book mapping
     * @return UserBook[]
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Remove user from book
     * @param UserBook $user
     * @return Book
     */
    public function removeUser(UserBook $user) : Book
    {
        $this->users->removeElement($user);
        return $this;
    }

    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        $authors = [];
        foreach ($this->authors as $author) {
            $authors[] = $author->getAuthor()->jsonSerialize();
        }
        
        $series = [];
        foreach ($this->series as $s) {
            $series[] = $s->jsonSerialize();
        }

        $users = [];
        foreach ($this->users as $u) {
            $users[] = $u->jsonSerialize();
        }
        
        return [
            'bookId' => $this->bookId,
            'name' => $this->name,
            'type' => $this->type,
            'authors' => $authors,
            'genres' => $this->getGenres(),
            'series' => $series,
            'users' => $users
        ];
    }
}
