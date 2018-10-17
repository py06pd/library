<?php
/** tests/AppBundle/Security/UserAuthenticatorTest.php */
namespace Tests\AppBundle\Security;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\User;
use AppBundle\Security\UserAuthenticator;
use DateTime;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserAuthenticatorTest extends TestCase
{
    /**
     * Instance of UserAuthenticator
     * @var UserAuthenticator
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
        $this->mockDateTime = $this->createMock(DateTimeFactory::class);
        $this->mockEm = $this->createMock(EntityManager::class);
        $this->client = new UserAuthenticator($this->mockEm, "s3cr3t", $config, $this->mockDateTime, new NullLogger());
    }
    
    /**
     * @test
     */
    public function givenUsernameAndPasswordInRequestWhenGetCredentialsCalledThenCredentialsArrayReturned()
    {
        $result = $this->client->getCredentials(new Request([], ['username' => "testUser", 'password' => "P455w0rd"]));
        $this->assertEquals(['username' => "testUser", 'password' => "P455w0rd"], $result);
    }
    
    /**
     * @test
     */
    public function givenUsernameIsEmptyWhenGetUserCalledThenNullReturned()
    {
        // Arrange       
        $provider = $this->createMock(UserProviderInterface::class);
        
        // Act
        $result = $this->client->getUser(['username' => "", 'password' => "P455w0rd"], $provider);
        
        // Assert
        $this->assertNull($result);
    }
    
    /**
     * @test
     */
    public function givenUsernameIsNotEmptyWhenGetUserCalledThenUserReturned()
    {
        // Arrange
        $user = new User();
        $mockRepository = $this->createMock(EntityRepository::class);
        $mockRepository->expects($this->once())
            ->method('findOneBy')
            ->with(['username' => "testUser"])
            ->willReturn($user);
        
        $this->mockEm->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($mockRepository);
        
        $provider = $this->createMock(UserProviderInterface::class);
        
        // Act
        $result = $this->client->getUser(['username' => "testUser", 'password' => "P455w0rd"], $provider);
        
        // Assert
        $this->assertEquals($user, $result);
    }
    
    /**
     * @test
     */
    public function givenInvalidPasswordWhenCheckCredentialsCalledThenNullReturned()
    {
        // Arrange
        $user = new User();
        $user->setPassword("invalid");
        
        // Act
        $result = $this->client->checkCredentials(['username' => "testUser", 'password' => "P455w0rd"], $user);
        
        // Assert
        $this->assertNull($result);
    }
    
    /**
     * @test
     */
    public function givenValidPasswordWhenCheckCredentialsCalledThenTrueReturned()
    {
        // Arrange
        $user = new User();
        $user->setPassword("b7a56873cd771f2cda068af7c01d56888ae04696eb4d8cf8d921d8b1987e4415ac379527436dde0a");
        
        // Act
        $result = $this->client->checkCredentials(['username' => "testUser", 'password' => "P455w0rd"], $user);
        
        // Assert
        $this->assertTrue($result);
    }
    
    /**
     * @test
     */
    public function givenAuthenticatorWhenOnAuthenticationSuccessCalledThenRedirectResponseReturned()
    {
        // Arrange
        $user = (new User())->setId(123);
        $token = new AnonymousToken("s3cr3t", $user);
        
        $this->mockDateTime->expects($this->exactly(2))
            ->method('getNow')
            ->willReturn(new DateTime("2018-08-03 22:12:11"));
        $this->mockEm->expects($this->once())
            ->method('flush')
            ->with($user);
        
        $bag = new ResponseHeaderBag();
        $bag->setCookie(new Cookie(
            'library',
            "123|1533334331|4c86a021aba85f37de77273a456a7e1024d0ce1afb3a4264ea7606d8d07512e8",
            1564870331,
            '/',
            "test.com",
            true
        ));
        $expected = new RedirectResponse("/", 302, $bag->all());
        
        // Act
        $result = $this->client->onAuthenticationSuccess(new Request(), $token, "providerKey");
        
        // Assert
        $this->assertEquals($expected, $result);
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
        $this->assertEquals($expected, $result);
    }
    
    /**
     * @test
     */
    public function givenNoUsernameAndPasswordWhenSupportsCalledThenFalseReturned()
    {
        // Act
        $result = $this->client->supports(new Request());
        
        // Assert
        $this->assertFalse($result);
    }
    
    /**
     * @test
     */
    public function givenUsernameAndPasswordWhenSupportsCalledThenTrueReturned()
    {
        // Act
        $result = $this->client->supports(new Request([], ['username' => "test", 'password' => "t35t"]));
        
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
