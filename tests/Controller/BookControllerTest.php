<?php
/** tests/Controller/BookControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\BookController;
use App\Entity\Author;
use App\Entity\Book;
use App\Entity\Genre;
use App\Entity\Series;
use App\Entity\Type;
use App\Entity\User;
use App\Entity\UserBook;
use App\Entity\UserGroup;
use App\Repository\BookRepository;
use App\Services\BookService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Templating\EngineInterface;

/**
 * Tests for BookController
 */
class BookControllerTest extends TestCase
{
    /**
     * Instance of BookController
     * @var BookController
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
     * Mock instance of EngineInterface
     * @var EngineInterface|MockObject
     */
    private $mockTemplating;

    /**
     * User
     * @var User
     */
    private $user;

    protected function setUp(): void
    {
        $this->mockBookService = $this->createMock(BookService::class);
        $this->mockEm = $this->createMock(EntityManager::class);
        $this->mockTemplating = $this->createMock(EngineInterface::class);

        $group = (new UserGroup("group1"))->setId(122)->addUser((new User())->setId(123)->setName("testUser"));
        $this->user = (new User())->setId(99999)->setName("test one")->setUsername("test01")->setRoles(['ROLE_USER'])
            ->addGroup($group);
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new PreAuthenticatedToken($this->user, "test"));

        $this->client = new BookController($this->mockEm, $this->mockBookService, $tokenStorage);

