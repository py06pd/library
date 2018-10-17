<?php

namespace AppBundle\Services;

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
     * @var EntityManager
     */
    private $em;
    
    /**
     * @var User
     */
    private $user;
    
    /**
     * @param EntityManager $em
     * @param TokenStorage $tokenStorage
     */
    public function __construct($em, $tokenStorage)
    {
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
        
        $audit = new Audit($this->user, time(), $id, $name, $description, $data);

        try {
            $this->em->persist($audit);
            $this->em->flush();
        } catch (Exception $e) {
            return false;
        }

        return true;
    }
    
    public function userBookLog($book, $user, $details)
    {
        $changes = [];
        foreach ($details as $detail) {
            if ($detail[0] != $detail[1]) {
                $changes[] = $detail;
            }
        }
        
        $this->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' updated for user '<log.details.user.name>'",
            array(
                'user' => array('id' => $user->id, 'name' => $user->name),
                'changes' => $changes
            )
        );
    }
}
