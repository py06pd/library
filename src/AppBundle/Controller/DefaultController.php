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
    
    /**
     * @Route("/getData")
     */
    public function getDataAction(Request $request)
    {
        $data = json_decode(file_get_contents($this->getParameter('kernel.project_dir') . "/app/Resources/data.json"));
        
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
            if (isset($item->type) && !in_array($item->type, $types)) {
                $types[] = $item->type;
            }
            
            if (isset($item->series) && is_array($item->series)) {
                foreach ($item->series as $value) {
                    if (!in_array($value->name, $series)) {
                        $series[] = $value->name;
                    }
                }
            }
            
            $books[] = array(
                'name' => $item->name,
                'type' => isset($item->type)?$item->type:null,
                'authors' => isset($item->type)?implode(", ", $item->authors):null,
                'genres' => isset($item->type)?implode(", ", $item->genres):null,
                'owners' => isset($item->type)?implode(", ", $item->owners):null,
                'read' => isset($item->type)?implode(", ", $item->read):null,
                'series' => isset($item->type)?json_encode($item->series):null
            );
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
                    'authors' => isset($item->type)?implode(", ", $item->authors):null,
                    'genres' => isset($item->type)?implode(", ", $item->genres):null,
                    'owners' => isset($item->type)?implode(", ", $item->owners):null,
                    'read' => isset($item->type)?implode(", ", $item->read):null,
                    'series' => isset($item->type)?json_encode($item->series):null
                );
            }
        }
        
        return $this->json(array('status' => "OK", 'data' => $book));
    }
    
    private function addToDataArray($item, $key, &$items)
    {
        if (isset($item->$key) && is_array($item->$key)) {
            foreach ($item->$key as $value) {
                if (!in_array($value, $items)) {
                    $items[] = $value;
                }
            }
        }
    }
}
