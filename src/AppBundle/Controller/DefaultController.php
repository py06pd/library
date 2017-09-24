<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

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
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"));
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
        
        $book = array();
        foreach ($data as $item) {
            if ($item->name == $request->request->get('name')) {
                $book = array(
                    'name' => $item->name,
                    'type' => isset($item->type)?$item->type:null,
                    'authors' => isset($item->authors)?$item->authors:array(),
                    'genres' => isset($item->genres)?$item->genres:array(),
                    'owners' => isset($item->owners)?$item->owners:array(),
                    'read' => isset($item->read)?$item->read:array(),
                    'series' => isset($item->series)?$item->series:array()
                );
            }
        }
        
        return $this->json(array('status' => "OK", 'data' => $book));
    }
    
    /**
     * @Route("/saveItem")
     */
    public function saveItemAction(Request $request)
    {
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"), true);
        $dataItem = json_decode($request->request->get('data'), true);
        
        if ($request->request->get('index') == -1) {
            $data[] = $dataItem;
        } else {
            foreach ($data as $i => $item) {
                if ($item['name'] == $request->request->get('originalName')) {
                    $data[$i] = $dataItem;
                    break;
                }
            }
        }
        
        file_put_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json", json_encode($data, JSON_PRETTY_PRINT));
        
        $bookSeries = array();
        if (isset($dataItem['series']) && is_array($dataItem['series'])) {
            foreach ($dataItem['series'] as $value) {
                $bookSeries[] = $value['name'];
            }
        }
            
        return $this->json(array(
            'status' => "OK",
            'book' => array(
                'name' => $dataItem['name'],
                'type' => isset($dataItem['type'])?$dataItem['type']:null,
                'authors' => isset($dataItem['authors'])?implode(", ", $dataItem['authors']):null,
                'genres' => isset($dataItem['genres'])?implode(", ", $dataItem['genres']):null,
                'owners' => isset($dataItem['owners'])?implode(", ", $dataItem['owners']):null,
                'read' => isset($dataItem['read'])?implode(", ", $dataItem['read']):null,
                'series' => implode(", ", $bookSeries)
            )
        ));
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
