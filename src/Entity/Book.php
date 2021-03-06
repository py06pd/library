<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity(repositoryClass="App\Repositories\BookRepository")
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
     * Type
     * @var Type
     * @ORM\ManyToOne(targetEntity="Type", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="type_id", referencedColumnName="type_id")
     */
    private $type;

    /**
     * Genres
     * @var BookGenre[]
     * @ORM\OneToMany(targetEntity="BookGenre", mappedBy="book", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $genres;

    /**
     * Creator user
     * @var User
     * @ORM\OneToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator_id", referencedColumnName="id")
     */
    private $creator;
    
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
        $this->genres = new ArrayCollection();
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
     * @return Type|null
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Sets type
     * @param Type|null $type
     * @return Book
     */
    public function setType(Type $type = null) : Book
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Adds genre to book genres
     * @param Genre $genre
     * @return Book
     */
    public function addGenre(Genre $genre) : Book
    {
        $this->genres->add(new BookGenre($this, $genre));
        return $this;
    }

    /**
     * Gets genres
     * @return Genre[]|ArrayCollection
     */
    public function getGenres()
    {
        $genres = [];
        if ($this->genres) {
            foreach ($this->genres as $genre) {
                $genres[] = $genre->getGenre();
            }
        }

        return new ArrayCollection($genres);
    }

    /**
     * Checks if book is by an genre
     * @param Genre $genre
     * @return bool
     */
    public function hasGenre(Genre $genre) : bool
    {
        foreach ($this->genres as $bookGenre) {
            if ($bookGenre->getGenre() === $genre) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove genre from book
     * @param Genre $genre
     * @return $this
     */
    public function removeGenre(Genre $genre)
    {
        foreach ($this->genres as $bookGenre) {
            if ($bookGenre->getGenre() === $genre) {
                $this->genres->removeElement($bookGenre);
            }
        }

        return $this;
    }

    /**
     * Gets creator
     * @return User
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * Sets creator
     * @param User $creator
     * @return Book
     */
    public function setCreator(User $creator) : Book
    {
        $this->creator = $creator;
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
            if ($bookAuthor->getAuthor() === $author) {
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
            if ($bookAuthor->getAuthor() === $author) {
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
            if ($bookSeries->getSeries() === $series) {
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
            if ($bookSeries->getSeries() === $series) {
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
     * Check if user is only one connected to book
     * @param User $user
     * @return bool
     */
    public function isOnlyUser(User $user)
    {
        return ($this->getCreator()->getId() == $user->getId() && (count($this->getUsers()) == 0 || (
            count($this->getUsers()) == 1 && $this->getUsers()[0]->getUser()->getId() == $user->getId()
        )));
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

        $genres = [];
        foreach ($this->genres as $genre) {
            $genres[] = $genre->getGenre()->jsonSerialize();
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
            'type' => $this->type ? $this->type->jsonSerialize() : null,
            'creatorId' => $this->creator ? $this->creator->getId() : null,
            'authors' => $authors,
            'genres' => $genres,
            'series' => $series,
            'users' => $users
        ];
    }
}
