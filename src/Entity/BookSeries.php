<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="bookseries")
 */
class BookSeries implements JsonSerializable
{
    /**
     * Book
     * @var Book
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="series")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $book;

    /**
     * Series
     * @var Series
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Series", cascade={"persist"})
     * @ORM\JoinColumn(name="seriesid", referencedColumnName="id")
     */
    private $series;
    
    /**
     * Number in series
     * @var int
     * @ORM\Column(type="integer", name="number", nullable=true)
     */
    private $number;
    
    /**
     * BookSeries constructor.
     * @param Book $book
     * @param Series $series
     * @param int $number
     */
    public function __construct(Book $book, Series $series, int $number = null)
    {
        $this->book = $book;
        $this->series = $series;
        $this->number = $number;
    }
    
    /**
     * Gets book
     * @return Book
     */
    public function getBook()
    {
        return $this->book;
    }
    
    /**
     * Gets series
     * @return Series
     */
    public function getSeries()
    {
        return $this->series;
    }
    
    /**
     * Gets number
     * @return int
     */
    public function getNumber()
    {
        return $this->number;
    }
    
    /**
     * Sets number
     * @param int $number
     * @return $this
     */
    public function setNumber(int $number = null)
    {
        $this->number = $number;
        return $this;
    }
    
    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'seriesId' => $this->series->getId(),
            'name' => $this->series->getName(),
            'number' => $this->number
        ];
    }
}
