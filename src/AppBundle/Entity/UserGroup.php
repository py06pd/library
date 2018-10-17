<?php
/** src/AppBundle/Entity/UserGroup.php */
namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * UserGroup entity
 * @ORM\Entity
 * @ORM\Table(name="user_group")
 */
class UserGroup
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
     * @var User[]
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
     */
    private $users;
    
    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
    
    /**
     * Gets group users
     * @return User[]
     */
    public function getUsers()
    {
        return $this->users;
    }
    
    /**
     * Add user to group
     * @param User $user
     * @return UserGroup
     */
    public function addUser(User $user) : UserGroup
    {
        $this->users->add($user);
        return $this;
    }
}
