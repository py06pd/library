<?php
/** tests/AppBundle/Services/AuditorTest.php */
namespace Tests\AppBundle\Services;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\Audit;
use AppBundle\Entity\User;
use AppBundle\Services\Auditor;
use DateTime;
use Doctrine\ORM\EntityManager;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Tests for Auditor
 */
class AuditorTest extends TestCase
{
    /**
     * Instance of Auditor
     * @var Auditor
     */
    private $client;

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
        $this->mockDateTime = $this->createMock(DateTimeFactory::class);
        $this->mockEm = $this->createMock(EntityManager::class);

        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new AnonymousToken("secret", (new User('test01'))->setId(123)));

        $this->client = new Auditor($this->mockEm, $this->mockDateTime, $tokenStorage);
    }

    /**
     * @test
     */
    public function givenExceptionThrownWhenLogCalledThenFalseReturned()
    {
        // Arrange
        $audit = new Audit(
            (new User('test01'))->setId(123),
            new DateTime('2018-11-18 22:09:11'),
            124,
            'testBook',
            'tested',
            ['changes' => ['field1' => ['value1', 'value2']]]
        );

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn(new DateTime('2018-11-18 22:09:11'));

        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with($audit);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->willThrowException(new Exception('test exception'));

        // Act
        $result = $this->client->log(124, 'testBook', 'tested', [
            'changes' => ['field1' => ['value1', 'value2']]
        ]);

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenNoExceptionThrownWhenLogCalledThenTrueReturned()
    {
        // Arrange
        $audit = new Audit(
            (new User('test01'))->setId(123),
            new DateTime('2018-11-18 22:09:11'),
            124,
            'testBook',
            'tested',
            ['changes' => ['field1' => ['value1', 'value2']]]
        );

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn(new DateTime('2018-11-18 22:09:11'));

        $this->mockEm->expects($this->once())
            ->method('persist')
            ->with($audit);
        $this->mockEm->expects($this->once())
            ->method('flush');

        // Act
        $result = $this->client->log(124, 'testBook', 'tested', [
            'changes' => ['field1' => ['value1', 'value2']]
        ]);

        // Assert
        $this->assertTrue($result);
    }
}
