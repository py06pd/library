<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Audit;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\BookAuthor;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;

class WishlistController extends Controller
{
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if ($userbook) {
            if ($userbook->owned) {
                return $this->formatError("You own this");
            } elseif ($userbook->wishlist) {
                return $this->formatError("You have already added this to your wishlist");
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $item->getId();
            $userbook->userid = $user->id;
            $em->persist($userbook);
        }

        $userbook->wishlist = true;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('wishlist' => array(0, 1)));
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/wishlist/get")
     */
    public function getAction(Request $request)
    {
        $userid = $request->request->get('userid');
        
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        
        $users = array();
        $userIds = $this->get('app.group')->getLinkedUsers($user->id);
        
        if (!in_array($userid, $userIds)) {
            return $this->formatError("Invalid request");
        }
        
        $data = $em->getRepository(User::class)->findBy(array('id' => $userIds));
        foreach ($data as $user) {
            $users[$user->id] = $user;
        }
        
        $books = $em->getRepository(UserBook::class)->findBy(array('userid' => $userid, 'wishlist' => true));
        
        $rows = $wishlist = array();
        foreach ($books as $row) {
            $rows[$row->id] = $row;
        }
        
        if (count($rows) > 0) {
            $details = $em->getRepository(Book::class)->getById(array_keys($rows));
                    
            foreach ($details as $detail) {
                $wishlist[] = array_merge($detail->toArray(), [
                    'notes' => $rows[$detail->getId()]->notes,
                    'gifted' => (
                        $user &&
                        $user->id !== $userid && $rows[$detail->getId()]->giftfromid != 0
                    ) ? (
                        isset($users[$rows[$detail->getId()]->giftfromid]) ?
                            $users[$rows[$detail->getId()]->giftfromid]->name : 'Unknown'
                    ) : ''
                ]);
            }
        }
        
        return $this->json(array('status' => "OK", 'books' => $wishlist));
    }
    
    /**
     * @Route("/wishlist/gift")
     */
    public function giftAction(Request $request)
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
        
        $userIds = $this->get('app.group')->getLinkedUsers($user->id);
        
        if (!in_array($request->request->get('userid'), $userIds)) {
            return $this->formatError("Invalid request");
        }
        
        $bookuser = $em->getRepository(User::class)->findOneBy(array('id' => $request->request->get('userid')));
        if (!$bookuser) {
            return $this->formatError("Invalid request");
        }
               
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $bookuser->id]);
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
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
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $user->id]);
        if (!$userbook || !$userbook->wishlist) {
            return $this->formatError("You have not added this to your wishlist");
        }

        $userbook->wishlist = false;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('wishlist' => array(1, 0)));
        
        return $this->json(array('status' => "OK"));
    }
}
