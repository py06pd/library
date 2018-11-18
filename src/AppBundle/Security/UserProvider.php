<?php
/** src/AppBundle/Security/UserProvider.php */
namespace AppBundle\Security;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProvider
 * @package AppBundle\Security
 */
class UserProvider implements UserProviderInterface
{
    /**
     * EntityManager
     * @var EntityManager
     */
    private $em;

    /**
     * UserProvider constructor.
     * @param EntityManager $em
     */
    public function __construct($em)
    {
        $this->em = $em;
    }

    /**
     * Get user by username
     * @param string $username
     * @return UserInterface|null
     */
    public function loadUserByUsername($username)
    {
        $user = null;
        if ($username != null) {
            /** @var User $user */
            $user = $this->em->getRepository(User::class)->findOneBy(['username' => $username]);
        }

        return $user;
    }

    /**
     * Reset user
     * @param UserInterface $user
     * @return UserInterface|null
     */
    public function refreshUser(UserInterface $user)
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['id' => $user->getId()]);

        return $user;
    }

    /**
     * Check if class
     * @param string $class
     * @return bool
     */
    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
