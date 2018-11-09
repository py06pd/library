<?php
/** tests/AppBundle/Controller/GroupControllerTest.php */
namespace Tests\AppBundle\Controller;

use AppBundle\Controller\GroupController;
use AppBundle\Entity\User;
use AppBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests for GroupController
 */
class GroupControllerTest extends TestCase
{
    /**
     * Instance of GroupController
     * @var GroupController
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

    protected function setUp()
    {
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->user = (new User())->setId(99999)->setRoles(['ROLE_USER']);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new AnonymousToken("s3cr3t", $this->user));

        $this->client = new GroupController($this->mockEm, $tokenStorage, new NullLogger());
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="getGroup method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleAndUserNotInGroupWhenGetGroupCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getGroup(new Request([], ['groupId' => 123]));

        // Assert
        $this->assertEquals(
            ['status' => "error", 'errorMessage' => "Insufficient user rights"],
            json_decode($result->getContent(), true)
        );
    }

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleAndUserInGroupWhenGetGroupCalledThenGroupDetailsReturned()
    {
        // Arrange
        $userGroup = (new UserGroup("group1"))->setId(123)
            ->addUser((new User("test one"))->setId(124))
            ->addUser((new User("test two"))->setId(125));
        $this->user->addGroup($userGroup);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['groupId' => 123])
            ->willReturn($userGroup);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserGroup::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getGroup(new Request([], ['groupId' => 123]));

        // Assert
        $this->assertEquals(
            [
                'status' => "OK",
                'data' => [
                    'groupId' => 123,
                    'name' => "group1",
                    'users' => [
                        ['userId' => 124, 'name' => "test one"],
                        ['userId' => 125, 'name' => "test two"]
                    ]
                ],
                'users' => []
            ],
            json_decode($result->getContent(), true)
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleWhenGetGroupCalledThenGroupDetailsAndUsersReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $userGroup = (new UserGroup("group1"))->setId(123)
            ->addUser((new User("test one"))->setId(124))
            ->addUser((new User("test two"))->setId(125));
        $this->user->addGroup($userGroup);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['groupId' => 123])
            ->willReturn($userGroup);
        $mockRepo->expects($this->once())
            ->method('findAll')
            ->willReturn([
                (new User("test three"))->setId(126),
                (new User("test four"))->setId(127)
            ]);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([UserGroup::class], [User::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getGroup(new Request([], ['groupId' => 123]));

        // Assert
        $this->assertEquals(
            [
                'status' => "OK",
                'data' => [
                    'groupId' => 123,
                    'name' => "group1",
                    'users' => [
                        ['userId' => 124, 'name' => "test one"],
                        ['userId' => 125, 'name' => "test two"]
                    ]
                ],
                'users' => [
                    [
                        'userId' => 126,
                        'name' => "test three",
                        'username' => null,
                        'roles' => ['ROLE_ANONYMOUS', 'ROLE_USER'],
                        'groups' => []
                    ],
                    [
                        'userId' => 127,
                        'name' => "test four",
                        'username' => null,
                        'roles' => ['ROLE_ANONYMOUS', 'ROLE_USER'],
                        'groups' => []
                    ]
                ]
            ],
            json_decode($result->getContent(), true)
        );
    }

    //</editor-fold>

    //<editor-fold desc="save method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleWhenSaveCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->save(new Request([], ['data' => "[]"]));

        // Assert
        $this->assertEquals(
            ['status' => "error", 'errorMessage' => "Insufficient user rights"],
            json_decode($result->getContent(), true)
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleAndGroupNotFoundWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['groupId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserGroup::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode([
            'groupId' => 123,
            'name' => "group0",
            'users' => [['userId' => 125], ['userId' => 126]]
        ])]));

        // Assert
        $this->assertEquals(
            ['status' => "error", 'errorMessage' => "Invalid form data"],
            json_decode($result->getContent(), true)
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleAndSaveFailsWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $userGroup = (new UserGroup("group1"))->setId(123)
            ->addUser((new User("test one"))->setId(124))
            ->addUser((new User("test two"))->setId(125));

        $expected = (new UserGroup("group0"))->setId(123);
        $expected->addUser((new User("test one"))->setId(124))
            ->addUser((new User("test two"))->setId(125))
            ->removeUser((new User("test one"))->setId(124))
            ->addUser((new User("test three"))->setId(126));

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['groupId' => 123])
            ->willReturn($userGroup);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => [125, 126]])
            ->willReturn([
                (new User("test two"))->setId(125),
                (new User("test three"))->setId(126)
            ]);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([UserGroup::class], [User::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected)
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode([
            'groupId' => 123,
            'name' => "group0",
            'users' => [['userId' => 125], ['userId' => 126]]
        ])]));

        // Assert
        $this->assertEquals(
            ['status' => "error", 'errorMessage' => "Update failed"],
            json_decode($result->getContent(), true)
        );
    }

    /**
     * @test
     */
    public function givenUserHasAdminRoleAndSaveSucceedsWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(['ROLE_ADMIN']);

        $expected = new UserGroup("group0");
        $expected->addUser((new User("test two"))->setId(125))
            ->addUser((new User("test three"))->setId(126));

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->never())
            ->method('findOneBy');
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => [125, 126]])
            ->willReturn([
                (new User("test two"))->setId(125),
                (new User("test three"))->setId(126)
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected);

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode([
            'groupId' => null,
            'name' => "group0",
            'users' => [['userId' => 125], ['userId' => 126]]
        ])]));

        // Assert
        $this->assertEquals(['status' => "OK"], json_decode($result->getContent(), true));
    }

    //</editor-fold>
}
