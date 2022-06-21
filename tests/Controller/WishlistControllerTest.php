<?php
/** tests/Controller/WishlistControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\WishlistController;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Entity\UserGroup;
use App\Services\BookService;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests for WishlistController
 */
class WishlistControllerTest extends TestCase
{
    /**
     * Instance of WishlistController
     * @var WishlistController
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

    protected function setUp(): void
    {
        $this->mockBookService = $this->createMock(BookService::class);
        $this->mockEm = $this->createMock(EntityManager::class);

        $group = (new UserGroup("group1"))->setId(122)->addUser((new User())->setId(123)->setName("testUser"));
        $this->user = (new User())->setId(99999)->setName("test one")->setUsername("test01")->setRoles(['ROLE_USER'])
            ->addGroup($group);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->user, "test"));

        $this->client = new WishlistController($this->mockEm, $this->mockBookService, $tokenStorage);
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="GetBooks method tests">

    /**
     * @test
     */
    public function givenUserNotInGroupWhenGetBooksCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getBooks(new Request([], ['userId' => 124]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserInGroupWhenGetBooksCalledThenBooksReturned()
    {
        // Arrange
        $books = [
            (new Book("test1"))->addUser((new UserBook((new User("test one"))->setId(123)))),
            (new Book("test2"))->addUser(
                (new UserBook((new User("test one"))->setId(123)))->setGiftedFrom(new User("test two"))
            )
        ];

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [(object)['field' => 'wishlist', 'operator' => 'equals', 'value' => [123]]], -1)
            ->willReturn($books);

        // Act
        $result = $this->client->getBooks(new Request([], ['userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'books' => $books]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserWhenGetBooksCalledThenBooksReturned()
    {
        // Arrange
        $books = [
            (new Book("test1"))->addUser((new UserBook((new User("test one"))->setId(99999)))),
            (new Book("test2"))->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setGiftedFrom(new User("test two"))
            )
        ];

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [(object)['field' => 'wishlist', 'operator' => 'equals', 'value' => [99999]]], -1)
            ->willReturn($books);

        // Act
        $result = $this->client->getBooks(new Request([], ['userId' => 99999]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'books' => $books]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Gift method tests">

    /**
     * @test
     */
    public function givenUserNotInGroupWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->never())
            ->method('gift');

        // Act
        $result = $this->client->gift(new Request([], ['bookId' => 123, 'userId' => 124]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenGiftFailsWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('gift')
            ->with(124, 123, $this->user)
            ->willReturn("error!");

        // Act
        $result = $this->client->gift(new Request([], ['bookId' => 124, 'userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "error!"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenGiftSucceedsWhenGiftCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('gift')
            ->with(124, 123, $this->user)
            ->willReturn(true);

        // Act
        $result = $this->client->gift(new Request([], ['bookId' => 124, 'userId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="SaveNote method tests">

    /**
     * @test
     */
    public function givenUserNotInGroupWhenSaveNoteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->never())
            ->method('note');

        // Act
        $result = $this->client->saveNote(new Request([], ['bookId' => 123, 'userId' => 124, 'text' => "testing"]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenNoteFailsWhenSaveNoteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('note')
            ->with(124, 123, "testing")
            ->willReturn(false);

        // Act
        $result = $this->client->saveNote(new Request([], ['bookId' => 124, 'userId' => 123, 'text' => "testing"]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenNoteSucceedsWhenSaveNoteCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('note')
            ->with(124, 123, "testing")
            ->willReturn(true);

        // Act
        $result = $this->client->saveNote(new Request([], ['bookId' => 124, 'userId' => 123, 'text' => "testing"]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>
}
