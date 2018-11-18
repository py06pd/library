<?php
/** src/AppBundle/Services/Auditor.php */
namespace AppBundle\Services;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\Audit;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

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
     * @param EntityManager   $em
     * @param DateTimeFactory $dateTime
     * @param TokenStorage    $tokenStorage
     */
    public function __construct(EntityManager $em, DateTimeFactory $dateTime, TokenStorage $tokenStorage)
    {
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
