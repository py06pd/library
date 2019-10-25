<?php
/** tests/Security/UserProviderTest.php */
namespace App\Tests\Security;

use App\Entity\User;
use App\Security\UserProvider;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;

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
