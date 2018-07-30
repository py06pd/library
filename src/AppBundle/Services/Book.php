<?php

namespace AppBundle\Services;

use AppBundle\Entity\Author;
use AppBundle\Entity\Book as BookEntity;
use AppBundle\Entity\BookAuthor;
use AppBundle\Entity\BookSeries;
use AppBundle\Entity\Series;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\User;

class Book
{
    /**
     * @var integer
     */
    public $id;

    /**
     * @var string
     */
    public $name;
    
    /**
     * @var string
     */
    public $type;
    
    /**
     * @var array
     */
    public $authors = array();
    
    /**
     * @var array
     */
    public $genres;
    
    /**
     * @var array
     */
    public $series = array();
    
    /**
     * @var array
     */
    public $owners = array();
    
    /**
     * @var array
     */
    public $read = array();
    
    /**
     * @var \AppBundle\Services\Auditor
     */
    private $auditor;
    
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    
    /**
     * @param \Doctrine\ORM\EntityManager $em
     * @param \AppBundle\Services\Auditor $auditor
     */
    public function __construct($em, $auditor)
    {
        $this->auditor = $auditor;
        $this->em = $em;
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
     * @param BookEntity $book
     * @return bool
     */
    public function save(BookEntity $book)
    {
        $id = $book->getId();
        $this->em->persist($book);
        $this->em->flush($book);
        
        if ($id) {
            $existing = $this->em->getRepository(Book::class)->getBookById($id);
            $existingArray = $existing->toArray();
            $bookArray = $book->toArray();
            
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' updated", ['changes' => [
                'name' => [$existing->getName(), $book->getName()],
                'type' => [$existing->getType(), $book->getType()],
                'authors' => [$existingArray['authors'], $bookArray['authors']],
                'genres' => [$existing->getGenres(), $book->getGenres()],
                'series' => [$existingArray['series'], $bookArray['series']]
            ]]);
        } else {
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' created");
        }
        
        return true;
    }
}
