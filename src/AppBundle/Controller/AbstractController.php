<?php
/** src/AppBundle/Controller/AbstractController.php */
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AbstractController
 * @package AppBundle\Controller
 */
abstract class AbstractController extends Controller
{
    /**
     * Format alert response
     * @param string $message
     * @return JsonResponse
     */
    protected function formatAlert($message)
    {
        return $this->json(['status' => "alert", 'errorMessage' => $message]);
    }

    /**
     * Format error response
     * @param string $message
     * @return JsonResponse
     */
    protected function formatError($message)
    {
        return $this->json(['status' => "error", 'errorMessage' => $message]);
    }
}
