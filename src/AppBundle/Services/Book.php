<?php

namespace AppBundle\Services;

use AppBundle\Entity\Book as BookEntity;
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
    public $authors;
    
    /**
     * @var array
     */
    public $genres;
    
    /**
     * @var array
     */
    public $series;
    
    /**
     * @var array
     */
    public $owners;
    
    /**
     * @var array
     */
    public $read;
    
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
    
    public function borrow($id, $userId)
    {
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
            $history = $this->em->getRepository(UserBook::class)->findBy(array('id' => $item->id));

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
            $this->auditor->log($item->id, $item->name, "book '<log.itemname>' deleted");
        }
        
        foreach ($history as $item) {
            $this->em->remove($item);
        }
        
        $this->em->flush();
        
        return true;
    }
    
    public function get($id)
    {
        $item = $this->em->getRepository(BookEntity::class)->findOneBy(array('id' => $id));
        
        $owned = $read = array();
        
        $history = $this->em->getRepository(UserBook::class)->findBy(array('id' => $id));
        
        foreach ($history as $row) {
            if ($row->owned) {
                $owned[] = $row->userid;
            }
            
            if ($row->read) {
                $read[] = $row->userid;
            }
        }
        
        $this->id = $item->id;
        $this->name = $item->name;
        $this->type = $item->type;
        $this->authors = $item->authors;
        $this->genres = $item->genres;
        $this->series = $item->series;
        $this->owners = $owned;
        $this->read = $read;
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
    
    public function request($id, $userId)
    {
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
        
        $history = $this->em->getRepository(UserBook::class)->findBy(array('id' => $id));
        
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
    
    public function save()
    {
        if ($this->id == -1) {
            $item = new BookEntity();
        } else {
            $item = $this->em->getRepository(BookEntity::class)->findOneBy(array('id' => $this->id));
        }
        
        $oldname = $item->name;
        $oldtype = $item->type;
        $oldauthors = $item->authors;
        $oldgenres = $item->genres;
        $oldseries = $item->series;
        
        $item->name = $this->name;
        $item->type = $this->type;
        $item->authors = $this->authors;
        $item->genres = $this->genres;
        $item->series = $this->series;
        
        if ($this->id == -1) {
            $this->em->persist($item);
        }
        
        $this->em->flush();
        
        if ($this->id == -1) {
            $this->auditor->log($item->id, $item->name, "book '<log.itemname>' added");
        } else {
            $this->auditor->log($item->id, $item->name, "book '<log.itemname>' updated", array('changes' => array(
                'name' => array($oldname, $item->name),
                'type' => array($oldtype, $item->type),
                'authors' => array($oldauthors, $item->authors),
                'genres' => array($oldgenres, $item->genres),
                'series' => array($oldseries, $item->series)
            )));
        }
        
        return true;
    }
}
