<?php

namespace AppBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookHistory;
use AppBundle\Entity\User;

class DefaultController extends Controller
{
    /**
     * @Route("/getLogFile")
     */
    public function getLogFileAction(Request $request)
    {
        $contents = file_get_contents(
            $this->getParameter('kernel.project_dir') .
            "/var/logs/" .
            $request->request->get('file')
        );
        
        return $this->json(array('status' => "OK", 'contents' => $contents));
    }
    
    /**
     * @Route("/getLogFiles")
     */
    public function getLogFilesAction()
    {
        $logfiles = glob($this->getParameter('kernel.project_dir') . "/var/logs/*");
        $files = array();
        foreach ($logfiles as $file) {
            $files[] = substr($file, strrpos($file, '/') + 1);
        }
        return $this->json(array('status' => "OK", 'files' => $files));
    }
    
    /**
     * @Route("/logs")
     */
    public function logsAction()
    {
        return $this->render('main/logs.html.twig');
    }
    
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        return $this->render('main/index.html.twig');
    }
    
    private function checkFilters($item, $bookSeries, $eqFilters, $noFilters)
    {
        foreach (array('author' => 'authors', 'genre' => 'genres', 'owner' => 'owners', 'read' => 'read') as $filter => $field) {
            if (isset($eqFilters[$filter]) && count(array_intersect($item->$field, $eqFilters[$filter])) == 0) {
                return false;
            }

            if (isset($noFilters[$filter]) && count(array_intersect($item->$field, $noFilters[$filter])) > 0) {
                return false;
            }
        }
        
        if (isset($eqFilters['type']) && !in_array($item->type, $eqFilters['type'])) {
            return false;
        }
        
        if (isset($noFilters['type']) && in_array($item->type, $noFilters['type'])) {
            return false;
        }

        if (isset($eqFilters['series']) && count(array_intersect($bookSeries, $eqFilters['series'])) == 0) {
            return false;
        }

        if (isset($noFilters['series']) && count(array_intersect($bookSeries, $noFilters['series'])) > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * @Route("/deleteItems")
     */
    public function deleteItemsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
                
        $history = $em->getRepository(BookHistory::class)->findBy(array('id' => $request->request->get('ids')));
        $books = $em->getRepository(Book::class)->findBy(array('id' => $request->request->get('ids')));
        foreach ($books as $item) {
            $em->remove($item);
        }
        foreach ($history as $item) {
            $em->remove($item);
        }
        
        $em->flush();
                
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $em = $this->getDoctrine();
        $data = $em->getRepository(Book::class)->findAll();
                
        $filters = json_decode($request->request->get('filters', json_encode(array())));
        
        $eqFilters = $noFilters = array();
        foreach ($filters as $filter) {
            if ($filter->operator == 'equals') {
                $eqFilters[$filter->field][] = $filter->value;
            } elseif ($filter->operator == 'does not equal') {
                $noFilters[$filter->field][] = $filter->value;
            }
        }
        
        $dbUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
        $users = array();
        foreach ($dbUsers as $user) {
            $users[$user->id] = $user;
        }
        
        $owned = $read = array();
        $history = $em->getRepository(BookHistory::class)->findBy(array('latest' => true));
        foreach ($history as $row) {
            if ($row->status & BookHistory::OWNED) {
                $owned[$row->id][] = $users[$row->userid]->name;
            }
            
            if ($row->status & BookHistory::READ) {
                $read[$row->id][] = $users[$row->userid]->name;
            }
        }
        
        $authors = array();
        $genres = array();
        $series = array();
        $types = array();
        $books = array();
        foreach ($data as $item) {
            $this->addToDataArray($item, 'authors', $authors);
            $this->addToDataArray($item, 'genres', $genres);
            if ($item->type != '' && !in_array($item->type, $types)) {
                $types[] = $item->type;
            }
            sort($types);
            
            $bookSeries = array();
            foreach ($item->series as $value) {
                $bookSeries[] = $value['name'];
                if (!in_array($value['name'], $series)) {
                    $series[] = $value['name'];
                }
            }
                
            sort($series);
            
            if ($this->checkFilters($item, $bookSeries, $eqFilters, $noFilters)) {
                $books[] = array(
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'authors' => implode(", ", $item->authors),
                    'genres' => implode(", ", $item->genres),
                    'owners' => isset($owned[$item->id]) ? implode(", ", $owned[$item->id]) : array(),
                    'read' => isset($read[$item->id]) ? implode(", ", $read[$item->id]) : array(),
                    'series' => implode(", ", $bookSeries)
                );
            }
        }
        
        return $this->json(array(
            'status' => "OK",
            'data' => $books,
            'authors' => $authors,
            'genres' => $genres,
            'types' => $types,
            'series' => $series,
            'user' => $this->getUser(),
            'users' => $users
        ));
    }
    
    /**
     * @Route("/getItem")
     */
    public function getItemAction(Request $request)
    {
        $em = $this->getDoctrine();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        
        $owned = $read = array();
        $history = $em->getRepository(BookHistory::class)->findBy(array(
            'id' => $request->request->get('id'),
            'latest' => true
        ));
        foreach ($history as $row) {
            if ($row->status & BookHistory::OWNED) {
                $owned[] = $row->userid;
            }
            
            if ($row->status & BookHistory::READ) {
                $read[] = $row->userid;
            }
        }
        
        return $this->json(array(
            'status' => "OK",
            'data' => $item,
            'owned' => $owned,
            'read' => $read
        ));
    }
    
    /**
     * @Route("/saveItem")
     */
    public function saveItemAction(Request $request)
    {
        $dataItem = json_decode($request->request->get('data'), true);
        
        $em = $this->getDoctrine()->getManager();
        if ($dataItem['id'] == -1) {
            $item = new Book();
        } else {
            $item = $em->getRepository(Book::class)->findOneBy(array('id' => $dataItem['id']));
        }
        
        $item->name = $dataItem['name'];
        $item->type = $dataItem['type'];
        $item->authors = $dataItem['authors'];
        $item->genres = $dataItem['genres'];
        $item->series = $dataItem['series'];
        
        if ($dataItem['id'] == -1) {
            $em->persist($item);
        }
        
        $em->flush();
        
        if ($dataItem['id'] != -1) {
            $userbooks = $em->getRepository(BookHistory::class)->findBy(array('id' => $item->id, 'latest' => true));
            $users = array();
            foreach ($userbooks as $book) {
                $users[$book->userid] = $book;
            }
        }
        
        $newRecords = array();
                
        foreach ($dataItem['owners'] as $id) {
            if (isset($users[$id]) && !($users[$id]->status & BookHistory::OWNED)) {
                $newRecord = new BookHistory();
                $newRecord->init($item->id, $id, BookHistory::OWNED, $users[$id], 1);
                $newRecords[$id] = $newRecord;
            } elseif ($dataItem['id'] == -1) {
                $newRecord = new BookHistory();
                $newRecord->init($item->id, $id, BookHistory::OWNED, 1);
                $newRecords[$id] = $newRecord;
            }
        }
        
        foreach ($dataItem['read'] as $id) {
            if (isset($newRecords[$id]) && !($newRecords[$id]->status & BookHistory::READ)) {
                $newRecords[$id]->status += BookHistory::READ;
            } elseif (isset($users[$id]) && !($users[$id]->status & BookHistory::READ)) {
                $newRecord = new BookHistory();
                $newRecord->init($item->id, $id, BookHistory::READ, $users[$id]);
                $newRecords[$id] = $newRecord;
            } elseif ($dataItem['id'] == -1) {
                $newRecord = new BookHistory();
                $newRecord->init($item->id, $id, BookHistory::READ);
                $newRecords[$id] = $newRecord;
            }
        }
        
        if (count($newRecords) > 0) {
            foreach ($newRecords as $id => $record) {
                if (isset($users[$id])) {
                    $users[$id]->latest = false;
                }
                
                $em->persist($record);
            }
        }
        
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/request")
     */
    public function requestAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(BookHistory::class)->findOneBy(array(
            'id' => $item->id,
            'userid' => $user->id,
            'latest' => true
        ));
        if ($userbook->status & BookHistory::OWNED) {
            return $this->json(array('status' => "error", 'errorMessage' => "You own this"));
        }
        if ($userbook->status & BookHistory::BORROWED) {
            return $this->json(array('status' => "error", 'errorMessage' => "You are already borrowing this"));
        } 
        if ($userbook->status & BookHistory::REQUESTED) { 
            return $this->json(array('status' => "error", 'errorMessage' => "You have already requested this"));
        }  
        
        $criteria = new Criteria(new CompositeExpression('AND', array(
            new Comparison('id', '=', $item->id),
            new Comparison('latest', '=', true),
            new Comparison('stock', '>', 0)
        )));        
        $history = $em->getRepository(BookHistory::class)->findBy($criteria);
        $total = array();
        foreach ($history as $record) {
            if ($record->status & BookHistory::OWNED) {
                $total[$record->userid] += $record->stock;
            } elseif ($record->status & BookHistory::BORROWED || $record->status & BookHistory::REQUESTED) {
                $total[$record->otheruserid] -= 1;
            }
        }
        
        if (array_sum($total) <= 0) {
            return $this->json(array('status' => "error", 'errorMessage' => "None available to borrow"));
        }
        
        foreach ($total as $userId => $stock) {
            if ($stock > 0) {
                $userbook->latest = false;
                
                $newRecord = new BookHistory();
                $newRecord->init($item->id, $user->id, BookHistory::REQUESTED, $userbook, 0, $userId);
                $em->persist($newRecord);
                break;
            }
        }
        
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    private function addToDataArray($item, $key, &$items)
    {
        if (isset($item->$key) && is_array($item->$key)) {
            foreach ($item->$key as $value) {
                if (!in_array($value, $items)) {
                    $items[] = $value;
                }
            }
            
            sort($items);
        }
    }
}
