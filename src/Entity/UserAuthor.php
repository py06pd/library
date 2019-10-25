<?php
/** src/App/Entity/UserAuthor.php */
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * UserAuthor entity
 * @ORM\Entity
 * @ORM\Table(name="userauthor")
 */
class UserAuthor
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="id")
     */
    private $authorId;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="userid")
     */
    private $userId;

    /**
     * UserAuthor constructor.
     * @param int $authorId
     * @param int $userId
     */
    public function __construct(int $authorId, int $userId)
    {
        $this->authorId = $authorId;
        $this->userId = $userId;
    }

    /**
     * Gets author id
     * @return int
     */
    public function getAuthorId() : int
    {
        return $this->authorId;
    }
}
