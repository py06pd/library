<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="types")
 */
class Type implements JsonSerializable
{
    /**
     * Type id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="type_id")
     */
    private $typeId;

    /**
     * Type name
     * @ORM\Column(type="string", name="name", length=256)
     */
    private $name;
    
    /**
     * Type constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
    }
    
    /**
     * Gets type id
     * @return int
     */
    public function getId()
    {
        return $this->typeId;
    }

    /**
     * Sets type id
     * @param int $typeId
     * @return Type
     */
    public function setId(int $typeId) : Type
    {
        $this->typeId = $typeId;
        return $this;
    }
    
    /**
     * Sets type name
     * @param string $name
     * @return Type
     */
    private function setName(string $name) : Type
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
            'typeId' => $this->typeId,
            'name' => $this->name
        ];
    }
}
