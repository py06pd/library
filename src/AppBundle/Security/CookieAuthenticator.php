<?php

// src/AppBundle/Security/CookieAuthenticator.php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use AppBundle\Entity\User;

class CookieAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    private $cookieParams;
    
    /**
     * @param Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct($entityManager, $cookieParams)
    {
        $this->entityManager = $entityManager;
        $this->cookieParams = $cookieParams;
    }
    
    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser(). Returning null will cause this authenticator
     * to be skipped.
     */
    public function getCredentials(Request $request)
    {
        if (!$request->cookies->has('library')) {
            return null;
        }
        
        $data = explode("|", $request->cookies->get('library'));
        
        // What you return here will be passed to getUser() as $credentials
        return array(
            'id' => $data[0],
            'datetime' => $data[1],
            'code' => $data[2]
        );
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(array('id' => $credentials['id']));
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        if ($credentials['code'] == hash("sha256", $user->id . $credentials['datetime'] . $user->sessionid)) {
            return true;
        }
        
        return null;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
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
        $response = new RedirectResponse($request->getBasePath() . "/");
        
        // clear cookie
        $response->headers->clearCookie('library', '/', $this->cookieParams['domain'], $this->cookieParams['secure']);
        
        return $response;
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
