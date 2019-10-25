<?php
/** src/App/Controller/LoginController.php */
namespace App\Controller;

use App\Security\CookieService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LoginController
 * @package App\Controller
 */
class LoginController extends Controller
{
    /**
     * Instance of CookieService
     * @var CookieService
     */
    private $cookieService;

    /**
     * LoginController constructor.
     * @param CookieService $cookieService
     */
    public function __construct(CookieService $cookieService)
    {
        $this->cookieService = $cookieService;
    }

    /**
     * Display login page
     * @Route("/login")
     * @return Response
     */
    public function login()
    {
        return $this->render("login.html.twig");
    }
    
    /**
     * Logout
     * @Route("/login/logout")
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        $response = new RedirectResponse($request->getBasePath() . "/login");
        $this->cookieService->clear($response);
        return $response;
    }
}
