<?php
/** src/AppBundle/Controller/UserController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\UserSession;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class UserController
 * @package AppBundle\Controller
 */
class UserController extends AbstractController
{
    /**
     * EntityManager
     * @var EntityManager
     */
    private $em;

    /**
     * Logger
     * @var LoggerInterface
     */
    private $logger;

    /**
     * App secret
     * @var string
     */
    private $secret;

    /**
     * User
     * @var User
     */
    private $user;

    /**
     * BookController constructor.
     * @param EntityManager $em
     * @param string $secret
     * @param TokenStorage $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, string $secret, TokenStorage $tokenStorage, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->secret = $secret;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Deletes users
     * @Route("/users/delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        if (!$this->user->hasRole("ROLE_ADMIN")) {
            return $this->formatError("Insufficient user rights");
        }

        $users = $this->em->getRepository(User::class)->findBy(['userId' => $request->request->get('ids')]);
        foreach ($users as $item) {
            $this->em->remove($item);
        }

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e);
            return $this->formatError("Delete failed");
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Get user sessions
     * @Route("/user/getSessions")
     * @param Request $request
     * @return JsonResponse
     */
    public function getSessions(Request $request)
    {
        $userId = $request->request->get('userId');

        if ($userId != $this->user->getId() && !$this->user->hasRole('ROLE_ADMIN')) {
            return $this->formatError("Invalid form data");
        }

        $sessions = $this->em->getRepository(UserSession::class)->findBy(['userId' => $userId]);

        return $this->json(['status' => "OK", 'data' => $sessions]);
    }

    /**
     * Gets user
     * @Route("/user/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getUserDetails(Request $request)
    {
        if (!$this->user->hasRole("ROLE_ADMIN")) {
            return $this->formatError("Insufficient user rights");
        }

        $user = $this->em->getRepository(User::class)->findOneBy(['userId' => $request->request->get('id')]);

        return $this->json(['status' => "OK", 'data' => $user]);
    }

    /**
     * Gets users
     * @Route("/users/get")
     * @return JsonResponse
     */
    public function getUsers()
    {
        if (!$this->user->hasRole("ROLE_ADMIN")) {
            return $this->formatError("Insufficient user rights");
        }

        $data = $this->em->getRepository(User::class)->findAll();

        return $this->json(['status' => "OK", 'users' => $data]);
    }

    /**
     * Save user details
     * @Route("/user/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        $userId = $request->request->get('userId');
        $name = $request->request->get('name');
        $username = $request->request->get('newUsername');
        $password = trim($request->request->get('newPassword'));
        
        if ($name == '' || $username == '' || $password == '') {
            return $this->formatAlert("Invalid form data");
        }

        if ($userId != $this->user->getId() && !$this->user->hasRole('ROLE_ADMIN')) {
            return $this->formatError("Invalid form data");
        }

        if ($userId) {
            /** @var User $user */
            $user = $this->em->getRepository(User::class)->findOneBy(['userId' => $userId]);
        } else {
            $user = new User();
            $this->em->persist($user);
        }

        $user->setName($name);
        $user->setUsername($username);
        if ($password !== "********") {
            $salt = substr(hash("sha256", mt_rand(0, 100)), 0, 16);
            $user->setPassword($salt . hash_hmac("sha256", $salt . $password, $this->secret));
        }

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e);
            return $this->formatError("Update failed");
        }
        
        return $this->json(['status' => "OK"]);
    }
}
