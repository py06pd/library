<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookHistory;
use AppBundle\Entity\User;

class WishlistController extends Controller
{
    /**
     * @Route("/wishlist")
     */
    public function indexAction(Request $request)
    {
        $userid = $request->query->get('userid');
        
        $wishlist = $this->getList($userid);
        
        return $this->forward('AppBundle:Default:index', array(
            'page' => 'wishlist',
            'params' => array('books' => $wishlist, 'userid' => $userid)
        ));
    }
    
    /**
     * @Route("/wishlist/add")
     */
    public function addAction(Request $request)
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
        if ($userbook && $userbook->isOwned()) {
            return $this->json(array('status' => "error", 'errorMessage' => "You own this"));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->wish();
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    private function getList($userid)
    {
        $em = $this->getDoctrine()->getManager();
        
        $users = array();
        $data = $em->getRepository(User::class)->findAll();
        foreach ($data as $user) {
            $users[$user->id] = $user;
        }
        
        if (!isset($users[$userid])) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $history = $em->getRepository(BookHistory::class)->findBy(array('userid' => $userid, 'latest' => true));
        
        $rows = $wishlist = array();
        foreach ($history as $row) {
            if ($row->isOnWishlist()) {
                $rows[$row->id] = $row;
            }
        }
        
        if (count($rows) > 0) {
            $details = $em->getRepository(Book::class)->findBy(array('id' => array_keys($rows)));
            
            foreach ($details as $detail) {
                $wishlist[] = array(
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'authors' => implode(",", $detail->authors),
                    'datetime' => date("Y-m-d H:i:s", $rows[$detail->id]->timestamp),
                    'gifted' => (
                        $this->getUser() &&
                        $this->getUser()->id !== $userid && $rows[$detail->id]->isGifted()
                    ) ? $users[$rows[$detail->id]->otheruserid]->name : ''
                );
            }
        }
        
        return $wishlist;
    }
    
    /**
     * @Route("/wishlist/get")
     */
    public function getAction(Request $request)
    {
        $userid = $request->request->get('id');
        
        $wishlist = $this->getList($userid);
        
        return $this->json(array('books' => $wishlist));
    }
    
    /**
     * @Route("/wishlist/own")
     */
    public function ownAction(Request $request)
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
        if (!$userbook || !$userbook->isOnWishlist()) {
            return $this->json(array(
                'status' => "error",
                'errorMessage' => "You have not added this to your wishlist"
            ));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->own();
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/wishlist/remove")
     */
    public function removeAction(Request $request)
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
        if (!$userbook || !$userbook->isOnWishlist()) {
            return $this->json(array(
                'status' => "error",
                'errorMessage' => "You have not added this to your wishlist"
            ));
        }

        $userbook->latest = false;
        
        $newRecord = $userbook->double()->unwish();
        
        $em->persist($newRecord);
        $em->flush();
        
        return $this->json(array('status' => "OK"));
    }
}
