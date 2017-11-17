<?php
// src/AppBundle/Controller/DefaultController
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Audit;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;

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
    public function indexAction($page = null, $params = array())
    {
        $dbUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
        $users = array();
        foreach ($dbUsers as $dbUser) {
            $users[$dbUser->id] = $dbUser;
        }
        
        return $this->render('main/index.html.twig', array('data' => json_encode(array(
            'page' => $page,
            'params' => $params,
            'user' => $this->getUser(),
            'users' => $users
        ))));
    }
    
    private function checkFilters($item, $owned, $read, $bookSeries, $eqFilters, $noFilters)
    {
        $fields = array('author' => 'authors', 'genre' => 'genres');
        foreach ($fields as $filter => $field) {
            if (isset($eqFilters[$filter]) && count(array_intersect($item->$field, $eqFilters[$filter])) == 0) {
                return false;
            }

            if (isset($noFilters[$filter]) && count(array_intersect($item->$field, $noFilters[$filter])) > 0) {
                return false;
            }
        }
        
        if (isset($eqFilters['owner'])) {
            foreach ($eqFilters['owner'] as $id) {
                if (!isset($owned[$item->id]) || !isset($owned[$item->id][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($noFilters['owner'])) {
            foreach ($noFilters['owner'] as $id) {
                if (isset($owned[$item->id]) && isset($owned[$item->id][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($eqFilters['read'])) {
            foreach ($eqFilters['read'] as $id) {
                if (!isset($read[$item->id]) || !isset($read[$item->id][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($noFilters['read'])) {
            foreach ($noFilters['read'] as $id) {
                if (isset($read[$item->id]) && isset($read[$item->id][$id])) {
                    return false;
                }
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
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $result = $this->get('app.book')->delete($request->request->get('ids'));
        if ($result === false) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid form data"));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $data = $this->get('app.book')->getAll();
        $user = $this->getUser();
        
        $series = $em->getRepository(Series::class)->findBy(array(), array('name' => "ASC"));
                
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
        $userbook = $em->getRepository(UserBook::class)->findAll();
        foreach ($userbook as $row) {
            if ($row->owned) {
                $owned[$row->id][$row->userid] = $users[$row->userid]->name;
            }
            
            if ($row->read) {
                $read[$row->id][$row->userid] = $users[$row->userid]->name;
            }
            
            if ($user && $row->requestedfromid == $user->id) {
                $requests++;
            }
        }
        
        $authors = array();
        $genres = array();
        $types = array();
        $books = array();
        $order = array();
        foreach ($data as $item) {
            $this->addToDataArray($item, 'authors', $authors);
            $this->addToDataArray($item, 'genres', $genres);
            if ($item->type != '' && !in_array($item->type, $types)) {
                $types[] = $item->type;
            }
            sort($types);
            
            $bookSeries = array();
            $seriesSeg = "";
            foreach ($item->series as $value) {
                $bookSeries[] = $value->name;
                
                if ($seriesSeg == "") {
                    $seriesSeg = $value->name . str_pad(($value->number == null) ?
                        "zz" : $value->number, 2, "0", STR_PAD_LEFT);
                }
            }
                
            if ($this->checkFilters($item, $owned, $read, $bookSeries, $eqFilters, $noFilters)) {
                $segs = array();
                foreach ($item->authors as $author) {
                    $seg = (strpos($author, " ") === false) ? $author :
                        (substr($author, strrpos($author, " ") + 1) . ", " . substr($author, 0, strrpos($author, " ")));
                    
                    $segs[] = $seg;
                }
                sort($segs);

                $firstAuthor = ((count($item->authors) > 0) ? $segs[0] : "zzz") . $seriesSeg;
                
                $order[$item->id] = $firstAuthor;
                $books[$item->id] = array(
                    'id' => $item->id,
                    'name' => $item->name,
                    'type' => $item->type,
                    'authors' => implode(", ", $item->authors),
                    'order' => $firstAuthor,
                    'genres' => implode(", ", $item->genres),
                    'owners' => isset($owned[$item->id]) ? array_keys($owned[$item->id]) : array(),
                    'ownerNames' => isset($owned[$item->id]) ? implode(", ", $owned[$item->id]) : "",
                    'read' => isset($read[$item->id]) ? array_keys($read[$item->id]) : array(),
                    'readNames' => isset($read[$item->id]) ? implode(", ", $read[$item->id]) : "",
                    'series' => $item->series
                );
            }
        }
        
        array_multisort($order, SORT_ASC, $books);
        
        return $this->json(array(
            'status' => "OK",
            'data' => $books,
            'authors' => $authors,
            'genres' => $genres,
            'types' => $types,
            'requests' => $requests,
            'series' => $series,
            'user' => $user
        ));
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
        
        $result = $this->get('app.book')->request(
            $request->request->get('id'),
            $user->id
        );
        if ($result !== true) {
            return $this->json(array('status' => "error", 'errorMessage' => $result));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/notes/save")
     */
    public function notesSaveAction(Request $request)
    {
        $em = $this->getDoctrine();
        
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $user = $em->getRepository(User::class)->findOneBy(array('id' => $request->request->get('userid')));
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $data = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $user->id));
        $oldnotes = $data->notes;
        $text = trim($request->request->get('text'));
        $data->notes = $text == '' ? null : $text;
        
        $em->getManager()->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('notes' => array($oldnotes, $text)));
        
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
