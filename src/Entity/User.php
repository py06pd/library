<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * User entity
 * @ORM\Entity(repositoryClass="App\Repositories\UserRepository")
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
     * User roles
     * @var array
     * @ORM\Column(type="json_array", length=256)
     */
    private $roles;
    
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
     * Groups
     * @var GroupUser[]|Collection
     * @ORM\OneToMany(targetEntity="GroupUser", mappedBy="user")
     */
    private $groups;

    /**
     * User constructor.
     * @param string $name
     * @param string $username
     * @param string $password
     */
    public function __construct(string $name = null, string $username = null, string $password = null)
    {
        $this->name = $name;
        $this->username = $username;
        $this->password = $password;
        $this->roles = ['ROLE_ANONYMOUS','ROLE_USER'];
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
     * Sets username
     * @param string $username
     * @return User
     */
    public function setUsername(string $username) : User
    {
        $this->username = $username;
        return $this;
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
        return $this->roles;
    }

    /**
     * Checks if user has role
     * @param string $role
     * @return bool
     */
    public function hasRole(string $role)
    {
        return in_array($role, $this->roles);
    }

    /**
     * Sets roles
     * @param array $roles
     * @return User
     */
    public function setRoles(array $roles) : User
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * Add mapped group
     * @param UserGroup $group
     * @return User
     */
    public function addGroup(UserGroup $group) : User
    {
        $this->groups->add(new GroupUser($this, $group));
        return $this;
    }

    /**
     * Get a user in one of this user's groups
     * @param int $userId
     * @return User|null
     */
    public function getGroupUser(int $userId)
    {
        foreach ($this->groups as $group) {
            foreach ($group->getGroup()->getUsers() as $user) {
                if ($user->getId() == $userId) {
                    return $user;
                }
            }
        }

        return null;
    }

    /**
     * Gets users in groups
     * @return User[]
     */
    public function getGroupUsers()
    {
        $users = [];
        foreach ($this->groups as $group) {
            foreach ($group->getGroup()->getUsers() as $user) {
                $users[] = $user;
            }
        }

        return $users;
    }

    /**
     * Gets user groups
     * @return GroupUser[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Checks if user is in given group
     * @param int $groupId
     * @return bool
     */
    public function inGroup(int $groupId) : bool
    {
        foreach ($this->groups as $group) {
            if ($group->getGroup()->getId() == $groupId) {
                return true;
            }
        }

        return false;
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
        $groups = [];
        foreach ($this->groups as $group) {
            $groups[] = $group->getGroup()->jsonSerialize();
        }

        return [
            'userId' => $this->getId(),
            'name' => $this->getName(),
            'username' => $this->getUsername(),
            'roles' => $this->roles,
            'groups' => $groups
        ];
    }
}
