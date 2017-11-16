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
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     */
    public $seriesid;
    
    /**
     * @ORM\Column(type="integer")
     */
    public $number;
}
