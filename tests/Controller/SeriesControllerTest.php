<?php
/** tests/Controller/SeriesControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\SeriesController;
use App\Entity\Book;
use App\Entity\Series;
use App\Entity\User;
use App\Entity\UserSeries;
use App\Repositories\BookRepository;
use App\Services\BookService;
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
 * Tests for SeriesController
 */
class SeriesControllerTest extends TestCase
{
    /**
     * Instance of SeriesController
     * @var SeriesController
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

        $this->client = new SeriesController($this->mockEm, $this->mockBookService, $tokenStorage, new NullLogger());
        $this->client->setContainer(new Container());
    }

    //<editor-fold desc="GetSeries method tests">

    /**
     * @test
     */
    public function givenNoAuthorAndNoSeriesWhenGetSeriesCalledThenErrorMessageReturned()
    {
        // Act
        $result = $this->client->getSeries(new Request());

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenNoAuthorAndSeriesDoesNotExistWhenGetSeriesCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['seriesId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Series::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->getSeries(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenNoAuthorAndSeriesExistsWhenGetSeriesCalledThenDataReturned()
    {
        // Arrange
        $series = (new Series("test series"))->setId(123);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls($series, new UserSeries(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [(object)['field' => 'series', 'operator' => 'equals', 'value' => [123]]])
            ->willReturn([
                (new Book("test5"))->setId(125)->addSeries($series, 2),
                (new Book("test4"))->setId(126)->addSeries($series, 2),
                (new Book("test3"))->setId(127)->addSeries($series, 1),
                (new Book("test2"))->setId(128)->addSeries($series),
                (new Book("test1"))->setId(129)->addSeries($series)
            ]);

        // Act
        $result = $this->client->getSeries(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'series' => $series,
                'main' => [
                    (new Book("test3"))->setId(127)->addSeries($series, 1),
                    (new Book("test5"))->setId(125)->addSeries($series, 2),
                    (new Book("test4"))->setId(126)->addSeries($series, 2)
                ],
                'other' => [
                    (new Book("test1"))->setId(129)->addSeries($series),
                    (new Book("test2"))->setId(128)->addSeries($series)
                ],
                'tracking' => true
            ]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenAuthorAndNoSeriesWhenGetSeriesCalledThenDataReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->never())
            ->method('findOneBy');

        $this->mockEm->expects($this->never())
            ->method('getRepository');

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [(object)['field' => 'author', 'operator' => 'equals', 'value' => [123]]])
            ->willReturn([new Book("test2"), new Book("test1")]);

        // Act
        $result = $this->client->getSeries(new Request([], ['authorId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'series' => (object)['seriesId' => 0, 'name' => "Standalone"],
                'main' => [],
                'other' => [new Book("test1"), new Book("test2")],
                'tracking' => false
            ]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenAuthorAndSeriesWhenGetSeriesCalledThenDataReturned()
    {
        // Arrange
        $series = (new Series("test series"))->setId(123);

        $mockRepo = $this->createMock(BookRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls($series, null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);

        $this->mockBookService->expects($this->once())
            ->method('search')
            ->with(null, [
                (object)['field' => 'author', 'operator' => 'equals', 'value' => [124]],
                (object)['field' => 'series', 'operator' => 'equals', 'value' => [123]]
            ])
            ->willReturn([
                (new Book("test5"))->setId(125)->addSeries($series, 2),
                (new Book("test4"))->setId(126)->addSeries($series, 2),
                (new Book("test3"))->setId(127)->addSeries($series, 1),
                (new Book("test2"))->setId(128)->addSeries($series),
                (new Book("test1"))->setId(129)->addSeries($series)
            ]);

        // Act
        $result = $this->client->getSeries(new Request([], ['authorId' => 124, 'seriesId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'series' => $series,
                'main' => [
                    (new Book("test3"))->setId(127)->addSeries($series, 1),
                    (new Book("test5"))->setId(125)->addSeries($series, 2),
                    (new Book("test4"))->setId(126)->addSeries($series, 2)
                ],
                'other' => [
                    (new Book("test1"))->setId(129)->addSeries($series),
                    (new Book("test2"))->setId(128)->addSeries($series)
                ],
                'tracking' => false
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Track method tests">

    /**
     * @test
     */
    public function givenSeriesDoesNotExistWhenTrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['seriesId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Series::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->track(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSeriesAlreadyTrackedWhenTrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), new UserSeries(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->track(new Request([], ['seriesId' => 123]));

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
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with(new UserSeries(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->track(new Request([], ['seriesId' => 123]));

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
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with(new UserSeries(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->track(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>

    //<editor-fold desc="Tracked method tests">

    /**
     * @test
     */
    public function givenControllerWhenTrackedCalledThenTrackedSeriesIdsReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findBy')
            ->with(['userId' => 99999])
            ->willReturn([
                new UserSeries(123, 99999),
                new UserSeries(124, 99999)
            ]);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSeries::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->tracked();

        // Assert
        $this->assertEquals(
            json_encode([
                'status' => "OK",
                'seriesIds' => [123, 124]
            ]),
            $result->getContent()
        );
    }

    //</editor-fold>

    //<editor-fold desc="Untrack method tests">

    /**
     * @test
     */
    public function givenSeriesDoesNotExistWhenUntrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['seriesId' => 123])
            ->willReturn(null);

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(Series::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->untrack(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(
            json_encode(['status' => "error", 'errorMessage' => "Invalid request"]),
            $result->getContent()
        );
    }

    /**
     * @test
     */
    public function givenSeriesNotTrackedWhenUntrackCalledThenErrorMessageReturned()
    {
        // Arrange
        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->exactly(2))
            ->method('findOneBy')
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), null);

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->untrack(new Request([], ['seriesId' => 123]));

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
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), new UserSeries(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('remove')
            ->with(new UserSeries(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception("test exception"));

        // Act
        $result = $this->client->untrack(new Request([], ['seriesId' => 123]));

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
            ->withConsecutive([['seriesId' => 123]], [['seriesId' => 123, 'userId' => 99999]])
            ->willReturnOnConsecutiveCalls((new Series("test"))->setId(123), new UserSeries(123, 99999));

        $this->mockEm->expects($this->exactly(2))
            ->method('getRepository')
            ->withConsecutive([Series::class], [UserSeries::class])
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('remove')
            ->with(new UserSeries(123, 99999));
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->untrack(new Request([], ['seriesId' => 123]));

        // Assert
        $this->assertEquals(json_encode(['status' => "OK"]), $result->getContent());
    }

    //</editor-fold>
}
