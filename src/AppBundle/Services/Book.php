<?php

namespace AppBundle\Services;

use AppBundle\Entity\Book as BookEntity;
use AppBundle\Entity\BookHistory;

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
     * @var \Doctrine\ORM\EntityManager 
     */
    private $em;
    
    /**
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em; 
    }
    
    public function borrow($id, $userId)
    {
        $item = $this->em->getRepository(BookEntity::class)
                         ->findOneBy(array('id' => $id));
        if (!$item) {
            return "Invalid request";
        }
        
        $userbook = $this->em->getRepository(BookHistory::class)
                            ->findOneBy(array(
                                'id' => $id,
                                'userid' => $userId,
                                'latest' => true
                            ));
        
        if ($userbook) {
            if ($userbook->isOwned()) {
                return "You own this";
            }

            if ($userbook->isBorrowed()) {
                return "You are already borrowing this";
            }
        }
        
        if ($userbook && $userbook->isRequested()) {
            $userbook->latest = false;
            
            $newRecord = $userbook->double()->borrow();

            $this->em->persist($newRecord);
        } else {
            $history = $this->em->getRepository(BookHistory::class)->findBy(array(
                'id' => $item->id,
                'latest' => true
            ));

            $total = array();
            foreach ($history as $record) {
                if ($record->isOwned()) {
                    if (!isset($total[$record->userid])) {
                        $total[$record->userid] = $record->stock;
                    } else {
                        $total[$record->userid] += $record->stock;
                    }
                } elseif ($record->isBorrowed() || $record->isRequested()) {
                    if (!isset($total[$record->otheruserid])) {
                        $total[$record->otheruserid] = -1;
                    } else {
                        $total[$record->otheruserid] -= 1;
                    }
                }
            }

            if (array_sum($total) <= 0) {
                return "None available to borrow";
            }

            foreach ($total as $ownerId => $stock) {
                if ($stock > 0) {
                    if ($userbook) {
                        $userbook->latest = false;
                        $newRecord = $userbook->double()->borrow($ownerId);
                    } else {
                        $newRecord = new BookHistory();
                        $newRecord->init($item->id, $userId)->borrow($ownerId);
                    }

                    $this->em->persist($newRecord);
                    break;
                }
            }
        }
        
        $this->em->flush();
        
        return true;
    }
    
    /**
     * @param array $ids
     * @return type
     */
    public function delete($ids)
    {               
        $books = $this->em->getRepository(BookEntity::class)
                          ->findBy(array('id' => $ids));
        
        if (count($books) != count($ids)) {
            return false;
        }
        
        $history = $this->em->getRepository(BookHistory::class)
                            ->findBy(array('id' => $ids));
        
        foreach ($books as $item) {
            $this->em->remove($item);
        }
        
        foreach ($history as $item) {
            $this->em->remove($item);
        }
        
        $this->em->flush();
                
        return true;
    }
    
    public function get($id)
    {
        $item = $this->em->getRepository(BookEntity::class)
                        ->findOneBy(array('id' => $id));
        
        $owned = $read = array();
        
        $history = $this->em->getRepository(BookHistory::class)->findBy(array(
            'id' => $id,
            'latest' => true
        ));
        
        foreach ($history as $row) {
            if ($row->isOwned()) {
                $owned[] = $row->userid;
            }
            
            if ($row->isRead()) {
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
    
    public function request($id, $userId)
    {
        $item = $this->em->getRepository(BookEntity::class)
                         ->findOneBy(array('id' => $id));
        if (!$item) {
            return "Invalid request";
        }
        
        $userbook = $this->em->getRepository(BookHistory::class)
                            ->findOneBy(array(
                                'id' => $id,
                                'userid' => $userId,
                                'latest' => true
                            ));
        
        if ($userbook) {
            if ($userbook->isOwned()) {
                return "You own this";
            }

            if ($userbook->isBorrowed()) {
                return "You are already borrowing this";
            } 

            if ($userbook->isRequested()) { 
                return "You have already requested this";
            }
        }
        
        $history = $this->em->getRepository(BookHistory::class)->findBy(array(
            'id' => $item->id,
            'latest' => true
        ));
        
        $total = array();
        foreach ($history as $record) {
            if ($record->isOwned()) {
                if (!isset($total[$record->userid])) {
                    $total[$record->userid] = $record->stock;
                } else {
                    $total[$record->userid] += $record->stock;
                }
            } elseif ($record->isBorrowed() || $record->isRequested()) {
                if (!isset($total[$record->otheruserid])) {
                    $total[$record->otheruserid] = -1;
                } else {
                    $total[$record->otheruserid] -= 1;
                }
            }
        }
        
        if (array_sum($total) <= 0) {
            return "None available to borrow";
        }
        
        foreach ($total as $ownerId => $stock) {
            if ($stock > 0) {
                if ($userbook) {
                    $userbook->latest = false;
                    $newRecord = $userbook->double()->request($ownerId);
                } else {
                    $newRecord = new BookHistory();
                    $newRecord->init($item->id, $userId)->request($ownerId);
                }
                
                $this->em->persist($newRecord);
                break;
            }
        }
        
        $this->em->flush();
        
        return true;
    }
    
    public function save()
    {
        if ($this->id == -1) {
            $item = new BookEntity();
        } else {
            $item = $this->em->getRepository(BookEntity::class)
                             ->findOneBy(array('id' => $this->id));
        }
        
        $item->name = $this->name;
        $item->type = $this->type;
        $item->authors = $this->authors;
        $item->genres = $this->genres;
        $item->series = $this->series;
        
        if ($this->id == -1) {
            $this->em->persist($item);
        }
        
        $this->em->flush();
        
        $users = array();
        
        $newRecords = array();
        
        if ($this->id != -1) {
            $userbooks = $this->em->getRepository(BookHistory::class)
                                  ->findBy(array(
                                      'id' => $item->id,
                                      'latest' => true
                                  ));
            
            foreach ($userbooks as $book) {
                $users[$book->userid] = $book;
                $newRecords[$book->userid] = $book->double();
                if (!in_array($book->userid, $this->owners)) {
                    $newRecords[$book->userid]->unown();
                }
                
                if (!in_array($book->userid, $this->read)) {
                    $newRecords[$book->userid]->unread();
                }
            }
        }
        
        foreach ($this->owners as $id) {
            if (!isset($newRecords[$id])) {
                $newRecords[$id] = new BookHistory();
                $newRecords[$id]->init($item->id, $id);
            }
            
            $newRecords[$id]->own();
        }
        
        foreach ($this->read as $id) {
            if (!isset($newRecords[$id])) {
                $newRecords[$id] = new BookHistory();
                $newRecords[$id]->init($item->id, $id);
            }
            
            $newRecords[$id]->read();
        }
        
        if (count($newRecords) > 0) {
            foreach ($newRecords as $id => $record) {
                if (isset($users[$id])) {
                    if ($users[$id]->status === $record->status) {
                        continue;
                    }
                    
                    $users[$id]->latest = false;
                }
                
                $this->em->persist($record);
            }
        }
        
        $this->em->flush();
        
        return true;
    }
}
