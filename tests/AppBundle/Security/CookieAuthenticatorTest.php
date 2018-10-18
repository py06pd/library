<?php
/** tests/AppBundle/Security/CookieAuthenticatorTest.php */
namespace Tests\AppBundle\Security;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSession;
use AppBundle\Repositories\UserRepository;
use AppBundle\Security\CookieAuthenticator;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class CookieAuthenticatorTest
 * @package Tests\AppBundle\Security
 */
class CookieAuthenticatorTest extends TestCase
{
    /**
     * Instance of CookieAuthenticator
     * @var CookieAuthenticator
     */
    private $client;

    /**
     * @var DateTimeFactory|MockObject
     */
    private $mockDateTime;

    /**
     * @var EntityManager|MockObject
     */
    private $mockEm;
    
    protected function setUp()
    {
        $config = ['domain' => "test.com", 'secure' => true];
        $this->mockEm = $this->createMock(EntityManager::class);
        $this->mockDateTime = $this->createMock(DateTimeFactory::class);
        $this->client = new CookieAuthenticator($this->mockEm, $config, $this->mockDateTime, new NullLogger());
    }
    
    /**
     * @test
     */
    public function givenLibraryCookieInRequestWhenGetCredentialsCalledThenCredentialsArrayReturned()
    {
        $request = new Request();
        $request->cookies->set("library", "a|b|c");
        
        $result = $this->client->getCredentials($request);
        $this->assertEquals(['id' => "a", 'datetime' => "b", 'code' => "c"], $result);
    }
    
    /**
     * @test
     */
    public function givenAuthenticatorWhenGetUserCalledThenUserReturned()
    {
        // Arrange
        $user = new User();
        $mockRepository = $this->createMock(UserRepository::class);
        $mockRepository->expects($this->once())
            ->method('getUserById')
            ->with(123)
            ->willReturn($user);
        
        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepository);

        /** @var UserProviderInterface|MockObject $provider */
        $provider = $this->createMock(UserProviderInterface::class);
        
        // Act
        $result = $this->client->getUser(['id' => 123], $provider);
        
        // Assert
        $this->assertEquals($user, $result);
    }
    
    /**
     * @test
     */
    public function givenInvalidCodeWhenCheckCredentialsCalledThenNullReturned()
    {
        // Arrange
        $user = (new User())->setId(123);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123, 'created' => new DateTime("@125")])
            ->willReturn(new UserSession(123, new DateTime(), "124", "d1"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->checkCredentials(['code' => "test", 'datetime' => 125], $user);
        
        // Assert
        $this->assertNull($result);
    }
    
    /**
     * @test
     */
    public function givenValidCodeWhenCheckCredentialsCalledThenTrueReturned()
    {
        // Arrange
        $user = (new User())->setId(123);

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123, 'created' => new DateTime("@125")])
            ->willReturn(new UserSession(123, new DateTime(), "124", "d1"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($mockRepo);

        // Act
        $result = $this->client->checkCredentials(
            ['code' => "7ddfec966b48c6699a0bfb45e6090107a716b3ca02c702b7d8ac11529d0c1852", 'datetime' => 125],
            $user
        );
        
        // Assert
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function givenSessionUpdateFailsWhenOnAuthenticationSuccessCalledThenFalseReturned()
    {
        // Arrange
        $token = new AnonymousToken("secret", (new User())->setId(123));

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123, 'created' => new DateTime("@125")])
            ->willReturn(new UserSession(123, new DateTime('2018-10-18'), "124", "d1"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new UserSession(123, new DateTime('2018-10-18'), "124", "d1"))
                ->setLastAccessed(new DateTime('2018-10-19')))
            ->willThrowException(new Exception("test exception"));

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn(new DateTime('2018-10-19'));

        $request = new Request();
        $request->cookies->set("library", "123|125|c");

        // Act
        $result = $this->client->onAuthenticationSuccess($request, $token, "providerKey");

        // Assert
        $this->assertFalse($result);
    }

    /**
     * @test
     */
    public function givenSessionUpdateSucceedsWhenOnAuthenticationSuccessCalledThenNullReturned()
    {
        // Arrange
        $token = new AnonymousToken("secret", (new User())->setId(123));

        $mockRepo = $this->createMock(EntityRepository::class);
        $mockRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['userId' => 123, 'created' => new DateTime("@125")])
            ->willReturn(new UserSession(123, new DateTime('2018-10-18'), "124", "d1"));

        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(UserSession::class)
            ->willReturn($mockRepo);
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with((new UserSession(123, new DateTime('2018-10-18'), "124", "d1"))
                ->setLastAccessed(new DateTime('2018-10-19')));

        $this->mockDateTime->expects($this->once())
            ->method('getNow')
            ->willReturn(new DateTime('2018-10-19'));

        $request = new Request();
        $request->cookies->set("library", "123|125|c");
        
        // Act
        $result = $this->client->onAuthenticationSuccess($request, $token, "providerKey");
        
        // Assert
        $this->assertNull($result);
    }
    
    /**
     * @test
     */
    public function givenAuthenticatorWhenOnAuthenticationFailureCalledThenCookieClearedAndRedirectResponseReturned()
    {
        // Arrange
        $expected = new RedirectResponse("/login");
        $expected->headers->clearCookie('library', '/', "test.com", true);
        
        // Act
        $result = $this->client->onAuthenticationFailure(new Request(), new AuthenticationException());
        
        // Assert
        $this->assertEquals($expected->getTargetUrl(), $result->getTargetUrl());
        $this->assertEquals($expected->headers->getCookies(), $result->headers->getCookies());
    }
    
    /**
     * @test
     */
    public function givenNoLibraryCookieWhenSupportsCalledThenFalseReturned()
    {
        // Act
        $result = $this->client->supports(new Request());
        
        // Assert
        $this->assertFalse($result);
    }
    
    /**
     * @test
     */
    public function givenLibraryCookieWhenSupportsCalledThenTrueReturned()
    {
        // Arrange
        $request = new Request();
        $request->cookies->set("library", "");
        
        // Act
        $result = $this->client->supports($request);
        
        // Assert
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function givenAuthenticatorWhenSupportsRememberMeCalledThenFalseReturned()
    {
        // Act
        $result = $this->client->supportsRememberMe();
        
        // Assert
        $this->assertFalse($result);
    }
}
