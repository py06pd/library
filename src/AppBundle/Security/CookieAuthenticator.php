<?php

/** src/AppBundle/Security/CookieAuthenticator.php */
namespace AppBundle\Security;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\UserSession;
use AppBundle\Repositories\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use AppBundle\Entity\User;

/**
 * Class CookieAuthenticator
 * @package AppBundle\Security
 */
class CookieAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Cookie parameters
     * @var array
     */
    private $cookieParams;

    /**
     * Instance of DateTimeFactory
     * @var DateTimeFactory
     */
    private $dateTime;

    /**
     * Instance of EntityManager
     * @var EntityManager
     */
    private $em;

    /**
     * Implements LoggerInterface
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * CookieAuthenticator constructor.
     * @param EntityManager $em
     * @param array $cookieParams
     * @param DateTimeFactory $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct($em, $cookieParams, DateTimeFactory $dateTime, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->cookieParams = $cookieParams;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        $data = explode("|", $request->cookies->get('library'));
        
        // What you return here will be passed to getUser() as $credentials
        return ['id' => $data[0], 'datetime' => $data[1], 'code' => $data[2]];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->em->getRepository(User::class);
        return $userRepo->getUserById($credentials['id']);
    }

    /**
     * {@inheritdoc}
     * @param User $user
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $session = $this->em->getRepository(UserSession::class)->findOneBy([
            'userId' => $user->getId(),
            'created' => new DateTime("@" . $credentials['datetime'])
        ]);
        if ($session &&
            $credentials['code'] == hash("sha256", $user->getId() . $credentials['datetime'] . $session->getSessionId())
        ) {
            return true;
        }
        
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $user = $token->getUser();
        $now = $this->dateTime->getNow();
        $data = explode("|", $request->cookies->get('library'));

        $session = $this->em->getRepository(UserSession::class)->findOneBy([
            'userId' => $user->getId(),
            'created' => new DateTime("@" . $data[1])
        ]);
        $session->setLastAccessed($now);
        try {
            $this->em->flush($session);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }

        // on success, let the request continue
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->start($request, $exception);
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $response = new RedirectResponse($request->getBasePath() . "/login");
        
        // clear cookie
        $response->headers->clearCookie('library', '/', $this->cookieParams['domain'], $this->cookieParams['secure']);
        
        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        if ($request->cookies->has('library')) {
            return true;
        }
        
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsRememberMe()
    {
        return false;
    }
}
