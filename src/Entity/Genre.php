<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="genres")
 */
class Genre implements JsonSerializable
{
    /**
     * Genre id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="genre_id")
     */
    private $genreId;

    /**
     * Genre name
     * @ORM\Column(type="string", length=256)
     */
    private $name;
    
    /**
     * Genre constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }
    
    /**
     * Gets genre id
     * @return int
     */
    public function getId()
    {
        return $this->genreId;
    }

    /**
     * Sets genre id
     * @param int $genreId
     * @return Genre
     */
    public function setId(int $genreId) : Genre
    {
        $this->genreId = $genreId;
        return $this;
    }
    
    /**
     * Sets genre name
     * @param string $name
     * @return Genre
     */
    private function setName(string $name) : Genre
    {
        $this->name = $name;
        
        return $this;
    }
    
    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'genreId' => $this->genreId,
            'name' => $this->name
        ];
    }
}
