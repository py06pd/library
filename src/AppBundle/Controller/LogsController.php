<?php
/** src/AppBundle/Controller/LogsController.php */
namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Series;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;
use AppBundle\Services\Group;

class LogsController extends Controller
{
    /**
     * @Route("/getLogFile")
     */
    public function getLogFileAction(Request $request)
    {
        $contents = file_get_contents(
            $this->getParameter('kernel.project_dir') .
            "/var/logs/" .
            $request->request->get('file')
        );
        
        return $this->json(array('status' => "OK", 'contents' => $contents));
    }
    
    /**
     * @Route("/getLogFiles")
     */
    public function getLogFilesAction()
    {
        $logfiles = glob($this->getParameter('kernel.project_dir') . "/var/logs/*");
        $files = array();
        foreach ($logfiles as $file) {
            $files[] = substr($file, strrpos($file, '/') + 1);
        }
        return $this->json(array('status' => "OK", 'files' => $files));
    }
    
    /**
     * @Route("/logs")
     */
    public function logsAction()
    {
        return $this->render('main/logs.html.twig');
    }
}
