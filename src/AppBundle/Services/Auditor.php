<?php

namespace AppBundle\Services;

use AppBundle\Entity\Audit;

class Auditor
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    
    /**
     * @var \AppBundle\Entity\User
     */
    private $user;
    
    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface $tokenStorage
     */
    public function __construct($em, $tokenStorage)
    {
        $this->em = $em;
        $this->user = $tokenStorage->getToken()->getUser();
    }
    
    public function log($id, $name, $description, $data = array())
    {
        if (isset($data['changes'])) {
            $checked = array();
            foreach ($data['changes'] as $field => $values) {
                if ($values[0] != $values[1]) {
                    $checked[$field] = $values;
                }
            }
            
            $data['changes'] = $checked;
        }
        
        $audit = new Audit();
        $audit->userid = $this->user ? $this->user->id : 0;
        $audit->timestamp = time();
        $audit->itemid = $id;
        $audit->itemname = $name;
        $audit->description = $description;
        $audit->details = $data;
        
        $this->em->persist($audit);
        
        $this->em->flush();
    }
    
    public function userBookLog($book, $user, $changes)
    {
        $this->log(
            $book->id,
            $book->name,
            "book '<log.itemname>' updated for user '<log.details.user.name>'",
            array(
                'user' => array('id' => $user->id, 'name' => $user->name),
                'changes' => $changes
            )
        );
    }
}
