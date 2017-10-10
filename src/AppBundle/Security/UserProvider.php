<?php

// src/AppBundle/Security/UserProvider.php
namespace AppBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use AppBundle\Entity\User;

class UserProvider implements UserProviderInterface
{
    /**
     * @var Doctrine\ORM\EntityManager
     */
    private $entityManager;
    
    /**
     * @param Doctrine\ORM\EntityManager $entityManager
     */
    public function __construct($entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public function loadUserByUsername($username)
    {
        if ($username != null) {
            return $this->entityManager->getRepository(User::class)->findOneBy(array('username' => $username));
        }
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->entityManager->getRepository(User::class)->findOneBy(array('id' => $user->id));
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
