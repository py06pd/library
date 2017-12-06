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
        
        if ($request->request->get('id') > 0) {
            $series = $em->getRepository(Series::class)->findOneBy(array('id' => $request->request->get('id')));
            if (!$series) {
                return $this->formatError("Invalid request");
            }
        } elseif ($authorid > 0) {
            $series = (object)array('id' => 0, 'name' => "Standalone");
        } else {
            return $this->formatError("Invalid request");
        }
        
        if ($authorid > 0) {
            $authorbooks = $em->getRepository(BookAuthor::class)->findBy(array(
                'authorid' => $authorid
            ));
            $bookids = array();
            foreach ($authorbooks as $map) {
                $bookids[$map->id] = $map->id;
            }
        }
            
        if ($series->id > 0 && $authorid > 0) {
            $bookseries = $em->getRepository(BookSeries::class)->findBy(array(
                'seriesid' => $series->id,
                'id' => $bookids
            ));
        } elseif ($series->id > 0) {
            $bookseries = $em->getRepository(BookSeries::class)->findBy(array('seriesid' => $series->id));
        } else {
            $bookseries = $em->getRepository(BookSeries::class)->findBy(array('id' => $bookids));
        }
        
        if ($series->id > 0) {
            $numbers = array();
            foreach ($bookseries as $bs) {
                $numbers[$bs->id] = $bs->number;
            }

            $bookids = array_keys($numbers);
        } else {
            foreach ($bookseries as $map) {
                if (isset($bookids[$map->id])) {
                    unset($bookids[$map->id]);
                }
            }
        }
        
        $books = $em->getRepository(Book::class)->findBy(array('id' => $bookids));
        $bookauthors = $em->getRepository(BookAuthor::class)->findBy(array('id' => $bookids));
              
        $authorids = $bookauthorids = array();
        foreach ($bookauthors as $ba) {
            $authorids[] = $ba->authorid;
            $bookauthorids[$ba->id][] = $ba->authorid;
        }
        
        $authors = array();
        if (count($authorids) > 0) {
            $dbauthors = $em->getRepository(Author::class)->findBy(array('id' => $authorids));
            foreach ($dbauthors as $a) {
                $authors[$a->id] = json_decode(json_encode($a));
            }
        }
        
        $user = $this->getUser();
        
        $tracking = false;
        $userbook = array();
        
        if ($user) {
            $userbooks = $em->getRepository(UserBook::class)->findBy(array(
                'id' => $bookids,
                'userid' => $user->id
            ));
            foreach ($userbooks as $ub) {
                $userbook[$ub->id] = $ub;
            }
            
            if ($series->id > 0 &&
                $em->getRepository(UserSeries::class)->findOneBy(array('id' => $series->id, 'userid' => $user->id))
            ) {
                $tracking = true;
            }
        }
        
        $main = $other = array();
        foreach ($books as $book) {
            $b = array(
                'id' => $book->id,
                'name' => $book->name,
                'type' => $book->type,
                'authors' => array(),
                'genres' => $book->genres,
                'owners' => array(),
                'read' => array()
            );
            if (isset($bookauthorids[$book->id])) {
                foreach ($bookauthorids[$book->id] as $id) {
                    $b['authors'][] = $authors[$id];
                }
            }
            if (isset($userbook[$book->id]) && $userbook[$book->id]->owned) {
                $b['owners'][] = $user->id;
            }
            if (isset($userbook[$book->id]) && $userbook[$book->id]->read) {
                $b['read'][] = $user->id;
            }
            if ($series->id > 0 && (string)$numbers[$book->id] != "") {
                $main[$numbers[$book->id]] = $b;
            } else {
                $other[$book->name] = $b;
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
        
        $item = $em->getRepository(UserSeries::class)->findOneBy(array('id' => $series->id, 'userid' => $user->id));
        if (!$item) {
            $item = new UserSeries();
            $item->id = $series->id;
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
        
        $item = $em->getRepository(UserSeries::class)->findOneBy(array('id' => $series->id, 'userid' => $user->id));
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
