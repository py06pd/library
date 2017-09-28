<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;

class DefaultController extends Controller
{
    /**
     * @Route("/info")
     */
    public function info()
    {
        echo phpinfo();
        return $this->json(array());
    }
    
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
     * @Route("/todb")
     */
    public function todbAction()
    {
        $em = $this->getDoctrine()->getManager();
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"));
        foreach ($data as $id => $item) {
            $book = new Book();
            $book->id = $id;
            $book->name = $item->name;
            if (isset($item->type) && $item->type != '') {
                $book->type = (string)$item->type;
            }

            $book->authors = array();
            if (isset($item->authors) && is_array($item->authors)) {
                $book->authors = $item->authors;
            }

            $book->genres = array();
            if (isset($item->genres) && is_array($item->genres)) {
                $book->genres = $item->genres;
            }

            $book->series = array();
            if (isset($item->series) && is_array($item->series)) {
                $book->series = $item->series;
            }

            $book->owners = array();
            if (isset($item->owners) && is_array($item->owners)) {
                $book->owners = $item->owners;
            }

            $book->read = array();
            if (isset($item->read) && is_array($item->read)) {
                $book->read = $item->read;
            }
            
            $em->persist($book);
            $em->flush();
        }
        
        return $this->json(array('status' => "OK"));
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
        $repo = $em->getRepository(Book::class);
        
        $books = $repo->findBy(array('id' => $request->request->get('ids')));
        foreach ($books as $item) {
            $em->remove($item);
        }
        $em->flush();
                
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/export")
     */
    public function exportAction()
    {
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"), true);
        
        return $this->json($data);
    }
    
    /**
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $data = $this->getDoctrine()->getRepository(Book::class)->findAll();
                
        $filters = json_decode($request->request->get('filters', json_encode(array())));
        
        $eqFilters = $noFilters = array();
        foreach ($filters as $filter) {
            if ($filter->operator == 'equals') {
                $eqFilters[$filter->field][] = $filter->value;
            } elseif ($filter->operator == 'does not equal') {
                $noFilters[$filter->field][] = $filter->value;
            }
        }
        
        $authors = array();
        $genres = array();
        $people = array();
        $series = array();
        $types = array();
        $books = array();
        foreach ($data as $item) {
            $this->addToDataArray($item, 'authors', $authors);
            $this->addToDataArray($item, 'genres', $genres);
            $this->addToDataArray($item, 'owners', $people);
            $this->addToDataArray($item, 'read', $people);
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
                    'owners' => implode(", ", $item->owners),
                    'read' => implode(", ", $item->read),
                    'series' => implode(", ", $bookSeries)
                );
            }
        }
        
        return $this->json(array(
            'status' => "OK",
            'data' => $books,
            'authors' => $authors,
            'genres' => $genres,
            'people' => $people,
            'types' => $types,
            'series' => $series
        ));
    }
    
    /**
     * @Route("/getItem")
     */
    public function getItemAction(Request $request)
    {
        $item = $this->getDoctrine()
                     ->getRepository(Book::class)
                     ->findOneBy(array('id' => $request->request->get('id')));
        
        return $this->json(array('status' => "OK", 'data' => $item));
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
            $item = $em->getRepository(Book::class)
                       ->findOneBy(array('id' => $dataItem['id']));
        }
        
        $item->name = $dataItem['name'];
        $item->type = $dataItem['type'];
        $item->authors = $dataItem['authors'];
        $item->genres = $dataItem['genres'];
        $item->series = $dataItem['series'];
        $item->owners = $dataItem['owners'];
        $item->read = $dataItem['read'];
        
        if ($dataItem['id'] == -1) {
            $em->persist($item);
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
