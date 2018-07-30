<?php
// src/AppBundle/Controller/SeriesController
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookAuthor;
use AppBundle\Entity\BookSeries;
use AppBundle\Entity\Series;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\UserSeries;

class SeriesController extends Controller
{
    /**
     * @Route("/series")
     */
    public function seriesAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $userseries = $em->getRepository(UserSeries::class)->findBy(array('userid' => $user->id));
        
        $seriesids = array();
        foreach ($userseries as $us) {
            $seriesids[] = $us->id;
        }
        
        return $this->json(array('status' => "OK", 'series' => $seriesids));
    }
    
    /**
     * @Route("/series/get")
     */
    public function getAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $authorid = $request->request->get('authorid');
        $seriesId = $request->request->get('id');
        
        if ($seriesId > 0) {
            $series = $em->getRepository(Series::class)->findOneBy(array('id' => $seriesId));
            if (!$series) {
                return $this->formatError("Invalid request");
            }
        } elseif ($authorid > 0) {
            $series = (object)array('id' => 0, 'name' => "Standalone");
        } else {
            return $this->formatError("Invalid request");
        }
        
        $books = $em->getRepository(Book::class)->getBooksByAuthorAndSeries($authorid, $seriesId);
        
        $user = $this->getUser();
        
        $tracking = false;
        $userbook = [];
        
        $bookids = [];
        foreach ($books as $book) {
            $bookids[] = $book->getId();
        }
        
        $userbooks = $em->getRepository(UserBook::class)->findBy(array(
            'id' => $bookids,
            'userid' => $user->id
        ));
        foreach ($userbooks as $ub) {
            $userbook[$ub->id] = $ub;
        }

        if ($seriesId > 0 &&
            $em->getRepository(UserSeries::class)->findOneBy(array('id' => $seriesId, 'userid' => $user->id))
        ) {
            $tracking = true;
        }
        
        $main = $other = array();
        foreach ($books as $book) {
            $b = ['owners' => [], 'read' => []];

            if (isset($userbook[$book->getId()]) && $userbook[$book->getId()]->owned) {
                $b['owners'][] = $user->id;
            }
            if (isset($userbook[$book->getId()]) && $userbook[$book->getId()]->read) {
                $b['read'][] = $user->id;
            }
            if ($seriesId > 0 && $book->getSeries()->get(0)->getNumber()) {
                $main[$book->getSeries()->get(0)->getNumber()] = $b;
            } else {
                $other[$book->getName()] = $b;
            }
        }
        
        ksort($main);
        ksort($other);
        
        return $this->json(array(
            'status' => "OK",
            'series' => $series,
            'main' => $main,
            'other' => array_values($other),
            'userbooks' => $userbook,
            'tracking' => $tracking
        ));
    }
    
    /**
     * @Route("/series/track")
     */
    public function trackAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $series = $em->getRepository(Series::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$series) {
            return $this->formatError("Invalid request");
        }
        
        $item = $em->getRepository(UserSeries::class)->findOneBy(['id' => $series->getId(), 'userid' => $user->id]);
        if (!$item) {
            $item = new UserSeries();
            $item->id = $series->getId();
            $item->userid = $user->id;
            
            $em->persist($item);
            
            $em->flush();
        }
                
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/series/untrack")
     */
    public function untrackAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $series = $em->getRepository(Series::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$series) {
            return $this->formatError("Invalid request");
        }
        
        $item = $em->getRepository(UserSeries::class)->findOneBy(['id' => $series->getId(), 'userid' => $user->id]);
        if ($item) {
            $em->remove($item);
            
            $em->flush();
        }
                
        return $this->json(array('status' => "OK"));
    }
    
    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
}
