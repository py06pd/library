<?php

// src/AppBundle/Security/UserAuthenticator.php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use AppBundle\Entity\User;

class UserAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var array 
     */
    private $cookieParams;
    
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    /**
     * @var string
     */
    private $secret;
    
    private $logger;
    
    /**
     * @param Doctrine\ORM\EntityManager $entityManager
     * @param string $secret
     * @param array $cookieParams
     * @param LoggerInterface $logger
     */
    public function __construct($entityManager, $secret, $cookieParams, $logger)
    {
        $this->entityManager = $entityManager;
        $this->secret = $secret;
        $this->cookieParams = $cookieParams;
        $this->logger = $logger;
    }
    
    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser(). Returning null will cause this authenticator
     * to be skipped.
     */
    public function getCredentials(Request $request)
    {
        if (!$request->request->has('username') ||
            !$request->request->has('password') ||
            $request->request->has('name') ||
            $request->request->get('username') == "" ||
            $request->request->get('password') == ""
        ) {
            return null;
        }
        
        // What you return here will be passed to getUser() as $credentials
        return array(
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password')
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->entityManager->getRepository(User::class)
                                ->findOneBy(array('username' => $credentials['username']));
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $salt = substr($user->getPassword(), 0, 16);
        if ($user->getPassword() == $salt . hash_hmac("sha256", $salt.$credentials['password'], $this->secret)) {
            return true;
        }
        
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $user = $token->getUser();
        
        $user->sessionid = hash("sha256", mt_rand(1, 32));
        $this->entityManager->flush();
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($user));
        
        return new RedirectResponse($request->getBaseUrl() . "/", 302, $bag->all());
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->start($request, $exception);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new RedirectResponse($request->getBasePath() . "/login");
        
        // clear cookie
        $response->headers->clearCookie('library', '/', $this->cookieParams['domain'], $this->cookieParams['secure']);
        
        return $response;
    }

    public function supportsRememberMe()
    {
        return false;
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
            $this->cookieParams['domain'],
            $this->cookieParams['secure']
        );
        
        return $auth;
    }
}
