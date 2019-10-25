<?php
/** src/App/Repositories/UserRepository */
namespace App\Repositories;

use App\Entity\User;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * UserRepository class
 */
class UserRepository extends EntityRepository
{
    /**
     * Gets user by id
     * @param int $userId
     * @return User|null
     */
    public function getUserById(int $userId)
    {
        $qb = $this->_em->createQueryBuilder();
        
        $user = $this->getBaseQuery()
            ->where($qb->expr()->eq('u.userId', $userId))
            ->getQuery()
            ->getResult();
        
        if ($user) {
            return $user[0];
        }
        
        return null;
    }
    
    /**
     * Gets base query
     * @return QueryBuilder
     */
    private function getBaseQuery()
    {
        $qb = $this->_em->createQueryBuilder();
        return $qb->select('u', 'ug', 'g', 'gu')
            ->from(User::class, 'u')
            ->leftJoin('u.groups', 'ug')
            ->leftJoin('ug.group', 'g')
            ->leftJoin('g.users', 'gu');
    }
}
