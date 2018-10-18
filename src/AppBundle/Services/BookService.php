<?php
/** src/AppBundle/Services/BookService.php */
namespace AppBundle\Services;

use AppBundle\Entity\Book;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\User;
use AppBundle\Repositories\BookRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class BookService
 * @package AppBundle\Services
 */
class BookService
{
    /**
     * @var Auditor
     */
    private $auditor;
    
    /**
     * @var EntityManager
     */
    private $em;
    
     /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * BookService constructor.
     * @param EntityManager $em
     * @param Auditor $auditor
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, Auditor $auditor, LoggerInterface $logger)
    {
        $this->auditor = $auditor;
        $this->em = $em;
        $this->logger = $logger;
    }
    
    /**
     * Search for books
     * @param int $total
     * @param array $filters
     * @param int $first
     * @return Book[]
     */
    public function search(int &$total = null, array $filters = [], int $first = 0)
    {
        $eq = $neq = $like = [];
        if (count($filters) > 0) {
            $eq = $this->parseFilters($filters, 'equals');
            $neq = $this->parseFilters($filters, 'does not equal');

            foreach ($filters as $filter) {
                if ($filter->operator == 'like') {
                    $like[] = $filter->value[0];
                }
            }
        }

        /** @var BookRepository $repo */
        $repo = $this->em->getRepository(Book::class);

        $books = [];
        $total = $repo->getSearchResultCount($eq, $neq, $like);
        if ($total > 0) {
            $allBookIds = $repo->getSearchResults($eq, $neq, $like);
            $bookIds = array_slice(array_unique($allBookIds), $first, 15);
            $books = $repo->getBooksById($bookIds);
        }
        
        return $books;
    }
    
    public function borrow($id, $userId, $userIds)
    {
        if (!in_array($userId, $userIds)) {
            return "Invalid request";
        }
        
        $item = $this->em->getRepository(BookEntity::class)->findOneBy(array('id' => $id));
        if (!$item) {
            return "Invalid request";
        }
        
        $user = $this->em->getRepository(User::class)->findOneBy(array('id' => $userId));
        if (!$user) {
            return "Invalid request";
        }
        
        $userbook = $this->em->getRepository(UserBook::class)->findOneBy(array('id' => $id, 'userid' => $userId));
        if ($userbook) {
            if ($userbook->owned) {
                return "You own this";
            }

            if ($userbook->borrowedfromid != 0) {
                return "You are already borrowing this";
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $id;
            $userbook->userid = $userId;
            $this->em->persist($userbook);
        }
        
        if ($userbook->requestedfromid != 0) {
            $userbook->borrowedfromid = $userbook->requestedfromid;
        } else {
            $history = $this->em->getRepository(UserBook::class)->findBy(array(
                'id' => $item->getId(),
                'userid' => $userIds
            ));

            $total = array();
            foreach ($history as $record) {
                if ($record->owned) {
                    if (!isset($total[$record->userid])) {
                        $total[$record->userid] = $record->stock;
                    } else {
                        $total[$record->userid] += $record->stock;
                    }
                } elseif ($record->borrowedfromid != 0) {
                    if (!isset($total[$record->borrowedfromid])) {
                        $total[$record->borrowedfromid] = -1;
                    } else {
                        $total[$record->borrowedfromid] -= 1;
                    }
                } elseif ($record->requestedfromid != 0) {
                    if (!isset($total[$record->requestedfromid])) {
                        $total[$record->requestedfromid] = -1;
                    } else {
                        $total[$record->requestedfromid] -= 1;
                    }
                }
            }

            if (array_sum($total) <= 0) {
                return "None available to borrow";
            }

            foreach ($total as $ownerId => $stock) {
                if ($stock > 0) {
                    $userbook->borrowedfromid = $ownerId;
                    break;
                }
            }
        }
        
        $oldid = $userbook->requestedfromid;
        $oldtime = $userbook->requestedtime;
        $userbook->requestedfromid = 0;
        $userbook->requestedtime = null;
        $userbook->borrowedtime = time();
        
        $this->em->flush();
        
        $this->auditor->userBookLog($item, $user, array(
            'requestedfromid' => array($oldid, 0),
            'requestedtime' => array($oldtime, null),
            'borrowedfromid' => array(0, $userbook->borrowedfromid),
            'borrowedtime' => array(null, $userbook->borrowedtime)
        ));
        
        return true;
    }
    
    /**
     * @param array $ids
     * @return type
     */
    public function delete($ids)
    {
        $books = $this->em->getRepository(BookEntity::class)->findBy(array('id' => $ids));
        if (count($books) != count($ids)) {
            return false;
        }
        
        $history = $this->em->getRepository(UserBook::class)->findBy(array('id' => $ids));
        
        foreach ($books as $item) {
            $this->em->remove($item);
            $this->auditor->log($item->getId(), $item->getName(), "book '<log.itemname>' deleted");
        }
        
        foreach ($history as $item) {
            $this->em->remove($item);
        }
        
        $this->em->flush();
        
        return true;
    }
    
    public function getLending($userid)
    {
        $qb = $this->em->createQueryBuilder();

        $and1 = $qb->expr()->andX();
        $and1->add($qb->expr()->eq('ub.userid', $userid));
        $and1->add($qb->expr()->neq('ub.borrowedfromid', 0));
        
        $and2 = $qb->expr()->andX();
        $and2->add($qb->expr()->eq('ub.userid', $userid));
        $and2->add($qb->expr()->neq('ub.requestedfromid', 0));
        
        $or = $qb->expr()->orX();
        $or->add($and1);
        $or->add($and2);
        $or->add($qb->expr()->eq('ub.requestedfromid', $userid));
        $or->add($qb->expr()->eq('ub.borrowedfromid', $userid));
        
        $q = $qb->select(
            'b.id',
            'b.name',
            'ub.userid',
            'ub.requestedfromid',
            'ub.borrowedfromid',
            'ub.requestedtime',
            'ub.borrowedtime'
        )
        ->from(UserBook::class, 'ub')
        ->join(BookEntity::class, 'b', 'WITH', 'ub.id = b.id')
        ->where($or)
        ->getQuery();

        return $q->getResult();
    }
    
    public function request($id, $userId, $userIds)
    {
        if (!in_array($userId, $userIds)) {
            return "Invalid request";
        }
        
        $item = $this->em->getRepository(BookEntity::class)->findOneBy(array('id' => $id));
        if (!$item) {
            return "Invalid request";
        }
        
        $user = $this->em->getRepository(User::class)->findOneBy(array('id' => $userId));
        if (!$user) {
            return "Invalid request";
        }
        
        $userbook = $this->em->getRepository(UserBook::class)->findOneBy(array('id' => $id, 'userid' => $userId));
        
        if ($userbook) {
            if ($userbook->owned) {
                return "You own this";
            }

            if ($userbook->borrowedfromid != 0) {
                return "You are already borrowing this";
            }

            if ($userbook->requestedfromid != 0) {
                return "You have already requested this";
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $id;
            $userbook->userid = $userId;
            $this->em->persist($userbook);
        }
        
        $history = $this->em->getRepository(UserBook::class)->findBy(array('id' => $id, 'userid' => $userIds));
        
        $total = array();
        foreach ($history as $record) {
            if ($record->owned) {
                if (!isset($total[$record->userid])) {
                    $total[$record->userid] = $record->stock;
                } else {
                    $total[$record->userid] += $record->stock;
                }
            } elseif ($record->borrowedfromid != 0) {
                if (!isset($total[$record->borrowedfromid])) {
                    $total[$record->borrowedfromid] = -1;
                } else {
                    $total[$record->borrowedfromid] -= 1;
                }
            } elseif ($record->requestedfromid != 0) {
                if (!isset($total[$record->requestedfromid])) {
                    $total[$record->requestedfromid] = -1;
                } else {
                    $total[$record->requestedfromid] -= 1;
                }
            }
        }
        
        if (array_sum($total) <= 0) {
            return "None available to borrow";
        }
        
        foreach ($total as $ownerId => $stock) {
            if ($stock > 0) {
                $userbook->requestedfromid = $ownerId;
                break;
            }
        }
        
        $userbook->requestedtime = time();
        
        $this->em->flush();
        
        $this->auditor->userBookLog($item, $user, array(
            'requestedfromid' => array(0, $userbook->requestedfromid),
            'requestedtime' => array(null, $userbook->requestedtime)
        ));
        
        return true;
    }
    
    /**
     * Save book
     * @param Book $newBook
     * @return bool
     */
    public function save(Book $newBook)
    {
        $bookId = $newBook->getId();
        if ($bookId) {
            /** @var BookRepository $bookRepo */
            $bookRepo = $this->em->getRepository(Book::class);

            $book = $bookRepo->getBookById($bookId);
            if (!$book) {
                return false;
            }
            
            $oldBook = $book->jsonSerialize();
            foreach ($book->getAuthors() as $author) {
                if (!$newBook->hasAuthor($author)) {
                    $book->removeAuthor($author);
                }
            }
            
            foreach ($book->getSeries() as $series) {
                if (!$newBook->inSeries($series->getSeries())) {
                    $book->removeSeries($series->getSeries());
                }
            }
            
            $book->setName($newBook->getName());
            $book->setType($newBook->getType());
            $book->setGenres($newBook->getGenres());
            foreach ($newBook->getAuthors() as $author) {
                if (!$book->hasAuthor($author)) {
                    $book->addAuthor($author);
                }
            }
            
            foreach ($newBook->getSeries() as $series) {
                if ($book->inSeries($series->getSeries())) {
                    $book->getSeriesById($series->getSeries()->getId())->setNumber($series->getNumber());
                } else {
                    $book->addSeries($series->getSeries(), $series->getNumber());
                }
            }

            foreach ($newBook->getUsers() as $user) {
                if ($book->getUserById($user->getUser()->getId())) {
                    $book->getUserById($user->getUser()->getId())
                        ->setOwned($user->isOwned())
                        ->setRead($user->isRead())
                        ->setWishlist($user->onWishlist());
                } else {
                    $book->addUser($user);
                }
            }
        } else {
            $book = $newBook;
            try {
                $this->em->persist($book);
            } catch (Exception $e) {
                $this->logger->error($e);
                return false;
            }
        }
        
        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }
        
        if ($bookId) {
            $bookArray = $book->jsonSerialize();
            
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' updated", ['changes' => [
                'name' => [$oldBook['name'], $book->getName()],
                'type' => [$oldBook['type'], $book->getType()],
                'authors' => [$oldBook['authors'], $bookArray['authors']],
                'genres' => [$oldBook['genres'], $book->getGenres()],
                'series' => [$oldBook['series'], $bookArray['series']],
                'users' => [$oldBook['users'], $bookArray['users']]
            ]]);
        } else {
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' created");
        }
        
        return true;
    }
    
    private function parseFilters($filters, $op)
    {
        $map = [
            'author' => 'a{nonce}.authorId',
            'genre' => 'b{nonce}.genres',
            'owner' => 'bu{nonce}.owned',
            'read' => 'bu{nonce}.read',
            'series' => 's{nonce}.seriesId',
            'type' => 'b{nonce}.type',
            'wishlist' => 'bu{nonce}.wishlist',
        ];

        $query = [];
        foreach ($filters as $filter) {
            if ($filter->operator == $op) {
                $query[] = [$map[$filter->field], $filter->value];
            }
        }
        
        return $query;
    }
}
