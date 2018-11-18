<?php
/** src/AppBundle/Controller/LoginController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class LoginController
 * @package AppBundle\Controller
 */
class LoginController extends Controller
{
    /**
     * @Route("/login")
     */
    public function login()
    {
        return $this->render("login.html.twig");
    }
    
    /**
     * @Route("/login/verify")
     */
    public function verifyAction(Request $request)
    {
        $user = $this->getUser();
        
        $em = $this->getDoctrine()->getManager();
        
        $user->sessionid = hash("sha256", mt_rand(1, 32));
        $em->flush();

        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($user));
        
        return new RedirectResponse("/", 200, $bag->all());
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
     * Logout
     * @Route("/login/logout")
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        $bag = new ResponseHeaderBag();
        $bag->clearCookie('library', '/', $this->getParameter('cookieDomain'), $this->getParameter('cookieSecure'));
               
        return new RedirectResponse($request->getBaseUrl() . "/login", 302, $bag->all());
    }
}
