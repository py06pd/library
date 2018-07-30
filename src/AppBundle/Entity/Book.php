<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="AppBundle\Repositories\BookRepository")
 * @ORM\Table(name="books")
 */
class Book
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    private $id;

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
     * @var BookAuthor[]|Collection
     * @ManyToOne(targetEntity="BookAuthor", mappedBy="book", cascade={"persist", "remove"})
     */
    private $authors;
    
    /**
     * Series
     * @var BookSeries[]|Collection
     * @ManyToOne(targetEntity="BookSeries", mappedBy="book", cascade={"persist", "remove"})
     */
    private $series;
    
    /**
     * Book constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->authors = new ArrayCollection();
        $this->series = new ArrayCollection();
    }
    
    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }
    
    /**
     * Sets book id
     * @param int $id
     * @return Book
     */
    public function setId(int $id)
    {
        $this->id = $id;
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
     * Gets series
     * @return BookSeries[]|ArrayCollection
     */
    public function getSeries()
    {
        $bookSeries = [];
        if ($this->series) {
            foreach ($this->series as $series) {
                $bookSeries[] = $series->getSeries();
            }
        }
        
        return new ArrayCollection($bookSeries);
    }
    
    /**
     * Adds series to book series
     * @param Series $series
     * @param int $number
     * @return Book
     */
    public function addSeries(Series $series, int $number) : Book
    {
        $this->series->add(new BookSeries($this, $series, $number));
        return $this;
    }
    
    /**
     * Gets array representation of object
     * @return array
     */
    public function toArray()
    {
        $authors = [];
        foreach ($this->authors as $author) {
            $authors[] = $author->getAuthor()->toArray();
        }
        
        $series = [];
        foreach ($this->series as $s) {
            $series[] = $s->toArray();
        }
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'authors' => $authors,
            'genres' => implode(", ", $this->genres),
            'series' => $series
        ];
    }
}
