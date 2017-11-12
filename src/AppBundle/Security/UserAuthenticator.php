<?php

// src/AppBundle/Security/UserAuthenticator.php
namespace AppBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use AppBundle\Entity\User;

class UserAuthenticator extends AbstractGuardAuthenticator
{
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
     */
    public function __construct($entityManager, $secret, $logger)
    {
        $this->entityManager = $entityManager;
        $this->secret = $secret;
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
        $this->logger->info($salt . hash_hmac("sha256", $salt.$credentials['password'], $this->secret));
        if ($user->getPassword() == $salt . hash_hmac("sha256", $salt.$credentials['password'], $this->secret)) {
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
        $data = array(
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData())

            // or to translate this message
            // $this->translator->trans($exception->getMessageKey(), $exception->getMessageData())
        );

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = array(
            // you might translate this message
            'message' => 'Authentication Required'
        );

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }
}
