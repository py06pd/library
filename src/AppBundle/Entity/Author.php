<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Entity
 * @ORM\Table(name="authors")
 */
class Author implements JsonSerializable
{
    /**
     * Author id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="id")
     */
    private $authorId;

    /**
     * @ORM\Column(type="string", length=256)
     */
    private $name;
    
    /**
     * @ORM\Column(type="string", length=256)
     */
    private $forename;
    
    /**
     * @ORM\Column(type="string", length=256, nullable=true)
     */
    private $surname;
    
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $alias;
    
    private $books;
    
    /**
     * Author constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
        $this->books = new ArrayCollection();
    }
    
    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->authorId;
    }

    /**
     * Sets author id
     * @param int $authorId
     * @return Author
     */
    public function setId(int $authorId) : Author
    {
        $this->authorId = $authorId;
        return $this;
    }
    
    /**
     * Gets forename
     * @return string
     */
    public function getForename()
    {
        return $this->forename;
    }
    
    /**
     * Gets surname
     * @return string|null
     */
    public function getSurname()
    {
        return $this->surname;
    }
    
    /**
     * Sets author name
     * @param string $name
     * @return Author
     */
    private function setName(string $name) : Author
    {
        if (stripos($name, " ") !== false) {
            $this->forename = substr($name, 0, strripos($name, " "));
            $this->surname = substr($name, strripos($name, " ") + 1);
            $this->name = substr($name, strripos($name, " ") + 1);
        } else {
            $this->forename = $name;
            $this->name = $name;
        }
        
        return $this;
    }
    
    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'authorId' => $this->authorId,
            'forename' => $this->forename,
            'surname' => $this->surname
        ];
    }
}