        $container = new Container();
        $container->set('twig', $this->mockTemplating);
        $this->client->setContainer($container);
    }

    //<editor-fold desc="Delete method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveLibrarianRoleWhenDeleteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_USER"]);

        // Act
        $result = $this->client->delete(new Request());

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookNotFoundWhenDeleteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['bookId' => [123, 124]])
            ->willReturn([]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->delete(new Request([], ['bookIds' => [123, 124]]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid form data"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleAndNotOnlyUserWhenDeleteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['bookId' => [123, 124]])
            ->willReturn([
                (new Book("test1"))->setCreator(new User("test01")),
                (new Book("test2"))->setCreator(new User("test02"))
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->delete(new Request([], ['bookIds' => [123, 124]]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenDeleteCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $books = [
            (new Book("test1"))->setCreator($this->user)->addUser(new UserBook($this->user)),
            (new Book("test2"))->setCreator($this->user)
        ];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['bookId' => [123, 124]])
            ->willReturn($books);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('delete')
            ->with($books)
            ->willReturn(false);

        // Act
        $result = $this->client->delete(new Request([], ['bookIds' => [123, 124]]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Delete failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenDeleteCalledThenOKStatusReturned()
    {
        /// Arrange
        $this->user->setRoles(["ROLE_ADMIN","ROLE_LIBRARIAN"]);

        $books = [new Book("test1"), new Book("test2")];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['bookId' => [123, 124]])
            ->willReturn($books);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('delete')
            ->with($books)
            ->willReturn(true);

        // Act
        $result = $this->client->delete(new Request([], ['bookIds' => [123, 124]]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Disown method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenDisownCalledThenErrorMessageReturned()
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
        $result = $this->client->disown(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookNotOwnedWhenDisownCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->disown(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You don't own this"], 15),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenDisownCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(true)));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(false)))
            ->willReturn(false);

        // Act
        $result = $this->client->disown(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenDisownCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(true)));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(false)))
            ->willReturn(true);

        // Act
        $result = $this->client->disown(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Homepage method tests">

    /**
     * @test
     */
    public function givenControllerWhenHomepageCalledThenUserReturned()
    {
        $this->mockTemplating->expects($this->once())
            ->method('render')
            ->with('main/index.html.twig', [
                'user' => [
                    'userId' => 99999,
                    'name' => "test one",
                    'username' => "test01",
                    'roles' => ['ROLE_USER'],
                    'groups' => [
                        [
                            'groupId' => 122,
                            'name' => "group1",
                            'users' => [['userId' => 123, 'name' => "testUser"]]
                        ]
                    ]
                ],
                'query' => []
            ])
        ->willReturn('output');
        
        $this->client->homepage(new Request());
    }

    //</editor-fold>

    //<editor-fold desc="GetBookFilters method tests">

    /**
     * @test
     */
    public function givenAuthorFieldWhenGetBooksCalledThenAuthorsReturned()
    {
        // Arrange
        $data = [new Author("test one"), new Author("test two")];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC', 'forename' => 'ASC'])
            ->willReturn($data);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Author::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getBookFilters(new Request([], ['field' => "author"]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK", 'data' => $data]), $result->getContent());
    }

    /**
     * @test
     */
    public function givenGenresFieldWhenGetBooksCalledThenGenresReturned()
    {
        // Arrange
        $data = [
            (new Genre('test1'))->setId(124),
            (new Genre('test2'))->setId(123),
            (new Genre('test3'))->setId(125)
        ];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn($data);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Genre::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getBookFilters(new Request([], ['field' => "genre"]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'data' => [
                ['genreId' => 124, 'name' => 'test1'],
                ['genreId' => 123, 'name' => 'test2'],
                ['genreId' => 125, 'name' => 'test3']
            ]]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSeriesFieldWhenGetBooksCalledThenSeriesReturned()
    {
        // Arrange
        $data = [new Series("test one"), new Series("test two")];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn($data);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Series::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getBookFilters(new Request([], ['field' => "series"]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK", 'data' => $data]), $result->getContent());
    }

    /**
     * @test
     */
    public function givenTypesFieldWhenGetBooksCalledThenTypesReturned()
    {
        // Arrange
        $data = [
            (new Type('test1'))->setId(124),
            (new Type('test2'))->setId(123)
        ];

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with([], ['name' => 'ASC'])
            ->willReturn($data);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Type::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getBookFilters(new Request([], ['field' => "type"]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "OK", 'data' => [
                ['typeId' => 124, 'name' => 'test1'],
                ['typeId' => 123, 'name' => 'test2']
            ]]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="GetBooks method tests">

    /**
     * @test
     */
    public function givenControllerWhenGetBooksCalledThenBookListReturned()
    {
        // Arrange
        $books = new ArrayCollection();
        $books->add(new Book("book test"));
        $total = 0;
        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with($total, [(object)['test' => "testing"]], 15)
            ->willReturnCallback(function (&$bookTotal) use ($books) {
                $bookTotal = 123;
                return $books;
            });

        // Act
        $result = $this->client->getBooks(new Request([], [
            'start' => 15,
            'filters' => json_encode([['test' => "testing"]])
        ]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'data' => $books,
                'total' => 123
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="GetBook method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenGetBookCalledThenErrorMessageReturned()
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
        $result = $this->client->getBook(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookExistsWhenGetBookCalledThenDataReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setId(123));
        $mockRepo->expects($this->exactly(4))
            ->method('findBy')
            ->withConsecutive(
                [[], ['name' => "ASC", 'forename' => "ASC"]],
                [[], ['name' => "ASC"]],
                [[], ['name' => "ASC"]],
                [[], ['name' => "ASC"]]
            )
            ->willReturnOnConsecutiveCalls(["authors"], ["genres"], ["series"], ["types"]);

        $this->mockEm->expects($this->exactly(5))
            ->method('getRepository')
            ->withConsecutive([Book::class], [Author::class], [Genre::class], [Series::class], [Type::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getBook(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'data' => (new Book("test1"))->setId(123),
                'authors' => ["authors"],
                'genres' => ['genres'],
                'types' => ['types'],
                'series' => ["series"]
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Own method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenOwnCalledThenErrorMessageReturned()
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
        $result = $this->client->own(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookAlreadyOwnedWhenOwnCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(123)))->setOwned(true)));
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123])
            ->willReturn((new User())->setId(123));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Book::class], [User::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->own(new Request([], ['bookId' => 123, 'userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "User already owns this"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenOwnCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(true)))
            ->willReturn(false);

        // Act
        $result = $this->client->own(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenOwnCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(new Book("test title"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook($this->user))->setOwned(true)))
            ->willReturn(true);

        // Act
        $result = $this->client->own(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Read method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenReadCalledThenErrorMessageReturned()
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
        $result = $this->client->read(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookAlreadyReadWhenReadCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(123)))->setRead(true)));
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123])
            ->willReturn((new User())->setId(123));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Book::class], [User::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->read(new Request([], ['bookId' => 123, 'userId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "User has already read this"], 15),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenReadCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setRead(true)))
            ->willReturn(false);

        // Act
        $result = $this->client->read(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenReadCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(new Book("test title"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook($this->user))->setRead(true)))
            ->willReturn(true);

        // Act
        $result = $this->client->read(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Save method tests">

    /**
     * @test
     */
    public function givenUserDoesNotHaveLibrarianRoleWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_USER"]);

        // Act
        $result = $this->client->save(new Request());

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookNotFoundWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->never())
            ->method('save');

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode(['bookId' => 123])]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid form data"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenUserDoesNotHaveAdminRoleAndNotOnlyUserWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setCreator(new User("test01")));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->never())
            ->method('save');

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode(['bookId' => 123])]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Insufficient user rights"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenSaveCalledThenErrorMessageReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_LIBRARIAN"]);

        $expected = (new Book("test1"))->setId(123)->setType(new Type("type1"))
            ->addGenre((new Genre('genres1'))->setId(128))
            ->addGenre(new Genre('genres2'))
            ->addAuthor(new Author('author1'))->addAuthor(new Author("testy testing"))
            ->addSeries(new Series('series1', 'sequence'), 126)
            ->addSeries(new Series('series2', 'sequence'), 127)
            ->setCreator($this->user);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->exactly(3))
            ->method('findOneBy')
            ->withConsecutive([['genreId' => 128]], [['authorId' => 124]], [['seriesId' => 125]])
            ->willReturnOnConsecutiveCalls(
                (new Genre('genres1'))->setId(128),
                new Author("author1"),
                new Series('series1', 'sequence')
            );
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test1"))->setCreator($this->user)->addUser(new UserBook($this->user)));

        $this->mockEm->expects($this->exactly(4))
            ->method('getRepository')
            ->withConsecutive([Book::class], [Genre::class], [Author::class], [Series::class])
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with($expected)
            ->willReturn(false);

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode([
            'bookId' => 123,
            'name' => "test1",
            'type' => ['name' => "type1"],
            'genres' => [['genreId' => 128], ['name' => 'genres2']],
            'authors' => [['authorId' => 124], ['forename' => 'testy testing']],
            'series' => [
                ['seriesId' => 125, 'name' => "series1", 'number' => 126],
                ['name' => "series2", 'number' => 127]
            ]
        ])]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenSaveCalledThenOKStatusAndNewAuthorsAndNewSeriesReturned()
    {
        // Arrange
        $this->user->setRoles(["ROLE_ADMIN","ROLE_LIBRARIAN"]);

        $expected = (new Book("test1"))->setId(123)->setType((new Type("type1"))->setId(129))
            ->addGenre((new Genre('genres1'))->setId(128))
            ->addGenre(new Genre('genres2'))
            ->addAuthor(new Author('author1'))->addAuthor(new Author("testy testing"))
            ->addSeries(new Series('series1', 'sequence'), 126)
            ->addSeries(new Series('series2', 'sequence'), 127)
            ->setCreator($this->user);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->exactly(4))
            ->method('findOneBy')
            ->withConsecutive([['typeId' => 129]], [['genreId' => 128]], [['authorId' => 124]], [['seriesId' => 125]])
            ->willReturnOnConsecutiveCalls(
                (new Type('type1'))->setId(129),
                (new Genre('genres1'))->setId(128),
                new Author("author1"),
                new Series('series1', 'sequence')
            );
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(new Book("test1"));

        $this->mockEm->expects($this->exactly(5))
            ->method('getRepository')
            ->withConsecutive([Book::class], [Type::class], [Genre::class], [Author::class], [Series::class])
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with($expected)
            ->willReturn(true);

        // Act
        $result = $this->client->save(new Request([], ['data' => json_encode([
            'bookId' => 123,
            'name' => "test1",
            'type' => ['typeId' => 129],
            'genres' => [['genreId' => 128], ['name' => 'genres2']],
            'authors' => [['authorId' => 124], ['forename' => 'testy testing']],
            'series' => [
                ['seriesId' => 125, 'name' => "series1", 'number' => 126],
                ['name' => "series2", 'number' => 127]
            ]
        ])]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'newAuthors' => [new Author("testy testing")],
                'newGenres' => [new Genre("genres2")],
                'newSeries' => [new Series('series2', 'sequence')],
                'newTypes' => []
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Unread method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenUnreadCalledThenErrorMessageReturned()
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
        $result = $this->client->unread(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookNotReadWhenUnreadCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->unread(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You haven't read this"], 15),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenUnreadCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setRead(true)));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setRead(false)))
            ->willReturn(false);

        // Act
        $result = $this->client->unread(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenUnreadCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setRead(true)));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setRead(false)))
            ->willReturn(true);

        // Act
        $result = $this->client->unread(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Unwish method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenUnwishCalledThenErrorMessageReturned()
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
        $result = $this->client->unwish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookNotOnWishlistWhenUnwishCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->unwish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You have not added this to your wishlist"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenUnwishCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(true))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(false)))
            ->willReturn(false);

        // Act
        $result = $this->client->unwish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenUnwishCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(true))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(false)))
            ->willReturn(true);

        // Act
        $result = $this->client->unwish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Wishlist method tests">

    /**
     * @test
     */
    public function givenBookDoesNotExistWhenWishlistCalledThenErrorMessageReturned()
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
        $result = $this->client->wish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookAlreadyOwnedWhenWishlistCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setOwned(true)));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->wish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You already own this"], 15),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenBookAlreadyOnWishlistWhenWishlistCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(
                (new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(true))
            );

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->wish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "You have already added this to your wishlist"], 15),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveFailsWhenWishlistCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook((new User())->setId(99999)))->setWishlist(true)))
            ->willReturn(false);

        // Act
        $result = $this->client->wish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Save failed"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSaveSucceedsWhenWishlistCalledThenOKStatusReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('getBookById')
            ->with(123)
            ->willReturn(new Book("test title"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Book::class)
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('save')
            ->with((new Book("test title"))->addUser((new UserBook($this->user))->setWishlist(true)))
            ->willReturn(true);

        // Act
        $result = $this->client->wish(new Request([], ['bookId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>
}
