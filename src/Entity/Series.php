<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="series")
 */
class Series implements JsonSerializable
{
    /**
     * @ORM\Column(type="integer", name="id")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     */
    private $seriesId;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $name;
    
    /**
     * @ORM\Column(type="string", length=16)
     */
    private $type;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $series;
    
    private $books = array();
    
    /**
     * Series constructor.
     * @param string $name
     * @param string $type
     */
    public function __construct(string $name, string $type = "sequence")
    {
        $this->name = $name;
        $this->type = $type;
    }
    
    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->seriesId;
    }

    /**
     * Sets series id
     * @param int $seriesId
     * @return Series
     */
    public function setId(int $seriesId) : Series
    {
        $this->seriesId = $seriesId;
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
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'seriesId' => $this->getId(),
            'name' => $this->getName()
        ];
    }
}
