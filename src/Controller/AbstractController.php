<?php
/** src/App/Controller/AbstractController.php */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class AbstractController
 * @package App\Controller
 */
abstract class AbstractController extends Controller
{
    /**
     * Format alert response
     * @param string $message
     * @return JsonResponse
     */
    protected function alert($message)
    {
        return $this->json(['status' => "alert", 'errorMessage' => $message]);
    }

    /**
     * Format error response
     * @param string $message
     * @return JsonResponse
     */
    protected function error($message)
    {
        return $this->json(['status' => "error", 'errorMessage' => $message]);
    }
}
