<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="authors")
 */
class Author
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
    
    private $books = array();
    
    /**
     * Author constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->setName($name);
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
     * @return Book
     */
    private function setName(string $name)
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
    public function toArray()
    {
        return [
            'id' => $this->id,
            'forename' => $this->forename,
            'surname' => $this->surname
        ];
    }
}
