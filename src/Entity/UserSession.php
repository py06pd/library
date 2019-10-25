<?php

namespace App\Entity;

use DateTime;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * UserSession entity
 * @ORM\Entity
 * @ORM\Table(name="user_sessions")
 */
class UserSession implements JsonSerializable
{
    /**
     * Session id
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", name="session_id", length=256)
     */
    private $sessionId;

    /**
     * User id
     * @var int
     * @ORM\Column(type="integer", name="user_id")
     */
    private $userId;

    /**
     * Created datetime
     * @var DateTime
     * @ORM\Column(type="datetime", name="created")
     */
    private $created;

    /**
     * Last accessed datetime
     * @var DateTime
     * @ORM\Column(type="datetime", name="last_accessed")
     */
    private $lastAccessed;
    
    /**
     * Disabled flag
     * @var bool
     * @ORM\Column(type="boolean", name="disabled")
     */
    private $disabled;
    
    /**
     * Disabled reason
     * @var int
     * @ORM\Column(type="integer", name="disabled_reason", nullable=true)
     */
    private $disabledReason;

    /**
     * Device name
     * @var string
     * @ORM\Column(type="string", name="device", length=256, nullable=true)
     */
    private $device;

    /**
     * UserSession constructor.
     * @param int      $userId
     * @param DateTime $created
     * @param string   $sessionId
     * @param string   $device
     */
    public function __construct(int $userId, DateTime $created, string $sessionId, string $device = null)
    {
        $this->userId = $userId;
        $this->created = $created;
        $this->lastAccessed = $created;
        $this->sessionId = $sessionId;
        $this->device = $device;
        $this->disabled = false;
    }

    /**
     * Gets session id
     * @return string
     */
    public function getSessionId() : string
    {
        return $this->sessionId;
    }

    /**
     * Gets created datetime
     * @return DateTime
     */
    public function getCreated() : DateTime
    {
        return $this->created;
    }

    /**
     * Gets user id
     * @return int
     */
    public function getUserId() : int
    {
        return $this->userId;
    }

    /**
     * Set last accessed datetime
     * @param DateTime $lastAccessed
     * @return UserSession
     */
    public function setLastAccessed(DateTime $lastAccessed) : UserSession
    {
        $this->lastAccessed = $lastAccessed;
        return $this;
    }

    /**
     * Gets array representation of object
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'device' => $this->device,
            'created' => $this->created->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s'),
            'lastAccessed' => $this->lastAccessed->setTimezone(new DateTimeZone('Europe/London'))->format('d/m/Y H:i:s')
        ];
    }
}
