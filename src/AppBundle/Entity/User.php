<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User entity
 * @ORM\Entity(repositoryClass="AppBundle\Repositories\UserRepository")
 * @ORM\Table(name="users")
 */
class User implements JsonSerializable, UserInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="id")
     */
    private $userId;

    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    private $name;
    
    /**
     * @var string
     * @ORM\Column(type="string", length=16)
     */
    private $role;
    
    /**
     * @var string
     * @ORM\Column(type="string", length=32)
     */
    private $username;
    
    /**
     * @var string
     * @ORM\Column(type="string", length=256)
     */
    private $password;
    
    /**
     * @var string
     * @ORM\Column(type="string", name="sessionid", length=256)
     */
    private $sessionId;

    /**
     * Groups
     * @var UserGroup[]|Collection
     * @ORM\ManyToMany(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinTable(name="groupuser",
     *     joinColumns={@ORM\JoinColumn(name="userid", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="id", referencedColumnName="group_id")}
     * )
     */
    private $groups;
    
    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
    
    /**
     * Gets user id
     * @return int
     */
    public function getId()
    {
        return $this->userId;
    }
    
    /**
     * Sets user id
     * @param int $userId
     * @return User
     */
    public function setId(int $userId) : User
    {
        $this->userId = $userId;
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
     * Sets name
     * @param string $name
     * @return User
     */
    public function setName(string $name) : User
    {
        $this->name = $name;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSalt()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getPassword()
    {
        return $this->password;
    }
    
    /**
     * Sets password
     * @param string $password
     * @return User
     */
    public function setPassword(string $password) : User
    {
        $this->password = $password;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoles()
    {
        return [$this->role];
    }

    /**
     * Checks if user has role
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role)
    {
        return ($role == $this->role);
    }

    /**
     * Sets role
     * @param string $role
     * @return $this
     */
    public function setRole(string $role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Gets session id
     * @return string
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }
    
    /**
     * Sets session id
     * @param string $sessionId
     * @return User
     */
    public function setSessionId(string $sessionId) : User
    {
        $this->sessionId = $sessionId;
        return $this;
    }
    
    /**
     * Gets group users
     * @return User[]|Collection
     */
    public function getGroupUsers()
    {
        $groupUsers = new ArrayCollection();
        foreach ($this->groups as $group) {
            foreach ($group->getUsers() as $user) {
                if (!$groupUsers->containsKey($user->getId())) {
                    $groupUsers->set($user->getId(), $user);
                }
            }
        }
        
        return $groupUsers;
    }

    /**
     * Add mapped group
     * @param UserGroup $group
     * @return User
     */
    public function addGroup(UserGroup $group) : User
    {
        $this->groups->add($group);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function eraseCredentials()
    {
        return null;
    }
    
    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        $users = $this->getGroupUsers();
        $groupUsers = [];
        foreach ($users as $user) {
            $groupUsers[] = ['userId' => $user->getId(), 'name' => $user->getName()];
        }
        return [
            'userId' => $this->getId(),
            'name' => $this->getName(),
            'role' => $this->role,
            'groupUsers' => $groupUsers
        ];
    }
}
