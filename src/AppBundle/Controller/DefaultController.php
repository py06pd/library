<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;

class DefaultController extends Controller
{
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
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"), true);
        
        foreach ($request->request->get('ids', array()) as $id) {
            unset($data[$id]);
        }
        
        file_put_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json", json_encode($data, JSON_PRETTY_PRINT));
        
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
        if ($this->getParameter('source') == 'pgsql') {
            $data = $this->getDoctrine()->getRepository(Book::class)->findAll();
        } else {        
            $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"));
        }
        
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
        foreach ($data as $id => $item) {
            $this->addToDataArray($item, 'authors', $authors);
            $this->addToDataArray($item, 'genres', $genres);
            $this->addToDataArray($item, 'owners', $people);
            $this->addToDataArray($item, 'read', $people);
            if (isset($item->type) && $item->type != '' && !in_array($item->type, $types)) {
                $types[] = $item->type;
            }
            sort($types);
            
            $bookSeries = array();
            if (isset($item->series) && is_array($item->series)) {
                foreach ($item->series as $value) {
                    $bookSeries[] = $value->name;
                    if (!in_array($value->name, $series)) {
                        $series[] = $value->name;
                    }
                }
                
                sort($series);
            }
            
            if ($this->checkFilters($item, $bookSeries, $eqFilters, $noFilters)) {
                $books[] = array(
                    'id' => $id,
                    'name' => $item->name,
                    'type' => isset($item->type)?$item->type:null,
                    'authors' => isset($item->authors)?implode(", ", $item->authors):null,
                    'genres' => isset($item->genres)?implode(", ", $item->genres):null,
                    'owners' => isset($item->owners)?implode(", ", $item->owners):null,
                    'read' => isset($item->read)?implode(", ", $item->read):null,
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
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"));
        
        $item = $data->{$request->request->get('id')};
        $book = array(
            'id' => $request->request->get('id'),
            'name' => $item->name,
            'type' => isset($item->type)?$item->type:null,
            'authors' => isset($item->authors)?$item->authors:array(),
            'genres' => isset($item->genres)?$item->genres:array(),
            'owners' => isset($item->owners)?$item->owners:array(),
            'read' => isset($item->read)?$item->read:array(),
            'series' => isset($item->series)?$item->series:array()
        );
        
        return $this->json(array('status' => "OK", 'data' => $book));
    }
    
    /**
     * @Route("/saveItem")
     */
    public function saveItemAction(Request $request)
    {
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"), true);
        $dataItem = json_decode($request->request->get('data'), true);
        
        if ($dataItem['id'] == -1) {
            $dataItem['id'] = max(array_keys($data)) + 1;
        } 
        
        $data[$dataItem['id']] = $dataItem;
        
        file_put_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json", json_encode($data, JSON_PRETTY_PRINT));
        
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
