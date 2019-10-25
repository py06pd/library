<?php
/** src/App/Security/CookieService.php */
namespace App\Security;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class CookieService
 * @package AppBundle\Security
 */
class CookieService
{
    /**
     * Cookie parameters
     * @var array
     */
    private $params;

    /**
     * CookieService constructor.
     * @param array $params
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * Clear cookie
     * @param Response $response
     */
    public function clear(Response $response)
    {
        $response->headers->clearCookie('library', '/', $this->params['domain'], $this->params['secure']);
    }

    /**
     * Creates cookie
     * @param string $content
     * @param int $expiry
     * @return Cookie
     */
    public function create(string $content, int $expiry)
    {
        return new Cookie('library', $content, $expiry, '/', $this->params['domain'], $this->params['secure']);
    }
}
