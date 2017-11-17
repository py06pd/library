<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Audit;
use AppBundle\Entity\Book;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;

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
    
    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
    
    /**
     * @Route("/wishlist/add")
     */
    public function addAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)
                   ->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $user->id));
        if ($userbook) {
            if ($userbook->owned) {
                return $this->formatError("You own this");
            } elseif ($userbook->wishlist) {
                return $this->formatError("You have already added this to your wishlist");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->id;
            $userbook->userid = $user->id;
            $em->persist($userbook);
        }

        $userbook->wishlist = true;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('wishlist' => array(0, 1)));
        
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
            return $this->formatError("Invalid request");
        }
        
        $books = $em->getRepository(UserBook::class)->findBy(array('userid' => $userid, 'wishlist' => true));
        
        $rows = $wishlist = array();
        foreach ($books as $row) {
            $rows[$row->id] = $row;
        }
        
        $user = $this->getUser();
        
        if (count($rows) > 0) {
            $details = $em->getRepository(Book::class)->findBy(array('id' => array_keys($rows)));
            
            foreach ($details as $detail) {
                $wishlist[] = array(
                    'id' => $detail->id,
                    'name' => $detail->name,
                    'authors' => implode(",", $detail->authors),
                    'notes' => $rows[$detail->id]->notes,
                    'gifted' => (
                        $user &&
                        $user->id !== $userid && $rows[$detail->id]->giftfromid != 0
                    ) ? (
                        isset($users[$rows[$detail->id]->giftfromid]) ?
                            $users[$rows[$detail->id]->giftfromid]->name : 'Unknown'
                    ) : ''
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
        $userid = $request->request->get('userid');
        
        $wishlist = $this->getList($userid);
        
        return $this->json(array('status' => "OK", 'books' => $wishlist));
    }
    
    /**
     * @Route("/wishlist/gift")
     */
    public function giftAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $bookuser = $em->getRepository(User::class)->findOneBy(array('id' => $request->request->get('userid')));
        if (!$bookuser) {
            return $this->formatError("Invalid request");
        }
               
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $bookuser->id));
        if (!$userbook || !$userbook->wishlist) {
            return $this->formatError("This book is not on the wishlist");
        }
        
        if ($userbook->giftfromid != 0) {
            return $this->formatError("This has already been gifted");
        }

        $user = $this->getUser();
        $userbook->giftfromid = $user ? $user->id : -1;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $bookuser, array('giftfromid' => array(0, $userbook->giftfromid)));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/wishlist/own")
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
        if (!$userbook || !$userbook->wishlist) {
            return $this->formatError("You have not added this to your wishlist");
        }

        $userbook->wishlist = false;
        $userbook->owned = true;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('owned' => array(0, 1), 'wishlist' => array(1, 0)));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/wishlist/remove")
     */
    public function removeAction(Request $request)
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
        if (!$userbook || !$userbook->wishlist) {
            return $this->formatError("You have not added this to your wishlist");
        }

        $userbook->wishlist = false;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('wishlist' => array(1, 0)));
        
        return $this->json(array('status' => "OK"));
    }
}
