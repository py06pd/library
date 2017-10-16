<?php
// src/AppBundle/Controller/DefaultController
namespace AppBundle\Controller;

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
        $result = $this->get('app.book')->delete($request->request->get('ids'));
        if ($result === false) {
            return $this->json(array(
                'status' => "error",
                'errorMessage' => "Invalid form data"
            ));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $em = $this->getDoctrine();
        $data = $em->getRepository(Book::class)->findAll();
        $user = $this->getUser();
        
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
        foreach ($dbUsers as $dbUser) {
            $users[$dbUser->id] = $dbUser;
        }
        
        $owned = $read = array();
        $requests = 0;
        $history = $em->getRepository(BookHistory::class)->findBy(array('latest' => true));
        foreach ($history as $row) {
            if ($row->owned()) {
                $owned[$row->id][] = $users[$row->userid]->name;
            }
            
            if ($row->read()) {
                $read[$row->id][] = $users[$row->userid]->name;
            }
            
            if ($row->requested() && $user && $row->otheruserid == $user->id) {
                $requests++;
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
            'requests' => $requests,
            'series' => $series,
            'user' => $user,
            'users' => $users
        ));
    }
    
    /**
     * @Route("/getItem")
     */
    public function getItemAction(Request $request)
    {
        $book = $this->get('app.book');
        $book->init($request->request->get('id'));
        
        return $this->json(array('status' => "OK", 'data' => $book));
    }
    
    /**
     * @Route("/saveItem")
     */
    public function saveItemAction(Request $request)
    {
        $dataItem = json_decode($request->request->get('data'), true);
        
        $book = $this->get('app.book');
        $book->id = $dataItem['id'];
        $book->name = $dataItem['name'];
        $book->type = $dataItem['type'];
        $book->authors = $dataItem['authors'];
        $book->genres = $dataItem['genres'];
        $book->series = $dataItem['series'];
        $book->owners = $dataItem['owners'];
        $book->read = $dataItem['read'];
        
        $book->save();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/request")
     */
    public function requestAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array(
                'status' => "error",
                'errorMessage' => "You must be logged in to make request"
            ));
        }
        
        $result = $this->get('app.book')->request(
            $request->request->get('id'),
            $user->id
        );
        if ($result !== true) {
            return $this->json(array(
                'status' => "error",
                'errorMessage' => $result
            ));
        }
        
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
