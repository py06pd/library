<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="groupuser")
 */
class GroupUser
{
    /**
     * User group
     * @var UserGroup
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="UserGroup", inversedBy="users")
     * @ORM\JoinColumn(name="id", referencedColumnName="group_id")
     */
    private $group;

    /**
     * User
     * @var User
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="User", inversedBy="groups")
     * @ORM\JoinColumn(name="userid", referencedColumnName="id")
     */
    private $user;

    /**
     * GroupUser constructor.
     * @param User $user
     * @param UserGroup $group
     */
    public function __construct($user, $group)
    {
        $this->group = $group;
        $this->user = $user;
    }

    /**
     * Gets user
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Gets group
     * @return UserGroup
     */
    public function getGroup()
    {
        return $this->group;
    }
}
