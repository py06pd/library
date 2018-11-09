<?php
/** src/AppBundle/Controller/GroupController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class GroupController
 * @package AppBundle\Controller
 */
class GroupController extends AbstractController
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
     * User
     * @var User
     */
    private $user;

    /**
     * GroupController constructor.
     * @param EntityManager $em
     * @param TokenStorageInterface $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, TokenStorageInterface $tokenStorage, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Gets group
     * @Route("/group/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getGroup(Request $request)
    {
        $groupId = $request->request->get('groupId');

        if (!$this->user->hasRole("ROLE_ADMIN") && !$this->user->inGroup($groupId)) {
            return $this->formatError("Insufficient user rights");
        }

        $group = $this->em->getRepository(UserGroup::class)->findOneBy(['groupId' => $groupId]);

        $users = [];
        if ($this->user->hasRole("ROLE_ADMIN")) {
            $users = $this->em->getRepository(User::class)->findAll();
        }

        return $this->json(['status' => "OK", 'data' => $group, 'users' => $users]);
    }

    /**
     * Saves group
     * @Route("/group/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        if (!$this->user->hasRole("ROLE_ADMIN")) {
            return $this->formatError("Insufficient user rights");
        }

        $data = json_decode($request->request->get('data'), true);

        if ($data['groupId']) {
            /** @var UserGroup $group */
            $group = $this->em->getRepository(UserGroup::class)->findOneBy(['groupId' => $data['groupId']]);
            if (!$group) {
                return $this->formatError("Invalid form data");
            }
        } else {
            $group = new UserGroup($data['name']);
            $this->em->persist($group);
        }

        $group->setName($data['name']);

        $new = $userIds = [];
        foreach ($data['users'] as $user) {
            $userIds[] = $user['userId'];
        }

        /** @var User[] $users */
        $users = $this->em->getRepository(User::class)->findBy(['userId' => $userIds]);
        foreach ($users as $user) {
            $new[$user->getId()] = $user;
        }

        $existing = [];
        foreach ($group->getGroupUsers() as $user) {
            if (in_array($user->getUser()->getId(), array_keys($new))) {
                $existing[] = $user->getUser()->getId();
            } else {
                $group->removeUser($user->getUser());
            }
        }

        foreach ($new as $user) {
            if (!in_array($user->getId(), $existing)) {
                $group->addUser($user);
            }
        }

        try {
            $this->em->flush($group);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return $this->formatError("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }
}
