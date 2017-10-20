<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookHistory;
use AppBundle\Entity\User;

class LendingController extends Controller
{
    /**
     * @Route("/lending/cancelled")
     */
    public function cancelledAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)
                   ->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(BookHistory::class)
                        ->findOneBy(array(
                            'id' => $request->request->get('id'),
                            'userid' => $user->id,
                            'latest' => true
                        ));
        if (!$userbook || !$userbook->isRequested()) {
            return $this->json(array('status' => "error", 'errorMessage' => "You have not requested this"));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->unrequest($user->id);
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/lending/delivered")
     */
    public function deliveredAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $result = $this->get('app.book')->borrow($request->request->get('id'), $user->id);
        if ($result !== true) {
            return $this->json(array('status' => "error", 'errorMessage' => $result));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/lending/get")
     */
    public function getAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        $history[0] = $em->getRepository(BookHistory::class)->findBy(array('userid' => $user->id, 'latest' => true));
        $history[1] = $em->getRepository(BookHistory::class)->findBy(array(
            'otheruserid' => $user->id,
            'latest' => true
        ));
        
        $ids = $requested = $requesting = $borrowed = $borrowing = array();
        foreach ($history as $results) {
            foreach ($results as $row) {
                if ($row->isRequested() || $row->isBorrowed()) {
                    $ids[] = $row->id;
                }
            }
        }
        
        if (count($ids) > 0) {
            $books = $em->getRepository(Book::class)->findBy(array('id' => $ids));
            
            if (count($books) > 0) {
                $aBooks = array();
                foreach ($books as $book) {
                    $aBooks[$book->id] = $book->name;
                }
                
                $dbUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
                $users = array();
                foreach ($dbUsers as $user) {
                    $users[$user->id] = $user;
                }
        
                foreach ($history[0] as $row) {
                    if ($row->isRequested() || $row->isBorrowed()) {
                        $details = array(
                            'id' => $row->id,
                            'name' => $aBooks[$row->id],
                            'datetime' => date("Y-m-d H:i:s", $row->timestamp),
                            'from' => $users[$row->otheruserid]->name
                        );
                        if ($row->isRequested()) {
                            $requesting[] = $details;
                        } else {
                            $borrowing[] = $details;
                        }
                    }
                }
                
                foreach ($history[1] as $row) {
                    if ($row->isRequested() || $row->isBorrowed()) {
                        $details = array(
                            'id' => $row->id,
                            'name' => $aBooks[$row->id],
                            'datetime' => date("Y-m-d H:i:s", $row->timestamp),
                            'from' => $users[$row->userid]->name
                        );
                        if ($row->isRequested()) {
                            $requested[] = $details;
                        } else {
                            $borrowed[] = $details;
                        }
                    }
                }
            }
        }
        
        return $this->json(array(
            'status' => "OK",
            'borrowed' => $borrowed,
            'borrowing' => $borrowing,
            'requested' => $requested,
            'requesting' => $requesting
        ));
    }
    
    /**
     * @Route("/lending/rejected")
     */
    public function rejectedAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)
                   ->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(BookHistory::class)
                        ->findOneBy(array(
                            'id' => $request->request->get('id'),
                            'otheruserid' => $user->id,
                            'latest' => true
                        ));
        if (!$userbook || !$userbook->isRequested()) {
            return $this->json(array('status' => "error", 'errorMessage' => "No one has requested this"));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->unrequest($user->id);
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/lending/returned")
     */
    public function returnedAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)
                   ->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(BookHistory::class)
                        ->findOneBy(array(
                            'id' => $request->request->get('id'),
                            'otheruserid' => $user->id,
                            'latest' => true
                        ));
        if (!$userbook || !$userbook->isBorrowed()) {
            return $this->json(array('status' => "error", 'errorMessage' => "No one has borrowed this"));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->unborrow();
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
}
