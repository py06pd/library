<?php
/** tests/AppBundle/Services/BookServiceTest.php */
namespace Tests\AppBundle\Services;

use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Repositories\BookRepository;
use AppBundle\Services\Auditor;
use AppBundle\Services\BookService;
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
     * Mock instance of EntityManager
     * @var EntityManager|MockObject
     */
    private $mockEm;

    protected function setUp()
    {
        $this->mockAuditor = $this->createMock(Auditor::class);
        $this->mockEm = $this->createMock(EntityManager::class);

        $this->client = new BookService($this->mockEm, $this->mockAuditor, new NullLogger());
    }

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
                'users' => [[], []]
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
}
