<?php

namespace AppBundle\Services;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;

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
    
    public function history($id, $userId, $status, $old = null, $stock = 0, $otheruserid = null)
    {
        $history = new BookHistory();
        
        $history->id = $id;
        $history->userid = $userId;
        $history->timestamp = time();
        
        if ($old) {
            $history->status += $old->status + $status;
            $history->stock += $old->stock + $stock;
        } else {
            $history->status = $status;
            $history->stock = $stock;
        }
        
        $history->latest = true;
        $history->otheruserid = $otheruserid;
        
        return $history;
    }
    
    public function init($id)
    {
        $item = $this->em->getRepository(BookEntity::class)
                        ->findOneBy(array('id' => $id));
        
        $owned = $read = array();
        
        $history = $this->em->getRepository(BookHistory::class)->findBy(array(
            'id' => $id,
            'latest' => true
        ));
        
        foreach ($history as $row) {
            if ($row->owned()) {
                $owned[] = $row->userid;
            }
            
            if ($row->read()) {
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
            if ($userbook->owned()) {
                return "You own this";
            }

            if ($userbook->borrowed()) {
                return "You are already borrowing this";
            } 

            if ($userbook->requested()) { 
                return "You have already requested this";
            }
        }
        
        $history = $this->em->getRepository(BookHistory::class)->findBy(array(
            'id' => $item->id,
            'latest' => true
        ));
        
        $total = array();
        foreach ($history as $record) {
            if ($record->owned()) {
                if (!isset($total[$record->userid])) {
                    $total[$record->userid] = $record->stock;
                } else {
                    $total[$record->userid] += $record->stock;
                }
            } elseif ($record->borrowed() || $record->requested()) {
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
                }
                
                $newRecord = $this->history(
                    $item->id,
                    $userId,
                    BookHistory::REQUESTED,
                    $userbook,
                    0,
                    $ownerId
                );
                
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
        
        if ($this->id != -1) {
            $userbooks = $this->em->getRepository(BookHistory::class)
                                  ->findBy(array(
                                      'id' => $item->id,
                                      'latest' => true
                                  ));
            
            foreach ($userbooks as $book) {
                $users[$book->userid] = $book;
            }
        }
        
        $newRecords = array();
                
        foreach ($this->owners as $id) {
            if($this->id == -1 || !$users[$id]->owned()) {
                $newRecords[$id] = $this->history(
                    $item->id,
                    $id,
                    BookHistory::OWNED,
                    ($this->id == -1) ? null : $users[$id],
                    1
                );
            }
        }
        
        foreach ($this->read as $id) {
            if (isset($newRecords[$id])) {
                if(!$newRecords[$id]->read()) {
                    $newRecords[$id]->status += BookHistory::READ;
                }
            } else {
                $newRecords[$id] = $this->history(
                    $item->id,
                    $id,
                    BookHistory::READ,
                    ($this->id == -1) ? null : $users[$id]
                );
            }
        }
        
        if (count($newRecords) > 0) {
            foreach ($newRecords as $id => $record) {
                if (isset($users[$id])) {
                    $users[$id]->latest = false;
                }
                
                $this->em->persist($record);
            }
        }
        
        $this->em->flush();
        
        return true;
    }
}
