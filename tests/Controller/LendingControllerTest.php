<?php
/** tests/Controller/LendingControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\LendingController;
use App\Entity\Book;
use App\Entity\User;
use App\Entity\UserBook;
use App\Repository\BookRepository;
use App\Services\BookService;
use DateTime;
use Doctrine\ORM\EntityManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests for LendingController
 */
class LendingControllerTest extends TestCase
{
    /**
     * Instance of LendingController
     * @var LendingController
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

        $this->user = (new User())->setId(99999);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->user, "test"));

        $this->client = new LendingController($this->mockEm, $this->mockBookService, $tokenStorage);
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="Cancel method tests">

    /**
     * @test
     */
    public function givenCancelFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('cancel')
            ->with(123, 99999)
            ->willReturn(false);

        // Act
        $result = $this->client->cancel(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenCancelSucceedsWhenRequestCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('cancel')
            ->with(123, 99999)
            ->willReturn(true);

        // Act
        $result = $this->client->cancel(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Delivered method tests">

    /**
     * @test
     */
    public function givenDeliveredFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('delivered')
            ->with(123, 99999)
            ->willReturn(false);

        // Act
        $result = $this->client->delivered(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenDeliveredSucceedsWhenRequestCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('delivered')
            ->with(123, 99999)
            ->willReturn(true);

        // Act
        $result = $this->client->delivered(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="GetLending method tests">

    /**
     * @test
     */
    public function givenControllerWhenGetLendingCalledThenBooksReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getLending')
            ->with(99999)
            ->willReturn([
                (new Book("test1"))->addUser((new UserBook((new User("test one"))->setId(123)))
                    ->setBorrowedFrom($this->user)->setBorrowedTime(new DateTime('2018-10-23 12:34:56'))),
                (new Book("test2"))->addUser((new UserBook((new User("test two"))->setId(124)))
                    ->setRequestedFrom($this->user)->setRequestedTime(new DateTime('2018-10-23 12:34:57'))),
                (new Book("test3"))->addUser((new UserBook($this->user))
                    ->setBorrowedFrom((new User("test three"))->setId(125))
                    ->setBorrowedTime(new DateTime('2018-10-23 12:34:58'))),
                (new Book("test4"))->addUser((new UserBook($this->user))
                    ->setRequestedFrom((new User("test four"))->setId(126))
                    ->setRequestedTime(new DateTime('2018-10-23 12:34:59')))
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getLending();

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'borrowed' => [(new Book("test1"))->addUser((new UserBook((new User("test one"))->setId(123)))
                    ->setBorrowedFrom($this->user)->setBorrowedTime(new DateTime('2018-10-23 12:34:56')))],
                'borrowing' => [(new Book("test3"))->addUser((new UserBook($this->user))
                    ->setBorrowedFrom((new User("test three"))->setId(125))
                    ->setBorrowedTime(new DateTime('2018-10-23 12:34:58')))],
                'requested' => [(new Book("test2"))->addUser((new UserBook((new User("test two"))->setId(124)))
                    ->setRequestedFrom($this->user)->setRequestedTime(new DateTime('2018-10-23 12:34:57')))],
                'requesting' => [(new Book("test4"))->addUser((new UserBook($this->user))
                    ->setRequestedFrom((new User("test four"))->setId(126))
                    ->setRequestedTime(new DateTime('2018-10-23 12:34:59')))]
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Reject method tests">

    /**
     * @test
     */
    public function givenRejectFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('reject')
            ->with(123, 124)
            ->willReturn(false);

        // Act
        $result = $this->client->reject(new Request([], ['bookId' => 123, 'userId' => 124]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenRejectSucceedsWhenRequestCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('reject')
            ->with(123, 124)
            ->willReturn(true);

        // Act
        $result = $this->client->reject(new Request([], ['bookId' => 123, 'userId' => 124]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Request method tests">
    
    /**
     * @test
     */
    public function givenRequestFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('request')
            ->with(123, $this->user)
            ->willReturn("error!");

        // Act
        $result = $this->client->request(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "error!"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenRequestSucceedsWhenRequestCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('request')
            ->with(123, $this->user)
            ->willReturn(true);

        // Act
        $result = $this->client->request(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Returned method tests">

    /**
     * @test
     */
    public function givenReturnedFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('returned')
            ->with(123, 124)
            ->willReturn(false);

        // Act
        $result = $this->client->returned(new Request([], ['bookId' => 123, 'userId' => 124]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Update failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenReturnedSucceedsWhenRequestCalledThenOKStatusReturned()
    {
        // Arrange
        $this->mockBookService->expects($this->once())
            ->method('returned')
            ->with(123, 124)
            ->willReturn(true);

        // Act
        $result = $this->client->returned(new Request([], ['bookId' => 123, 'userId' => 124]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>
}
