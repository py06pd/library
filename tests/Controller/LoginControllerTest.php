<?php
/** tests/Controller/LoginControllerTest.php */
namespace App\Tests\Controller;

use App\Controller\LoginController;
use App\Security\CookieService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

/**
 * Tests for LoginController
 */
class LoginControllerTest extends TestCase
{
    /**
     * Instance of LoginController
     * @var LoginController
     */
    private $client;

    /**
     * Mock instance of CookieService
     * @var CookieService|MockObject
     */
    private $mockCookieService;

    /**
     * Mock instance of EngineInterface
     * @var EngineInterface|MockObject
     */
    private $mockTemplating;

    protected function setUp(): void
    {
        $this->mockCookieService = $this->createMock(CookieService::class);
        $this->mockTemplating = $this->createMock(EngineInterface::class);
        $this->client = new LoginController($this->mockCookieService);

        $container = new Container();
        $container->set('twig', $this->mockTemplating);
        $this->client->setContainer($container);
    }

    //<editor-fold desc="Login method tests">

    /**
     * @test
     */
    public function givenControllerWhenLoginCalledThenLoginPageDisplayed()
    {
        // Arrange
        $this->mockTemplating->expects($this->once())
            ->method('render')
            ->with('login.html.twig')
            ->willReturn('output');;

        // Act
        $this->client->login();
    }

    //</editor-fold>

    //<editor-fold desc="Logout method tests">

    /**
     * @test
     */
    public function givenControllerWhenLogoutCalledThenRedirectResponseReturned()
    {
        // Arrange
        $this->mockCookieService->expects($this->once())
            ->method('clear')
            ->with(new RedirectResponse("/login"));

        // Act
        $result = $this->client->logout(new Request());

        // Assert
        $this->assertEquals('/login', $result->getTargetUrl());
    }

    //</editor-fold>
}
