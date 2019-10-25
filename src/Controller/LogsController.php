<?php
/** src/App/Controller/LogsController.php */
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LogsController
 * @package App\Controller
 */
class LogsController extends Controller
{
    /**
     * Logs directory
     * @var string
     */
    private $logs_dir;

    /**
     * LogsController constructor.
     * @param string $logs_dir
     */
    public function __construct(string $logs_dir)
    {
        $this->logs_dir = $logs_dir;
    }

    /**
     * Get log contents
     * @Route("/getLogFile")
     * @param Request $request
     * @return JsonResponse
     */
    public function getLogFileAction(Request $request)
    {
        $contents = file_get_contents($this->logs_dir . "/" . $request->request->get('file'));
        
        return $this->json(['status' => "OK", 'contents' => $contents]);
    }
    
    /**
     * Gets log files
     * @Route("/getLogFiles")
     * @return JsonResponse
     */
    public function getLogFilesAction()
    {
        $logfiles = glob($this->logs_dir . "/*");
        $files = [];
        foreach ($logfiles as $file) {
            $files[] = substr($file, strrpos($file, '/') + 1);
        }
        return $this->json(['status' => "OK", 'files' => $files]);
    }
    
    /**
     * Display logs page
     * @Route("/logs")
     * @return Response
     */
    public function logsAction()
    {
        return $this->render('main/logs.html.twig');
    }
}
