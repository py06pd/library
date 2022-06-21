<?php
/** src/App/Repository/BookRepository */
namespace App\Repository;

use App\Entity\Book;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Exception;

/**
 * BookRepository class
 */
class BookRepository extends EntityRepository
{
    /**
     * Gets book by id
     * @param int $bookId
     * @return Book
     */
    public function getBookById(int $bookId)
    {
        $qb = $this->_em->createQueryBuilder();

        try {
            $book = $this->getBaseQuery()
                ->where($qb->expr()->eq('b.bookId', $bookId))
                ->getQuery()
                ->getSingleResult();
        } catch (Exception $e) {
            return null;
        }

        return $book;
    }

    /**
     * Gets books by id
     * @param array $bookIds
     * @return Book[]
     */
    public function getBooksById(array $bookIds)
    {
        $qb = $this->_em->createQueryBuilder();

        try {
            $books = $this->getBaseQuery()
                ->where($qb->expr()->in('b.bookId', $bookIds))
                ->addOrderBy('a.name', 'ASC')
                ->addOrderBy('a.forename', 'ASC')
                ->addOrderBy('s.name', 'ASC')
                ->addOrderBy('bs.number', 'ASC')
                ->getQuery()
                ->getResult();
        } catch (Exception $e) {
            return null;
        }

        return $books;
    }

    /**
     * Get books requested or borrowed from or by user
     * @param int $userId
     * @return Book[]
     */
    public function getLending(int $userId)
    {
        $qb = $this->_em->createQueryBuilder();

        /** @var Book[] $books */
        $books = $this->getBaseQuery()
            ->where($qb->expr()->orX(
                $qb->expr()->andX(
                    $qb->expr()->eq('u.userId', $userId),
                    $qb->expr()->isNotNull('bf.userId')
                ),
                $qb->expr()->andX(
                    $qb->expr()->eq('u.userId', $userId),
                    $qb->expr()->isNotNull('rf.userId')
                ),
                $qb->expr()->eq('rf.userId', $userId),
                $qb->expr()->eq('bf.userId', $userId)
            ))
            ->getQuery()
            ->getResult();

        return $books;
    }

    /**
     * Gets search result total count
     * @param array $eq
     * @param array $neq
     * @param array $like
     * @return int
     */
    public function getSearchResultCount(array $eq = [], array $neq = [], array $like = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('COUNT(b.bookId)');
        $this->buildSearchQuery($qb, $eq, $neq, $like);
        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Gets search results
     * @param array $eq
     * @param array $neq
     * @param array $like
     * @return array
     */
    public function getSearchResults(array $eq = [], array $neq = [], array $like = [])
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('b.bookId');
        $this->buildSearchQuery($qb, $eq, $neq, $like);
        $result = $qb->addOrderBy('a.name', 'ASC')
            ->addOrderBy('a.forename', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->addOrderBy('bs.number', 'ASC')
            ->getQuery()
            ->getResult();

        return array_column($result, "bookId");
    }
    
    /**
     * Gets base query for search
     * @param string $nonce
     * @return QueryBuilder
     */
    public function getSearchBaseQuery(string $nonce) : QueryBuilder
    {
        $qb = $this->_em->createQueryBuilder();
        return $qb->select('b' . $nonce . '.bookId')
            ->from(Book::class, 'b' . $nonce)
            ->leftJoin('b' . $nonce . '.authors', 'ba' . $nonce)
            ->leftJoin('ba' . $nonce . '.author', 'a' . $nonce)
            ->leftJoin('b' . $nonce . '.genres', 'bg' . $nonce)
            ->leftJoin('bg' . $nonce . '.genre', 'g' . $nonce)
            ->leftJoin('b' . $nonce . '.series', 'bs' . $nonce)
            ->leftJoin('bs' . $nonce . '.series', 's' . $nonce)
            ->leftJoin('b' . $nonce . '.type', 't' . $nonce)
            ->leftJoin('b' . $nonce . '.users', 'bu' . $nonce)
            ->leftJoin('bu' . $nonce . '.user', 'u' . $nonce);
    }
    
    /**
     * Build search query
     * @param QueryBuilder $qb
     * @param array $eq
     * @param array $neq
     * @param array $like
     */
    private function buildSearchQuery(QueryBuilder $qb, array $eq = [], array $neq = [], array $like = [])
    {
        $qb->from(Book::class, 'b')
            ->leftJoin('b.authors', 'ba')
            ->leftJoin('ba.author', 'a')
            ->leftJoin('b.genres', 'bg')
            ->leftJoin('bg.genre', 'g')
            ->leftJoin('b.series', 'bs')
            ->leftJoin('bs.series', 's')
            ->leftJoin('b.type', 't')
            ->leftJoin('b.users', 'bu')
            ->leftJoin('bu.user', 'u');

        $this->buildSearchSubQuery($qb, '', $qb, $eq);
        foreach ($like as $index => $value) {
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->like('b.name', ':like' . $index),
                $qb->expr()->like("CONCAT(a.forename, ' ', a.surname)", ':like' . $index)
            ));
            $qb->setParameter(':like' . $index, '%' . $value . '%');
        }

