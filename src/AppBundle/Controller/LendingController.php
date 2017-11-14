<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Audit;
use AppBundle\Entity\Book;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;

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
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array('id' => $item->id, 'userid' => $user->id));
        if (!$userbook || $userbook->requestedfromid == 0) {
            return $this->json(array('status' => "error", 'errorMessage' => "You have not requested this"));
        }
        
        $oldid = $userbook->requestedfromid;
        $userbook->requestedfromid = 0;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $user, array('requestedfromid' => array($oldid, 0)));
        
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
        
        $lending = $this->get('app.book')->getLending($user->id);
        $requested = $requesting = $borrowed = $borrowing = array();
                
        if (count($lending) > 0) {
            $dbUsers = $this->getDoctrine()->getRepository(User::class)->findAll();
            $users = array();
            
            foreach ($dbUsers as $dbuser) {
                $users[$dbuser->id] = $dbuser;
            }
            
            foreach ($lending as $lend) {
                $row = (object)$lend;
                if ($row->userid == $user->id) {
                    if ($row->borrowedfromid != 0) {
                        $borrowing[] = array(
                            'id' => $row->id,
                            'name' => $row->name,
                            'datetime' => date("Y-m-d H:i:s", $row->borrowedtime),
                            'from' => $users[$row->borrowedfromid]->name
                        );
                    } elseif ($row->requestedfromid != 0) {
                        $requesting[] = array(
                            'id' => $row->id,
                            'name' => $row->name,
                            'datetime' => date("Y-m-d H:i:s", $row->requestedtime),
                            'from' => $users[$row->requestedfromid]->name
                        );
                    }
                } else {
                    if ($row->borrowedfromid != 0) {
                        $borrowed[] = array(
                            'id' => $row->id,
                            'name' => $row->name,
                            'datetime' => date("Y-m-d H:i:s", $row->borrowedtime),
                            'from' => $users[$row->userid]->name
                        );
                    } elseif ($row->requestedfromid != 0) {
                        $requested[] = array(
                            'id' => $row->id,
                            'name' => $row->name,
                            'datetime' => date("Y-m-d H:i:s", $row->requestedtime),
                            'from' => $users[$row->userid]->name
                        );
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
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array(
            'id' => $item->id,
            'requestedfromid' => $user->id
        ));
        if (!$userbook) {
            return $this->json(array('status' => "error", 'errorMessage' => "No one has requested this"));
        }

        $userbook->requestedfromid = 0;
        
        $em->flush();
        
        $bookuser = $em->getRepository(User::class)->findOneBy(array('id' => $userbook->userid));
                
        $this->get('auditor')->userBookLog($item, $bookuser, array('requestedfromid' => array($user->id, 0)));
        
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
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $userbook = $em->getRepository(UserBook::class)->findOneBy(array(
            'id' => $request->request->get('id'),
            'borrowedfromid' => $user->id
        ));
        if (!$userbook) {
            return $this->json(array('status' => "error", 'errorMessage' => "No one has borrowed this"));
        }

        $userbook->borrowedfromid = 0;
        
        $em->flush();
        
        $bookuser = $em->getRepository(User::class)->findOneBy(array('id' => $userbook->userid));
        
        $this->get('auditor')->userBookLog($item, $bookuser, array('borrowedfromid' => array($user->id, 0)));
        
        return $this->json(array('status' => "OK"));
    }
}
