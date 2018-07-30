<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bookseries")
 */
class BookSeries
{
    /**
     * Book
     * @var Book
     * @ORM\Id
     * @ManyToOne(targetEntity="Book", inversedBy="series")
     * @JoinColumn(name="id", referencedColumnName="id")
     */
    private $book;

    /**
     * Series
     * @var Series
     * @ORM\Id
     * @OneToOne(targetEntity="Series")
     * @JoinColumn(name="seriesid", referencedColumnName="id")
     */
    private $series;
    
    /**
     * Number in series
     * @var int
     * @ORM\Column(type="integer")
     */
    private $number;
    
    /**
     * BookSeries constructor.
     * @param Book $book
     * @param Series $series
     * @param int $number
     */
    public function __construct(Book $book, Series $series, int $number)
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
     * Gets array representation of object
     * @return array
     */
    public function toArray()
    {
        return [
            'id' => $this->series->getId(),
            'name' => $this->series->getName(),
            'number' => $this->number
        ];
    }
}
