<?php
/** src/AppBundle/Security/UserAuthenticator.php */
namespace AppBundle\Security;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var array
     */
    private $cookieParams;
    
    /**
     * Instance of DateTimeFactory
     * @var DateTimeFactory 
     */
    private $dateTime;
    
    /**
     * @var EntityManager
     */
    private $entityManager;
    
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
     * @param EntityManager $entityManager
     * @param string $secret
     * @param array $cookieParams
     * @param DateTimeFactory $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager$entityManager,
        string $secret,
        array $cookieParams,
        DateTimeFactory $dateTime,
        LoggerInterface $logger
    ) {
        $this->cookieParams = $cookieParams;
        $this->dateTime = $dateTime;
        $this->entityManager = $entityManager;
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
            return $this->entityManager->getRepository(User::class)->findOneBy([
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
        $user = $token->getUser();      
        $user->setSessionId(hash("sha256", $this->dateTime->getNow()->getTimestamp()));
        $this->entityManager->flush($user);
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie($this->createCookie($user));
        
        return new RedirectResponse($request->getBaseUrl() . "/", 302, $bag->all());
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
    
    private function createCookie($user)
    {
        $time = $this->dateTime->getNow()->getTimestamp();
        $auth = new Cookie(
            'library',
            implode("|", array(
                $user->getId(),
                $time,
                hash("sha256", $user->getId() . $time . $user->getSessionId())
            )),
            $time + (3600 * 24 * 365),
            '/',
            $this->cookieParams['domain'],
            $this->cookieParams['secure']
        );
        
        return $auth;
    }
}
