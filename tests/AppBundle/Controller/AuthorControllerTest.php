<?php
/** tests/AppBundle/Controller/AuthorControllerTest.php */
namespace Tests\AppBundle\Controller;

use AppBundle\Controller\AuthorController;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthor;
use AppBundle\Repositories\BookRepository;
use AppBundle\Services\BookService;
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
 * Tests for AuthorController
 */
class AuthorControllerTest extends TestCase
{
    /**
     * Instance of AuthorController
     * @var AuthorController
     */
    private $client;
    
    /**
     * Mock instance of BookService
     * @var BookService|MockObject
     */
    private $mockBookService;
    
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
        $this->mockBookService = $this->createMock(BookService::class);
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->user = (new User())->setId(99999);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new AnonymousToken("s3cr3t", $this->user));

        $this->client = new AuthorController($this->mockEm, $this->mockBookService, $tokenStorage, new NullLogger());
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="GetAuthor method tests">

    /**
     * @test
     */
    public function givenAuthorDoesNotExistWhenGetAuthorCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['authorId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Author::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getAuthor(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenAuthorExistsWhenGetAuthorCalledThenDataReturned()
    {
        // Arrange
        $author = (new Author("test one"))->setId(123);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls($author, new UserAuthor(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [(object)['field' => 'author', 'operator' => 'equals', 'value' => [123]]])
            ->willReturn([new Book("test2"), new Book("test1")]);

        // Act
        $result = $this->client->getAuthor(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'author' => $author,
                'books' => [new Book("test2"), new Book("test1")],
                'tracking' => true
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Track method tests">

    /**
     * @test
     */
    public function givenAuthorDoesNotExistWhenTrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['authorId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Author::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->track(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenAuthorAlreadyTrackedWhenTrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), new UserAuthor(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->track(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You already track this"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenTrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with(new UserAuthor(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->track(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenTrackCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with(new UserAuthor(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->track(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Tracked method tests">

    /**
     * @test
     */
    public function givenControllerWhenTrackedCalledThenTrackedauthorIdsReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => 99999])
            ->willReturn([
                new UserAuthor(123, 99999),
                new UserAuthor(124, 99999)
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserAuthor::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->tracked();

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'authorIds' => [123, 124]
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Untrack method tests">

    /**
     * @test
     */
    public function givenAuthorDoesNotExistWhenUntrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['authorId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Author::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->untrack(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenAuthorNotTrackedWhenUntrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->untrack(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You are not tracking this"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenUntrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), new UserAuthor(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('remove')
            ->with(new UserAuthor(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->untrack(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenUntrackCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['authorId' => 123]], [['authorId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Author("test one"))->setId(123), new UserAuthor(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Author::class], [UserAuthor::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('remove')
            ->with(new UserAuthor(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->untrack(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>
}
