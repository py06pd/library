<?php
/** src/AppBundle/Repositories/BookRepository */
namespace AppBundle\Repositories;

use AppBundle\Entity\Book;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * BookRepository class
 */
class BookRepository extends EntityRepository
{
    /**
     * Gets all books
     * @return ArrayCollection
     */
    public function getAll()
    {
        $result = $this->getBaseQuery()
            ->getQuery()
            ->getResult();
        
        return new ArrayCollection($result);
    }
    
    /**
     * Gets book by id
     * @return Book
     */
    public function getBookById($id)
    {
        $qb = $this->createQueryBuilder();
        
        return $this->getBaseQuery()
            ->where($qb->expr()->eq('b.id'), $id)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Gets books by author and/or series
     * @param int $authorId
     * @param int $seriesId
     * @return ArrayCollection
     */
    public function getBooksByAuthorAndSeries(int $authorId = null, int $seriesId = null) : ArrayCollection
    {
        $qb = $this->createQueryBuilder();
        
        $query = $this->getBaseQuery();
        if ($authorId > 0) {
            $query->addWhere($qb->expr()->eq('ba.authorid', $authorId));
        }
        
        if ($seriesId > 0) {
            $query->addWhere($qb->expr()->eq('ba.seriesid', $seriesId));
        }
        
        $result = $query->getQuery()->getResult();
        
        return new ArrayCollection($result);
    }
    
    /**
     * Gets base query
     * @return QueryBuilder
     */
    private function getBaseQuery()
    {
        $qb = $this->createQueryBuilder();
        return $qb->select(['b.*', 'ba.*', 'bs.*', 'a.*', 's.*'])
            ->from(Book::class)
            ->leftJoin('b.authors', 'ba')
            ->leftJoin('ba.author', 'a')
            ->leftJoin('b.series', 'bs')
            ->leftJoin('bs.series', 's');
    }
}
