<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="book_genre")
 */
class BookGenre
{
    /**
     * Book
     * @var Book
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="genres")
     * @ORM\JoinColumn(name="book_id", referencedColumnName="id")
     */
    private $book;

    /**
     * Genre
     * @var Genre
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Genre", cascade={"persist"})
     * @ORM\JoinColumn(name="genre_id", referencedColumnName="genre_id")
     */
    private $genre;

    /**
     * BookGenre constructor.
     * @param Book $book
     * @param Genre $genre
     */
    public function __construct(Book $book, Genre $genre)
    {
        $this->book = $book;
        $this->genre = $genre;
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
     * Gets genre
     * @return Genre
     */
    public function getGenre()
    {
        return $this->genre;
    }
}
