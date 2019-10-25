<?php
/** src/App/Entity/Audit.php */
namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="audit")
 */
class Audit
{
    /**
     * Log id
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="SEQUENCE")
     * @ORM\Column(type="integer", name="logid")
     */
    private $logId;

    /**
     * User
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="userid", referencedColumnName="id")
     */
    private $user;

    /**
     * @ORM\Column(type="integer", name="timestamp")
     */
    private $timestamp;

    /**
     * @ORM\Column(type="integer", name="itemid", nullable=true)
     */
    private $itemId;

    /**
     * @ORM\Column(type="string", name="itemname", length=256, nullable=true)
     */
    private $itemName;
    
    /**
     * @ORM\Column(type="string", name="description", length=256)
     */
    private $description;
    
    /**
     * @ORM\Column(type="json_array", name="details", length=1024, nullable=true)
     */
    private $details;

    /**
     * Audit constructor.
     * @param User     $user
     * @param DateTime $dateTime
     * @param int      $itemId
     * @param string   $itemName
     * @param string   $description
     * @param array    $details
     */
    public function __construct(
        User $user,
        DateTime $dateTime,
        int $itemId,
        string $itemName,
        string $description,
        array $details
    ) {
        $this->user = $user;
        $this->timestamp = $dateTime->getTimestamp();
        $this->itemId = $itemId;
        $this->itemName = $itemName;
        $this->description = $description;
        $this->details = $details;
    }
}
