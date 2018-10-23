<?php
/** tests/AppBundle/Controller/LoginControllerTest.php */
namespace Tests\AppBundle\Controller;

use AppBundle\Controller\LoginController;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\UserGroup;
use AppBundle\Repositories\BookRepository;
use AppBundle\Services\BookService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
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
     * Mock instance of EngineInterface
     * @var EngineInterface|MockObject
     */
    private $mockTemplating;

    protected function setUp()
    {
        $this->mockTemplating = $this->createMock(EngineInterface::class);
        $this->client = new LoginController();

        $container = new Container();
        $container->set('templating', $this->mockTemplating);
        $container->setParameter('cookieDomain', 'testDomain');
        $container->setParameter('cookieSecure', false);
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
            ->with('login.html.twig');

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
        $bag = new ResponseHeaderBag();
        $bag->clearCookie('library', '/', "testDomain", false);
        $expected = new RedirectResponse("/login", 302, $bag->all());

        // Act
        $result = $this->client->logout(new Request());

        // Assert
        $this->assertEquals("/login", $result->getTargetUrl());
        $this->assertEquals(302, $result->getStatusCode());
        $this->assertEquals($expected->headers->getCookies(), $result->headers->getCookies());
    }

    //</editor-fold>
}
