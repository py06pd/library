<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use AppBundle\Entity\User;

class LoginController extends Controller
{
    /**
     * @Route("/login")
     */
    public function loginAction(Request $request)
    {
        if (!$this->getUser()) {
            return $this->json(array('status' => "forceLogin"));
        }
        
        $sessionid = hash("sha256", mt_rand(1, 32));
        
        $em = $this->getDoctrine()->getManager();
        $user = $em->getRepository(User::class)->findOneBy(array('id' => $this->getUser()->id));       
        $user->sessionid = $sessionid;
        $em->flush();
        
        $time = time();
        $auth = new Cookie(
            'library',
            implode("|", array(
                $user->id,
                $time,
                hash("sha256", $user->id . $time . $sessionid)
            )),
            0,
            '/',
            $this->getParameter('cookieDomain'),
            $this->getParameter('cookieSecure')
        );
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie($auth);
        
        return $this->json(array('status' => "OK", 'user' => $user), 200, $bag->all());
    }
    
    /**
     * @Route("/logout")
     */
    public function logoutAction(Request $request)
    {
        $bag = new ResponseHeaderBag();
        $bag->clearCookie('library', '/', $this->getParameter('cookieDomain'), $this->getParameter('cookieSecure'));
               
        return $this->json(array('status' => "OK"), 200, $bag->all());
    }
}
