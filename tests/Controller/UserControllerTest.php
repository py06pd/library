<?php
/** tests/Controller/UserControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\UserController;
use App\Entity\User;
use App\Entity\UserSession;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests for UserController
 */
class UserControllerTest extends TestCase
{
    /**
     * Instance of UserController
     * @var UserController
     */
    private $client;
    
    /**
     * Mock instance of EntityManager
     * @var EntityManager|MockObject
     */
    private $mockEm;

    /**
     * User
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->user = (new User())->setId(99999)->setRoles(['ROLE_USER']);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->user, "test"));

        $this->client = new UserController("s3cr3t", $this->mockEm, $tokenStorage, new NullLogger());
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="Delete method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleWhenDeleteCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->delete(new Request([], ['userIds' => [123, 124]]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenDeleteFailsWhenDeleteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => [123, 124]])
            ->willReturn([(new User())->setId(123), (new User())->setId(124)]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([(new User())->setId(123)], [(new User())->setId(124)]);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception('test exception'));

        // Act
        $result = $this->client->delete(new Request([], ['userIds' => [123, 124]]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Delete failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenDeleteSucceedsWhenDeleteCalledThenOKStatusReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => [123, 124]])
            ->willReturn([(new User())->setId(123), (new User())->setId(124)]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([(new User())->setId(123)], [(new User())->setId(124)]);
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->delete(new Request([], ['userIds' => [123, 124]]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="GetSessions method tests">

    /**
     * @test
     */
    public function givenRequestedUserIsNotAppUserWhenGetSessionsCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getSessions(new Request([], ['userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid form data"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenRequestedUserIsAppUserWhenGetSessionsCalledThenSessionsReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => 99999])
            ->willReturn([
                new UserSession(99999, new DateTime('2018-10-17'), 's1', 'd1'),
                new UserSession(99999, new DateTime('2018-10-18'), 's2', 'd2')
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getSessions(new Request([], ['userId' => 99999]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'data' => [
                ['device' => 'd1', 'created' => '17/10/2018 01:00:00', 'lastAccessed' => '17/10/2018 01:00:00'],
                ['device' => 'd2', 'created' => '18/10/2018 01:00:00', 'lastAccessed' => '18/10/2018 01:00:00']
            ]]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="GetUserDetails method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleWhenGetUserDetailsThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getUserDetails(new Request([], ['userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleWhenGetUserDetailsThenUserDetailsReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123])
            ->willReturn((new User())->setId(123)->setName("test one")->setUsername("test01")->setRoles(['ROLE_TEST']));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getUserDetails(new Request([], ['userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'data' => [
                'userId' => 123,
                'name' => "test one",
                'username' => "test01",
                'roles' => ['ROLE_TEST'],
                'groups' => []
            ]]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="GetUsers method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleWhenGetUsersThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getUsers();

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleWhenGetUsersThenUsersReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findAll')
            ->willReturn([
                (new User())->setId(123)->setName("test one")->setUsername("test01")->setRoles(['ROLE_TEST1']),
                (new User())->setId(124)->setName("test two")->setUsername("test02")->setRoles(['ROLE_TEST2'])
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getUsers();

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'users' => [
                [
                    'userId' => 123,
                    'name' => "test one",
                    'username' => "test01",
                    'roles' => ['ROLE_TEST1'],
                    'groups' => []
                ],
                [
                    'userId' => 124,
                    'name' => "test two",
                    'username' => "test02",
                    'roles' => ['ROLE_TEST2'],
                    'groups' => []
                ]
            ]]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Save method tests">

    /**
     * @test
     */
    public function givenRequestedUserIsNotAppUserWhenSaveCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->save(new Request([], ['userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid form data"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenNameBlankWhenSaveCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->save(new Request([], ['userId' => 99999]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "alert", 'errorMessage' => "Invalid form data"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $user = (new User())->setId(99999)->setName("testone")->setUsername("test1")->setRoles(['ROLE_TEST'])
            ->setPassword("testone");

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 99999])
            ->willReturn($user);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->save(new Request([], [
            'userId' => 99999,
            'name' => "test one",
            'newUsername' => "test01",
            'newPassword' => "testing"
        ]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
        $this->assertEquals("test one", $user->getName());
        $this->assertEquals("test01", $user->getUsername());
        $this->assertEquals(['ROLE_TEST'], $user->getRoles());
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenSaveCalledThenOKStatusReturned()
    {
        // Arrange
        $user = (new User())->setId(99999)->setName("testone")->setUsername("test1")->setRoles(['ROLE_TEST'])
            ->setPassword("testone");

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 99999])
            ->willReturn($user);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->save(new Request([], [
            'userId' => 99999,
            'name' => "test one",
            'newUsername' => "test01",
            'newPassword' => "testing"
        ]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
        $this->assertEquals("test one", $user->getName());
        $this->assertEquals("test01", $user->getUsername());
        $this->assertEquals(['ROLE_TEST'], $user->getRoles());
    }

    /**
     * @test
     */
    public function givenNewUserAndSaveSucceedsWhenSaveCalledThenOKStatusReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $user = new User();

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->never())
            ->method('findOneBy');

        $this->mockEm->expects($this->never())
            ->method('getRepository');
        $this->mockEm->expects($this->once())
            ->method('persist');
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willReturnCallback(function ($flush) use (&$user) {
                $user = $flush;
            });

        // Act
        $result = $this->client->save(new Request([], [
            'name' => "test one",
            'newUsername' => "test01",
            'newPassword' => "testing"
        ]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
        $this->assertEquals("test one", $user->getName());
        $this->assertEquals("test01", $user->getUsername());
        $this->assertEquals(['ROLE_USER', 'ROLE_ANONYMOUS'], $user->getRoles());
    }

    //</editor-fold>
}
