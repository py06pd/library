<?php
/** src/App/Security/UserAuthenticator.php */
namespace App\Security;

use App\DateTimeFactory;
use App\Entity\User;
use App\Entity\UserSession;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserAuthenticator
 * @package App\Security
 */
class UserAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Cookie service
     * @var CookieService
     */
    private $cookieService;
    
    /**
     * Instance of DateTimeFactory
     * @var DateTimeFactory
     */
    private $dateTime;
    
    /**
     * @var EntityManagerInterface
     */
    private $em;
    
    /**
     * Implements LoggerInterface
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var string
     */
    private $secret;
    
    /**
     * UserAuthenticator constructor.
     * @param string $secret
     * @param EntityManagerInterface $em
     * @param CookieService $cookieService
     * @param DateTimeFactory $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        string $secret,
        EntityManagerInterface $em,
        CookieService $cookieService,
        DateTimeFactory $dateTime,
        LoggerInterface $logger
    ) {
        $this->cookieService = $cookieService;
        $this->dateTime = $dateTime;
        $this->em = $em;
        $this->logger = $logger;
        $this->secret = $secret;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getCredentials(Request $request)
    {
        return [
            'username' => $request->request->get('username'),
            'password' => $request->request->get('password')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        if ($credentials['username'] != "") {
            return $this->em->getRepository(User::class)->findOneBy([
                'username' => $credentials['username']
            ]);
        }
        
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function checkCredentials($credentials, UserInterface $user)
    {
        $salt = substr($user->getPassword(), 0, 16);
        if ($user->getPassword() == $salt . hash_hmac("sha256", $salt . $credentials['password'], $this->secret)) {
            return true;
        }
        
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        /** @var User $user */
        $user = $token->getUser();

        $now = $this->dateTime->getNow();
        $sessionId = hash("sha256", $user->getId() . $now->getTimestamp() . $this->secret);

        $agent = $request->headers->get('User-Agent');
        $device = substr($agent, strpos($agent, '(') + 1, strpos($agent, ')') - strpos($agent, '(') - 1);
        
        $session = new UserSession($user->getId(), $now, $sessionId, $device);

        $this->em->persist($session);

        // delete old device sessions
        $sessions = $this->em->getRepository(UserSession::class)->findBy([
            'userId' => $user->getId(),
            'device' => $device
        ]);
        foreach ($sessions as $oldSession) {
            $this->em->remove($oldSession);
        }

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return new Response('', 500);
        }

        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($session));

        $url = $request->getBaseUrl() . "/";
        if ($request->getQueryString()) {
            $url .= "?" . $request->getQueryString();
        }

        if ($request->request->get('hash')) {
            $url .= $request->request->get('hash');
        }

        return new RedirectResponse($url, 302, $bag->all());
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
        $this->cookieService->clear($response);
        return $response;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supports(Request $request)
    {
        if ($request->request->has('username') && $request->request->has('password')) {
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

    /**
     * Create cookie for authentication
     * @param UserSession $session
     * @return Cookie
     */
    private function createCookie(UserSession $session)
    {
        $time = $session->getCreated()->getTimestamp();
        return $this->cookieService->create(
            implode("|", [
                $session->getUserId(),
                $time,
                hash("sha256", $session->getUserId() . $time . $session->getSessionId())
            ]),
            $time + (3600 * 24 * 365)
        );
    }
}
