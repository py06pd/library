<?php
/** tests/Security/UserAuthenticatorTest.php */
namespace App\Tests\Security;

use App\Security\CookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class CookieServiceTest
 * @package Tests\AppBundle\Security
 */
class CookieServiceTest extends TestCase
{
    /**
     * Instance of CookieService
     * @var CookieService
     */
    private $client;

    protected function setUp(): void
    {
        $this->client = new CookieService(['domain' => "test.com", 'secure' => true]);
    }

    /**
     * @test
     */
    public function givenCookieParamsWhenCreateCalledThenCookieReturned()
    {
        // Act
        $result = $this->client->create('test', 123);

        // Assert
        $this->assertEquals(new Cookie('library', "test", 123, '/', "test.com", true), $result);
    }

    /**
     * @test
     */
    public function givenResponseWhenClearCalledThenCookieClearedInResponse()
    {
        // Arrange
        $response = new RedirectResponse("/login");

        // Act
        $this->client->clear($response);

        // Assert
        $this->assertEquals('/login', $response->getTargetUrl());
        $this->assertEquals([new Cookie('library', null, 1, '/', 'test.com', true, true, false, null)], $response->headers->getCookies());
    }
}
