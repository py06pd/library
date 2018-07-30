<?php
// src/AppBundle/Controller/AuthorController
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookAuthor;
use AppBundle\Entity\BookSeries;
use AppBundle\Entity\UserAuthor;
use AppBundle\Entity\UserBook;

class AuthorController extends Controller
{
    /**
     * @Route("/authors")
     */
    public function authorsAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $useritems = $em->getRepository(UserAuthor::class)->findBy(array('userid' => $user->id));
        
        $ids = array();
        foreach ($useritems as $i) {
            $ids[] = $i->id;
        }
        
        return $this->json(array('status' => "OK", 'ids' => $ids));
    }
    
    /**
     * @Route("/author/get")
     */
    public function getAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $item = $em->getRepository(Author::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $books = $em->getRepository(BookAuthor::class)->findBy(array('authorid' => $item->getId()));
        
        $bookids = array();
        foreach ($books as $i) {
            $bookids[] = $i->getBook()->getId();
        }
            
        $collected = $seriesids = array();
        $series = $em->getRepository(BookSeries::class)->findBy(array('id' => $bookids));
        foreach ($series as $s) {
            $seriesids[$s->getSeries()->getId()] = $s->getSeries()->getId();
            $collected[$s->getBook()->getId()] = $s->getBook()->getId();
        }
        
        // add uncollected series id = 0
        if (count($bookids) > count($collected)) {
            $seriesids[0] = 0;
        }
        
        $user = $this->getUser();
        
        $tracking = false;
        if ($user) {
            $userbooks = $em->getRepository(UserBook::class)->findBy(array(
                'id' => $bookids,
                'userid' => $user->id
            ));
            foreach ($userbooks as $ub) {
                $userbook[$ub->id] = $ub;
            }
            
            if ($em->getRepository(UserAuthor::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id])) {
                $tracking = true;
            }
        }
        
        $total = $owned = $read = 0;
        foreach ($books as $book) {
            $total++;
            
            if (isset($userbook[$book->getBook()->getId()]) && $userbook[$book->getBook()->getId()]->owned) {
                $owned++;
            }
            if (isset($userbook[$book->getBook()->getId()]) && $userbook[$book->getBook()->getId()]->read) {
                $read++;
            }
        }
        
        return $this->json(array(
            'status' => "OK",
            'author' => $item,
            'total' => $total,
            'owned' => $owned,
            'read' => $read,
            'series' => $seriesids,
            'tracking' => $tracking
        ));
    }
    
    /**
     * @Route("/author/track")
     */
    public function trackAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $item = $em->getRepository(Author::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $useritem = $em->getRepository(UserAuthor::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if (!$useritem) {
            $useritem = new UserAuthor();
            $useritem->id = $item->getId();
            $useritem->userid = $user->id;
            
            $em->persist($useritem);
            
            $em->flush();
        }
                
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/author/untrack")
     */
    public function untrackAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $item = $em->getRepository(Author::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $useritem = $em->getRepository(UserAuthor::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if ($useritem) {
            $em->remove($useritem);
            
            $em->flush();
        }
                
        return $this->json(array('status' => "OK"));
    }
    
    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
}
