<?php

namespace AppBundle\Services;

use AppBundle\Entity\GroupUser;

class Group
{
    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param \AppBundle\Services\Auditor $auditor
     */
    public function __construct($em, $auditor)
    {
        $this->auditor = $auditor;
        $this->em = $em;
    }
    
    public function getLinkedUsers($id)
    {
        $qb = $this->em->createQueryBuilder();

        $and1 = $qb->expr()->andX();
        $and1->add($qb->expr()->eq('g.userid', $id));
        
        $q = $qb->select('gu.userid')
            ->from(GroupUser::class, 'g')
            ->join(GroupUser::class, 'gu', 'WITH', 'g.id = gu.id')
            ->where($and1)
            ->getQuery();

        $users = array($id);
        $result = $q->getResult();
        if ($result) {
            foreach ($result as $row) {
                if (!in_array($row['userid'], $users)) {
                    $users[] = $row['userid'];
                }
            }
        }
        
        return $users;
    }
}
