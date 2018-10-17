<?php
/** tests/AppBundle/Security/CookieAuthenticatorTest.php */
namespace Tests\AppBundle\Security;

use AppBundle\Entity\User;
use AppBundle\Repositories\UserRepository;
use AppBundle\Security\CookieAuthenticator;
use Doctrine\ORM\EntityManager;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CookieAuthenticatorTest extends TestCase
{
    /**
     * Instance of CookieAuthenticator
     * @var CookieAuthenticator
     */
    private $client;
    
    /**
     * @var EntityManager|MockObject
     */
    private $mockEm;
    
    protected function setUp()
    {
        $config = ['domain' => "test.com", 'secure' => true];
        $this->mockEm = $this->createMock(EntityManager::class);
        $this->client = new CookieAuthenticator($this->mockEm, $config);
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
        $user = new User();
        $user->setId(123);
        $user->setSessionId("124");
        
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
        $user = new User();
        $user->setId(123);
        $user->setSessionId("124");
        
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
    public function givenAuthenticatorWhenOnAuthenticationSuccessCalledThenNullReturned()
    {
        // Arrange
        $token = $this->createMock(TokenInterface::class);
        
        // Act
        $result = $this->client->onAuthenticationSuccess(new Request(), $token, "providerKey");
        
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
