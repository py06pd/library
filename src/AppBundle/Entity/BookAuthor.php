<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="bookauthor")
 */
class BookAuthor
{
    /**
     * Book
     * @var Book
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Book", inversedBy="authors")
     * @ORM\JoinColumn(name="id", referencedColumnName="id")
     */
    private $book;

    /**
     * Author
     * @var Author
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Author", cascade={"persist"})
     * @ORM\JoinColumn(name="authorid", referencedColumnName="id")
     */
    private $author;
    
    /**
     * BookAuthor constructor.
     * @param Book $book
     * @param Author $author
     */
    public function __construct(Book $book, Author $author)
    {
        $this->book = $book;
        $this->author = $author;
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
     * Gets author
     * @return Author
     */
    public function getAuthor()
    {
        return $this->author;
    }
}
