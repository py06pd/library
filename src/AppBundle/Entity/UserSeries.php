<?php
/** src/AppBundle/Entity/UserSeries.php */
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserSeries entity
 * @ORM\Entity
 * @ORM\Table(name="userseries")
 */
class UserSeries
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     */
    private $seriesId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="userid")
     */
    private $userId;

    /**
     * UserSeries constructor.
     * @param int $seriesId
     * @param int $userId
     */
    public function __construct(int $seriesId, int $userId)
    {
        $this->seriesId = $seriesId;
        $this->userId = $userId;
    }

    /**
     * Gets series id
     * @return int
     */
    public function getSeriesId() : int
    {
        return $this->seriesId;
    }
}
