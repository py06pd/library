<?php
/** tests/AppBundle/Services/BookServiceTest.php */
namespace Tests\AppBundle\Services;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\UserGroup;
use AppBundle\Repositories\BookRepository;
use AppBundle\Services\Auditor;
use AppBundle\Services\BookService;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * Tests for BookService
 */
class BookServiceTest extends TestCase
{
    /**
     * Instance of BookService
     * @var BookService
     */
    private $client;

    /**
     * Mock instance of Auditor
     * @var Auditor|MockObject
     */
    private $mockAuditor;

    /**
     * Mock instance of DateTimeFactory
     * @var DateTimeFactory|MockObject
     */
    private $mockDateTime;

    /**
     * Mock instance of EntityManager
     * @var EntityManager|MockObject
     */
    private $mockEm;

    protected function setUp()
    {
        $this->mockAuditor = $this->createMock(Auditor::class);
        $this->mockDateTime = $this->createMock(DateTimeFactory::class);
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->client = new BookService($this->mockEm, $this->mockDateTime, $this->mockAuditor, new NullLogger());
    }

    //<editor-fold desc="Cancel method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenCancelCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->cancel(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenCancelCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setRequestedFrom(new User()))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User())->setId(99999))))
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->cancel(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenCancelCalledThenTrueReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setRequestedFrom(new User()))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User())->setId(99999))));

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' request from cancelled");

        // Act
        $result = $this->client->cancel(123, 99999);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Delete method tests">

    /**
     * @test
     */
    public function givenSaveFailsWhenDeleteCalledThenFalseReturned()
    {
        // Arrange
        $this->mockEm->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([new Book("test1")], [new Book("test2")]);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->delete([new Book("test1"), new Book("test2")]);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenDeleteCalledThenTrueReturned()
    {
        // Arrange
        $this->mockEm->expects($this->exactly(2))
            ->method('remove')
            ->withConsecutive([(new Book("test1"))->setId(123)], [(new Book("test2"))->setId(124)]);
        $this->mockEm->expects($this->once())
            ->method('flush');

        $this->mockAuditor->expects($this->exactly(2))
            ->method('log')
            ->withConsecutive(
                [123, "test1", "book '<log.itemname>' deleted"],
                [124, "test2", "book '<log.itemname>' deleted"]
            );

        // Act
        $result = $this->client->delete([(new Book("test1"))->setId(123), (new Book("test2"))->setId(124)]);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Delivered method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenDeliveredCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->delivered(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenDeliveredCalledThenFalseReturned()
    {
        // Arrange
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setRequestedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setBorrowedFrom((new User("test one"))->setId(124))->setBorrowedTime($now))
            )
            ->willThrowException(new Exception("test exception"));

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        // Act
        $result = $this->client->delivered(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenDeliveredCalledThenTrueReturned()
    {
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setRequestedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))
                    ->setBorrowedFrom((new User("test one"))->setId(124))->setBorrowedTime($now))
            );

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' borrowed from '<log.user.name>'", [
                'user' => ['userId' => 124, 'name' => "test one"]
            ]);

        // Act
        $result = $this->client->delivered(123, 99999);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Gift method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->gift(123, 99999, (new User())->setId(124));

        // Assert
        $this->assertEquals("Invalid request", $result);
    }

    /**
     * @test
     */
    public function givenBookNotOnWishlistWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))
            ));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->gift(123, 99999, (new User())->setId(124));

        // Assert
        $this->assertEquals("This book is not on the wishlist", $result);
    }

    /**
     * @test
     */
    public function givenBookAlreadyGiftedWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setWishlist(true)
                    ->setGiftedFrom((new User())->setId(125))
            ));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->gift(123, 99999, (new User())->setId(124));

        // Assert
        $this->assertEquals("This has already been gifted", $result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenGiftCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setWishlist(true)
            ));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setWishlist(true)
                    ->setGiftedFrom((new User())->setId(124))
            ))
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->gift(123, 99999, (new User())->setId(124));

        // Assert
        $this->assertEquals("Update failed", $result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenGiftCalledThenTrueReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setWishlist(true)
            ));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setWishlist(true)
                    ->setGiftedFrom((new User())->setId(124))
            ));

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' gifted to '<log.user.name>'", [
                'user' => ['userId' => 99999, 'name' => "test one"]
            ]);

        // Act
        $result = $this->client->gift(123, 99999, (new User())->setId(124));

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Note method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenNoteCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->note(123, 99999, "note");

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenNoteCalledThenFalseReturned()
    {
        // Arrange
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User())->setId(99999)))->setNotes("note")
            ))
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->note(123, 99999, "note");

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenNoteCalledThenTrueReturned()
    {
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setNotes("old")
            ));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(
                (new UserBook((new User("test one"))->setId(99999)))->setNotes("note")
            ));

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' updated", [
                'user' => ['userId' => 99999, 'name' => "test one", 'changes' => ['notes' => ["old", "note"]]]
            ]);

        // Act
        $result = $this->client->note(123, 99999, "note");

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Reject method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenRejectCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->reject(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenRejectCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User("test user"))->setId(99999)))
                    ->setRequestedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User("test user"))->setId(99999))))
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->reject(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenRejectCalledThenTrueReturned()
    {
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User("test user"))->setId(99999)))
                    ->setRequestedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User("test user"))->setId(99999))));

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' request from '<log.user.name>' rejected", [
                'user' => ['userId' => 99999, 'name' => "test user"]
            ]);

        // Act
        $result = $this->client->reject(123, 99999);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Request method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->request(123, new User());

        // Assert
        $this->assertEquals("Invalid request", $result);
    }

    /**
     * @test
     */
    public function givenUserOwnsBookWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $user = (new User())->setId(99999);
        $book = (new Book("test1"))->setId(123)->addUser((new UserBook($user))->setOwned(true));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertEquals("You own this", $result);
    }

    /**
     * @test
     */
    public function givenUserBorrowingBookWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $user = (new User())->setId(99999);
        $book = (new Book("test1"))->setId(123)->addUser((new UserBook($user))->setBorrowedFrom(new User()));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertEquals("You are already borrowing this", $result);
    }

    /**
     * @test
     */
    public function givenUserRequestedBookWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $user = (new User())->setId(99999);
        $book = (new Book("test1"))->setId(123)->addUser((new UserBook($user))->setRequestedFrom(new User()));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertEquals("You have already requested this", $result);
    }

    /**
     * @test
     */
    public function givenNoBookAvailableWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $user = (new User())->setId(99999);
        $book = new Book("test one");

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertEquals("None available to borrow", $result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenRequestCalledThenErrorMessageReturned()
    {
        // Arrange
        $now = new DateTime('2018-10-19 18:34:12');
        $groupUser1 = (new User("test one"))->setId(124);
        $groupUser2 = (new User("test two"))->setId(125);
        $groupUser3 = (new User("test three"))->setId(126);
        $groupUser4 = (new User("test four"))->setId(127);
        $groupUser5 = (new User("test five"))->setId(128);
        $user = (new User())->setId(99999)->addGroup(
            (new UserGroup("group1"))->addUser($groupUser1)->addUser($groupUser2)->addUser($groupUser3)
                ->addUser($groupUser4)->addUser($groupUser5)
        );
        $book = (new Book("test1"))->setId(123)->addUser((new UserBook($groupUser1))->setOwned(true))
            ->addUser((new UserBook($groupUser2))->setOwned(true))
            ->addUser((new UserBook($groupUser3))->setRequestedFrom($groupUser4))
            ->addUser((new UserBook($groupUser4))->setOwned(true))
            ->addUser((new UserBook($groupUser5))->setBorrowedFrom($groupUser2));
        $expected = (new Book("test1"))->setId(123)->addUser((new UserBook($groupUser1))->setOwned(true))
            ->addUser((new UserBook($groupUser2))->setOwned(true))
            ->addUser((new UserBook($groupUser3))->setRequestedFrom($groupUser4))
            ->addUser((new UserBook($groupUser4))->setOwned(true))
            ->addUser((new UserBook($groupUser5))->setBorrowedFrom($groupUser2))
            ->addUser((new UserBook($user))->setRequestedFrom($groupUser1)->setRequestedTime($now));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected)
            ->willThrowException(new Exception("test exception"));

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertEquals("Update failed", $result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenRequestCalledThenTrueReturned()
    {
        // Arrange
        $now = new DateTime('2018-10-19 18:34:12');
        $groupUser1 = (new User("test one"))->setId(124);
        $groupUser2 = (new User("test two"))->setId(125);
        $groupUser3 = (new User("test three"))->setId(126);
        $groupUser4 = (new User("test four"))->setId(127);
        $groupUser5 = (new User("test five"))->setId(128);
        $user = (new User())->setId(99999)->addGroup(
            (new UserGroup("group1"))->addUser($groupUser1)->addUser($groupUser2)->addUser($groupUser3)
                ->addUser($groupUser4)->addUser($groupUser5)
        );
        $book = (new Book("test1"))->setId(123)->addUser((new UserBook($groupUser1))->setOwned(true))
            ->addUser((new UserBook($groupUser2))->setOwned(true))
            ->addUser((new UserBook($groupUser3))->setRequestedFrom($groupUser2))
            ->addUser((new UserBook($groupUser5))->setBorrowedFrom($groupUser4))
            ->addUser((new UserBook($groupUser4))->setOwned(true));
        $expected = (new Book("test1"))->setId(123)->addUser((new UserBook($groupUser1))->setOwned(true))
            ->addUser((new UserBook($groupUser2))->setOwned(true))
            ->addUser((new UserBook($groupUser3))->setRequestedFrom($groupUser2))
            ->addUser((new UserBook($groupUser5))->setBorrowedFrom($groupUser4))
            ->addUser((new UserBook($groupUser4))->setOwned(true))
            ->addUser((new UserBook($user))->setRequestedFrom($groupUser1)->setRequestedTime($now));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn($book);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected);

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn($now);

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(
                123,
                "test1",
                "book '<log.itemname>' requested from '<log.user.name>'",
                [
                    'user' => [
                        'userId' => 124,
                        'name' => "test one",
                    ]
                ]
            );

        // Act
        $result = $this->client->request(123, $user);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Returned method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenReturnedCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->returned(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenReturnedCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User("test user"))->setId(99999)))
                    ->setBorrowedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User("test user"))->setId(99999))))
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->returned(123, 99999);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenReturnedCalledThenTrueReturned()
    {
        $now = new DateTime('2018-10-19 18:34:12');

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test1"))->setId(123)->addUser((new UserBook((new User("test user"))->setId(99999)))
                    ->setBorrowedFrom((new User("test one"))->setId(124)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new Book("test1"))->setId(123)->addUser(new UserBook((new User("test user"))->setId(99999))));

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "test1", "book '<log.itemname>' borrowed by '<log.user.name>' returned", [
                'user' => ['userId' => 99999, 'name' => "test user"]
            ]);

        // Act
        $result = $this->client->returned(123, 99999);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Save method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenSaveCalledThenFalseReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->save((new Book("title"))->setId(123));

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenSaveCalledThenFalseReturned()
    {
        // Arrange
        $author1 = new Author("author one");
        $author2 = new Author("author two");
        $author3 = new Author("author three");

        $series1 = (new Series("series 1"))->setId(124);
        $series2 = (new Series("series 2"))->setId(125);
        $series3 = (new Series("series 3"))->setId(126);

        $book = (new Book("title"))->setId(123)->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author3)->addSeries($series1, 1)->addSeries($series3, 3);

        $expected = (new Book("title"))->setId(123)->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author2)->removeAuthor($author2)->addAuthor($author3)
            ->addSeries($series1, 1)->addSeries($series2, 2)->removeSeries($series2)->addSeries($series3, 3);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("old title"))->setId(123)->setType("test type")->setGenres(['genre1', 'genre2'])
                    ->addAuthor($author1)->addAuthor($author2)->addSeries($series1, 1)->addSeries($series2, 2)
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected)
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->save($book);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenSaveCalledThenTrueReturned()
    {
        // Arrange
        $author1 = (new Author("author one"))->setId(127);
        $author2 = (new Author("author two"))->setId(128);
        $author3 = (new Author("author three"))->setId(129);

        $series1 = (new Series("series 1"))->setId(124);
        $series2 = (new Series("series 2"))->setId(125);
        $series3 = (new Series("series 3"))->setId(126);

        $book = (new Book("title"))->setId(123)->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author3)->addSeries($series1, 1)->addSeries($series3, 3)
            ->addUser((new UserBook((new User("test one"))->setId(130)))->setOwned(true)->setRead(true));

        $expected = (new Book("title"))->setId(123)->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author2)->removeAuthor($author2)->addAuthor($author3)
            ->addSeries($series1, 1)->addSeries($series2, 2)->removeSeries($series2)->addSeries($series3, 3)
            ->addUser((new UserBook((new User("test one"))->setId(130)))->setOwned(true)->setRead(true));

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("old title"))->setId(123)->setType("test type")->setGenres(['genre1', 'genre2'])
                    ->addAuthor($author1)->addAuthor($author2)->addSeries($series1, 1)->addSeries($series2, 2)
                    ->addUser(new UserBook((new User("test one"))->setId(130)))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected);

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "title", "book '<log.itemname>' updated", ['changes' => [
                'name' => ["old title", "title"],
                'type' => ["test type", "test type 2"],
                'authors' => [
                    [
                        ['authorId' => 127, 'forename' => "author", 'surname' => "one"],
                        ['authorId' => 128, 'forename' => "author", 'surname' => "two"]
                    ],
                    [
                        ['authorId' => 127, 'forename' => "author", 'surname' => "one"],
                        ['authorId' => 129, 'forename' => "author", 'surname' => "three"]
                    ]
                ],
                'genres' => [['genre1', 'genre2'], ['genre1', 'genre3']],
                'series' => [
                    [
                        ['seriesId' => 124, 'name' => 'series 1', 'number' => 1],
                        ['seriesId' => 125, 'name' => 'series 2', 'number' => 2]
                    ],
                    [
                        ['seriesId' => 124, 'name' => 'series 1', 'number' => 1],
                        ['seriesId' => 126, 'name' => 'series 3', 'number' => 3]
                    ]
                ],
                'users' => [
                    [[
                        'bookId' => 123,
                        'userId' => 130,
                        'name' => "test one",
                        'borrowedFromId' => null,
                        'borrowedFrom' => null,
                        'borrowedTime' => null,
                        'giftedFrom' => null,
                        'notes' => null,
                        'owned' => false,
                        'read' => false,
                        'requestedFromId' => null,
                        'requestedFrom' => null,
                        'requestedTime' => null,
                        'stock' => null,
                        'wishlist' => false
                    ]],
                    [[
                        'bookId' => 123,
                        'userId' => 130,
                        'name' => "test one",
                        'borrowedFromId' => null,
                        'borrowedFrom' => null,
                        'borrowedTime' => null,
                        'giftedFrom' => null,
                        'notes' => null,
                        'owned' => true,
                        'read' => true,
                        'requestedFromId' => null,
                        'requestedFrom' => null,
                        'requestedTime' => null,
                        'stock' => 1,
                        'wishlist' => false
                    ]]
                ]
            ]]);

        // Act
        $result = $this->client->save($book);

        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function givenNewBookAndSaveSucceedsWhenSaveCalledThenTrueReturned()
    {
        // Arrange
        $author1 = (new Author("author one"))->setId(127);
        $author3 = (new Author("author three"))->setId(129);

        $series1 = (new Series("series 1"))->setId(124);
        $series3 = (new Series("series 3"))->setId(126);

        $book = (new Book("title"))->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author3)->addSeries($series1, 1)->addSeries($series3, 3);

        $expected = (new Book("title"))->setType("test type 2")->setGenres(['genre1', 'genre3'])
            ->addAuthor($author1)->addAuthor($author3)->addSeries($series1, 1)->addSeries($series3, 3);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->never())
            ->method('getBookById');

        $this->mockEm->expects($this->never())
            ->method('getRepository');

        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with($expected);

        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($expected)
            ->willReturnCallback(function ($book) {
                /** @var Book $book */
                $book->setId(123);
            });

        $this->mockAuditor->expects($this->once())
            ->method('log')
            ->with(123, "title", "book '<log.itemname>' created");

        // Act
        $result = $this->client->save($book);

        // Assert
        $this->assertTrue($result);
    }

    //</editor-fold>

    //<editor-fold desc="Search method tests">

    /**
     * @test
     */
    public function givenServiceWhenSearchCalledThenBooksReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getSearchResultCount')
            ->with(
                [['a{nonce}.authorId', 123]],
                [['bu{nonce}.owned', 124]],
                ["125"]
            )
            ->willReturn(3);
        $mockRepo->expects($this->once())
            ->method('getSearchResults')
            ->with(
                [['a{nonce}.authorId', 123]],
                [['bu{nonce}.owned', 124]],
                ["125"]
            )
            ->willReturn([126, 127, 128, 127]);
        $mockRepo->expects($this->once())
            ->method('getBooksById')
            ->with([126, 127, 128])
            ->willReturn([new Book("test1"), new Book("test2")]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $total = 0;
        $filters = [
            (object)['field' => 'author', 'operator' => 'equals', 'value' => 123],
            (object)['field' => 'search', 'operator' => 'like', 'value' => ["125"]],
            (object)['field' => 'owner', 'operator' => 'does not equal', 'value' => 124]
        ];

        // Act
        $result = $this->client->search($total, $filters, 0);

        // Assert
        $this->assertEquals(3, $total);
        $this->assertEquals([new Book("test1"), new Book("test2")], $result);
    }

    /**
     * @test
     */
    public function givenStartIsMinusOneWhenSearchCalledThenBooksReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getSearchResultCount')
            ->with([], [], [])
            ->willReturn(3);
        $mockRepo->expects($this->once())
            ->method('getSearchResults')
            ->with([], [], [])
            ->willReturn([126, 127, 128]);
        $mockRepo->expects($this->once())
            ->method('getBooksById')
            ->with([126, 127, 128])
            ->willReturn([new Book("test1"), new Book("test2")]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $total = 0;

        // Act
        $result = $this->client->search($total, [], -1);

        // Assert
        $this->assertEquals(3, $total);
        $this->assertEquals([new Book("test1"), new Book("test2")], $result);
    }

    //</editor-fold>
}