        if (count($neq) > 0) {
            $nqb = $this->getSearchBaseQuery('_neq');
            $this->buildSearchSubQuery($nqb, '_neq', $qb, $neq);
            $qb->andWhere($qb->expr()->notIn('b.bookId', $nqb->getDQL()));
        }
    }

    /**
     * Build search sub-query
     * @param QueryBuilder $qb
     * @param string $nonce
     * @param QueryBuilder $bqb
     * @param array $conditions
     */
    private function buildSearchSubQuery(QueryBuilder $qb, string $nonce, QueryBuilder $bqb, array $conditions = [])
    {
        if (count($conditions) > 0) {
            foreach ($conditions as $key => $condition) {
                if (in_array($condition[0], ['bu{nonce}.owned', 'bu{nonce}.read', 'bu{nonce}.wishlist'])) {
                    $userNonce = $nonce . '_' . substr($condition[0], 10);
                    $buqb = $this->_em->createQueryBuilder();
                    $buqb->select('b' . $userNonce . '.bookId')
                        ->from(Book::class, 'b' . $userNonce)
                        ->join('b' . $userNonce . '.users', 'bu' . $userNonce)
                        ->join('bu' . $userNonce . '.user', 'u' . $userNonce);
                    if (count($condition[1]) == 1) {
                        $buqb->andWhere($buqb->expr()->eq('u' . $userNonce . '.userId', $condition[1][0]));
                    } else {
                        $buqb->andWhere($buqb->expr()->in('u' . $userNonce . '.userId', $condition[1]));
                    }
                    $buqb->andWhere($qb->expr()->eq(str_replace('{nonce}', $userNonce, $condition[0]), ':true'));
                    $bqb->setParameter('true', true);
                    $qb->andWhere($qb->expr()->in('b' . $nonce . '.bookId', $buqb->getDQL()));
                } elseif (count($condition[1]) == 1) {
                    $qb->andWhere($qb->expr()->eq(str_replace('{nonce}', $nonce, $condition[0]), $condition[1][0]));
                } else {
                    $qb->andWhere($qb->expr()->in(str_replace('{nonce}', $nonce, $condition[0]), $condition[1]));
                }
            }
        }
    }

    /**
     * Gets base query
     * @return QueryBuilder
     */
    private function getBaseQuery()
    {
        $qb = $this->_em->createQueryBuilder();
        return $qb->select('b', 'ba', 'a', 'bg', 'g', 'bs', 's', 't', 'bu', 'u', 'rf', 'bf')
            ->from(Book::class, 'b')
            ->leftJoin('b.authors', 'ba')
            ->leftJoin('ba.author', 'a')
            ->leftJoin('b.genres', 'bg')
            ->leftJoin('bg.genre', 'g')
            ->leftJoin('b.series', 'bs')
            ->leftJoin('bs.series', 's')
            ->leftJoin('b.type', 't')
            ->leftJoin('b.users', 'bu')
            ->leftJoin('bu.user', 'u')
            ->leftJoin('bu.requestedFrom', 'rf')
            ->leftJoin('bu.borrowedFrom', 'bf');
    }
}
