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
        $em = $this->getDoctrine()->getManager();
        
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "forceLogin"));
        }
        
        $sessionid = hash("sha256", mt_rand(1, 32));
        
        $user->sessionid = $sessionid;
        $em->flush();
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($user));
        
        return $this->json(array('status' => "OK", 'user' => $user), 200, $bag->all());
    }
    
    /**
     * @Route("/login/register")
     */
    public function loginRegisterAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        
        $user = new User();
        $user->name = trim($request->request->get('name'));
        $user->username = trim($request->request->get('username'));
        $password = trim($request->request->get('password'));
        $user->role = "user";
        
        if ($user->name == '' || $user->username == '' || $password == '') {
            return $this->json(array('status' => "warn", 'errorMessage' => "Invalid form data"));
        }
        
        $salt = substr(hash("sha256", mt_rand(0, 100)), 0, 16);
        $user->password = $salt . hash_hmac("sha256", $salt . $password, $this->getParameter('secret'));
        
        $sessionid = hash("sha256", mt_rand(1, 32));
        $user->sessionid = $sessionid;
        
        $em->persist($user);
        $em->flush();
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($user));
        
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
    
    private function createCookie($user)
    {
        $time = time();
        $auth = new Cookie(
            'library',
            implode("|", array(
                $user->id,
                $time,
                hash("sha256", $user->id . $time . $user->sessionid)
            )),
            $time + (3600 * 24 * 365),
            '/',
            $this->getParameter('cookieDomain'),
            $this->getParameter('cookieSecure')
        );
        
        return $auth;
    }
}
