<?php
/** tests/AppBundle/Security/UserProviderTest.php */
namespace Tests\AppBundle\Security;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSession;
use AppBundle\Security\UserAuthenticator;
use AppBundle\Security\UserProvider;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class UserProviderTest
 * @package Tests\AppBundle\Security
 */
class UserProviderTest extends TestCase
{
    /**
     * Instance of UserProvider
     * @var UserProvider
     */
    private $client;
    
    /**
     * Mock instance of EntityManager
     * @var EntityManager|MockObject
     */
    private $mockEm;
    
    protected function setUp()
    {
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->client = new UserProvider($this->mockEm);
    }
    
    /**
     * @test
     */
    public function givenUserDoesNotExistWhenLoadUserByUsernameCalledThenNullReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test01'])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->loadUserByUsername('test01');

        // Assert
        $this->assertNull($result);
    }

    /**
     * @test
     */
    public function givenUserDoesNotExistWhenLoadUserByUsernameCalledThenUserReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => 'test01'])
            ->willReturn(new User('test01'));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->loadUserByUsername('test01');

        // Assert
        $this->assertEquals(new User('test01'), $result);
    }

    /**
     * @test
     */
    public function givenUserWhenRefreshUserCalledThenUserReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['id' => 123])
            ->willReturn((new User('test01'))->setId(123));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->refreshUser((new User('test02'))->setId(123));

        // Assert
        $this->assertEquals((new User('test01'))->setId(123), $result);
    }

    /**
     * @test
     */
    public function givenUserClassWhenSupportsClassCalledThenTrueReturned()
    {
        // Act
        $result = $this->client->supportsClass(User::class);

        // Assert
        $this->assertTrue($result);
    }
}
