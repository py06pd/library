<?php
// src/AppBundle/Controller/DefaultController
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Entity\UserBook;

class BookController extends Controller
{
    /**
     * @Route("/book/get")
     */
    public function getAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        if ($request->request->get('id') > 0) {
            $book = $em->getRepository(Book::class)->getBookById($request->request->get('id'));
            if (!$book) {
                return $this->formatError("Invalid request");
            }
        } else {
            $book = new Book();
        }
        
        $data = $em->getRepository(Book::class)->getAll();
        
        $authors = $em->getRepository(Author::class)->findBy([], ['name' => "ASC", 'forename' => "ASC"]);
        $series = $em->getRepository(Series::class)->findBy([], ['name' => "ASC"]);
        
        $genres = [];
        $types = [];
        foreach ($data as $item) {
            foreach ($item->getGenres() as $value) {
                if (!in_array($value, $genres)) {
                    $genres[] = $value;
                }
            }
        }

        sort($genres);
            
        if ($item->getType() && !in_array($item->getType(), $types)) {
            $types[] = $item->getType();
        }
            
        sort($types);
        
        return $this->json(array(
            'status' => "OK",
            'data' => $book->toArray(),
            'authors' => $authors,
            'genres' => $genres,
            'types' => $types,
            'series' => $series
        ));
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
        $item = $em->getRepository(Book::class)->findOneBy(['id' => $request->request->get('id')]);
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if ($userbook) {
            $old = json_decode(json_encode($userbook));
            if ($userbook->owned) {
                return $this->formatError("You already own this");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->getId();
            $userbook->userid = $user->id;
            $em->persist($userbook);
        }

        $userbook->wishlist = false;
        $userbook->owned = true;
        $userbook->stock = 1;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array(
            'wishlist' => array((isset($$old) && $old->wishlist ? 1 : 0), 0),
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if ($userbook) {
            if ($userbook->read) {
                return $this->formatError("You've already read this");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->getId();
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
        
        $book = new Book($dataItem['name']);
        if ($dataItem['id'] > 0) {
            $book->setId($dataItem['id']);
        }
        
        if ($dataItem['type']) {
            $book->setType($dataItem['type']);
        }
        
        if ($dataItem['genres']) {
            $book->setGenres($dataItem['genres']);
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $newAuthors = array();
        foreach ($dataItem['authors'] as $a) {
            if (!is_integer($a)) {
                $author = new Author(trim($a));
                $em->persist($author);
                $em->flush($author);
                $newAuthors[] = $author;
            } else {
                $author = $em->getRepository(Author::class)->findOneBy(['id' => $a]);
            }
            
            $book->addAuthor($author);
        }
        
        $newSeries = array();
        foreach ($dataItem['series'] as $s) {
            if (!is_integer($s['id'])) {
                $series = new Series($s['name'], "sequence");
                $em->persist($series);
                $em->flush($series);
                $newSeries[] = $series;
            } else {
                $series = $em->getRepository(Series::class)->findOneBy(['id' => $s['id']]);
            }
            
            $book->addSeries($series, $s['number']);
        }
        
        $this->get('app.book')->save($book);
        
        return $this->json(array(
            'status' => "OK",
            'newAuthors' => $newAuthors,
            'newSeries' => $newSeries
        ));
    }
    
    /**
     * @Route("/book/unown")
     */
    public function unownAction(Request $request)
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if (!$userbook || !$userbook->owned) {
            return $this->formatError("You don't own this");
        }
        
        $userbook->owned = false;
        $userbook->stock = 0;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('owned' => array(1, 0), 'stock' => array(1, 0)));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/book/unread")
     */
    public function unreadAction(Request $request)
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if (!$userbook || !$userbook->read) {
            return $this->formatError("You haven't read this");
        }

        $userbook->read = false;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('read' => array(1, 0)));
        
        return $this->json(array('status' => "OK"));
    }
    
    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
}
