<?php
/** src/App/Services/Auditor.php */
namespace App\Services;

use App\DateTimeFactory;
use App\Entity\Audit;
use App\Entity\User;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class Auditor
 * @package AppBundle\Services
 */
class Auditor
{
    /**
     * DateTime factory
     * @var DateTimeFactory
     */
    private $dateTime;

    /**
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var User
     */
    private $user;

    /**
     * Auditor constructor.
     * @param EntityManagerInterface $em
     * @param DateTimeFactory        $dateTime
     * @param TokenStorageInterface  $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        DateTimeFactory $dateTime,
        TokenStorageInterface $tokenStorage
    ) {
        $this->dateTime = $dateTime;
        $this->em = $em;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Log feature update
     * @param int $id
     * @param string $name
     * @param string $description
     * @param array $data
     * @return bool
     */
    public function log(int $id, string $name, string $description, array $data = [])
    {
        if (isset($data['changes'])) {
            $checked = [];
            foreach ($data['changes'] as $field => $values) {
                if ($values[0] != $values[1]) {
                    $checked[$field] = $values;
                }
            }
            
            $data['changes'] = $checked;
        }
        
        $audit = new Audit($this->user, $this->dateTime->getNow(), $id, $name, $description, $data);

        $this->em->persist($audit);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
}
