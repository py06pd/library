<?php
// src/AppBundle/Controller/DefaultController
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Entity\UserBook;

class BookController extends Controller
{
    /**
     * @Route("/book/get")
     */
    public function getAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $item->series = array_values($this->get('app.book')->getSeries($item->id));
        
        return $this->json(array('status' => "OK", 'data' => $item));
    }
    
    /**
     * @Route("/book/own")
     */
    public function ownAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $user->id));
        if ($userbook) {
            $old = json_decode(json_encode($userbook));
            if ($userbook->owned) {
                return $this->formatError("You already own this");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->id;
            $userbook->userid = $user->id;
            $em->persist($userbook);
        }

        $userbook->wishlist = false;
        $userbook->owned = true;
        $userbook->stock = 1;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array(
            'wishlist' => array(($old && $old->wishlist ? 1 : 0), 0),
            'owned' => array(0, 1),
            'stock' => array(0, 1)
        ));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/book/read")
     */
    public function readAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $user->id));
        if ($userbook) {
            if ($userbook->read) {
                return $this->formatError("You've already read this");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->id;
            $userbook->userid = $user->id;
            $em->persist($userbook);
        }

        $userbook->read = true;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('read' => array(0, 1)));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/book/save")
     */
    public function saveAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $dataItem = json_decode($request->request->get('data'), true);
        
        $book = $this->get('app.book');
        $book->id = $dataItem['id'];
        $book->name = $dataItem['name'];
        $book->type = $dataItem['type'];
        $book->authors = $dataItem['authors'];
        $book->genres = $dataItem['genres'];
        $book->series = $final_array = array_combine(array_column($dataItem['series'], 'id'), $dataItem['series']);
        
        $book->save();
        
        return $this->json(array('status' => "OK"));
    }
    
    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
}
