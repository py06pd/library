<?php
/** src/AppBundle/Entity/UserGroup.php */
namespace AppBundle\Entity;

use JsonSerializable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserGroup entity
 * @ORM\Entity
 * @ORM\Table(name="user_group")
 */
class UserGroup implements JsonSerializable
{
    /**
     * Group id
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="group_id")
     */
    private $groupId;

    /**
     * Group name
     * @var string
     * @ORM\Column(type="string", name="group_name", length=64)
     */
    private $name;
    
    /**
     * Group users
     * @var GroupUser[]
     * @ORM\OneToMany(targetEntity="GroupUser", mappedBy="group", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private $users;

    /**
     * UserGroup constructor.
     * @param string $name
     */
    public function __construct(string $name)
    {
        $this->name = $name;
        $this->users = new ArrayCollection();
    }

    /**
     * Gets group id
     * @return int
     */
    public function getId()
    {
        return $this->groupId;
    }

    /**
     * Sets group id
     * @param int $groupId
     * @return UserGroup
     */
    public function setId(int $groupId) : UserGroup
    {
        $this->groupId = $groupId;
        return $this;
    }

    /**
     * Sets name
     * @param string $name
     * @return UserGroup
     */
    public function setName(string $name) : UserGroup
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Gets group user
     * @return GroupUser[]|ArrayCollection
     */
    public function getGroupUsers()
    {
        return $this->users;
    }

    /**
     * Remove user from group
     * @param GroupUser $user
     * @return UserGroup
     */
    public function removeUser(User $user) : UserGroup
    {
        foreach ($this->users as $groupUser) {
            if ($groupUser->getUser()->getId() === $user->getId()) {
                $this->users->removeElement($groupUser);
            }
        }

        return $this;
    }

    /**
     * Gets group users
     * @return User[]
     */
    public function getUsers()
    {
        $users = [];
        foreach ($this->users as $user) {
            $users[] = $user->getUser();
        }

        return $users;
    }
    
    /**
     * Add user to group
     * @param User $user
     * @return UserGroup
     */
    public function addUser(User $user) : UserGroup
    {
        $this->users->add(new GroupUser($user, $this));
        return $this;
    }

    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        $users = [];
        foreach ($this->users as $user) {
            $users[] = ['userId' => $user->getUser()->getId(), 'name' => $user->getUser()->getName()];
        }

        return [
            'groupId' => $this->groupId,
            'name' => $this->name,
            'users' => $users
        ];
    }
}
