<?php
/** src/App/Security/CookieAuthenticator.php */
namespace App\Security;

use App\DateTimeFactory;
use App\Entity\User;
use App\Entity\UserSession;
use App\Repositories\UserRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class CookieAuthenticator
 * @package App\Security
 */
class CookieAuthenticator extends AbstractGuardAuthenticator
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
     * Instance of EntityManagerInterface
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * Implements LoggerInterface
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * CookieAuthenticator constructor.
     * @param EntityManagerInterface $em
     * @param CookieService $cookieService
     * @param DateTimeFactory $dateTime
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManagerInterface $em,
        CookieService $cookieService,
        DateTimeFactory $dateTime,
        LoggerInterface $logger
    ) {
        $this->cookieService = $cookieService;
        $this->em = $em;
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
        $this->cookieService->clear($response);
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
