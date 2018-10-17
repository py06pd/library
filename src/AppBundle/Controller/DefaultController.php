<?php
/**
 * @todo fix adding second book after adding new author/series
 * @todo add series from series page
 * @todo add books to series in series page
 * @todo subseries in series page
 * @todo nicer book menu visuals
 * @todo mobile styling
 */
// src/AppBundle/Controller/DefaultController
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

class DefaultController extends Controller
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
    
    private function checkFilters($item, $owned, $read, $bookAuthors, $bookSeries, $eqFilters, $noFilters)
    {
        if (isset($eqFilters['genre']) && count(array_intersect($item->getGenres(), $eqFilters['genre'])) == 0) {
            return false;
        }

        if (isset($noFilters['genre']) && count(array_intersect($item->getGenres(), $noFilters['genre'])) > 0) {
            return false;
        }
        
        if (isset($eqFilters['owner'])) {
            foreach ($eqFilters['owner'] as $id) {
                if (!isset($owned[$item->getId()]) || !isset($owned[$item->getId()][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($noFilters['owner'])) {
            foreach ($noFilters['owner'] as $id) {
                if (isset($owned[$item->getId()]) && isset($owned[$item->getId()][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($eqFilters['read'])) {
            foreach ($eqFilters['read'] as $id) {
                if (!isset($read[$item->getId()]) || !isset($read[$item->getId()][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($noFilters['read'])) {
            foreach ($noFilters['read'] as $id) {
                if (isset($read[$item->getId()]) && isset($read[$item->getId()][$id])) {
                    return false;
                }
            }
        }
        
        if (isset($eqFilters['type']) && !in_array($item->getType(), $eqFilters['type'])) {
            return false;
        }
        
        if (isset($noFilters['type']) && in_array($item->getType(), $noFilters['type'])) {
            return false;
        }

        foreach (array('author' => $bookAuthors, 'series' => $bookSeries) as $filter => $values) {
            if (isset($eqFilters[$filter]) && count(array_intersect($values, $eqFilters[$filter])) == 0) {
                return false;
            }

            if (isset($noFilters[$filter]) && count(array_intersect($values, $noFilters[$filter])) > 0) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * @Route("/deleteItems")
     */
    public function deleteItemsAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $result = $this->get('app.book')->delete($request->request->get('ids'));
        if ($result === false) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid form data"));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/request")
     */
    public function requestAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $userIds = $this->get('app.group')->getLinkedUsers($user->id);
        
        $result = $this->get('app.book')->request(
            $request->request->get('id'),
            $user->id,
            $userIds
        );
        if ($result !== true) {
            return $this->json(array('status' => "error", 'errorMessage' => $result));
        }
        
        return $this->json(array('status' => "OK"));
    }
    
    /**
     * @Route("/notes/save")
     */
    public function notesSaveAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->json(array('status' => "error", 'errorMessage' => "You must be logged in to make request"));
        }
        
        $userIds = $this->get('app.group')->getLinkedUsers($user->id);
        
        if (!in_array($request->request->get('userid'), $userIds)) {
            return $this->formatError("Invalid request");
        }
        
        $em = $this->getDoctrine();
        
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $noteUser = $em->getRepository(User::class)->findOneBy(array('id' => $request->request->get('userid')));
        if (!$noteUser) {
            return $this->json(array('status' => "error", 'errorMessage' => "Invalid request"));
        }
        
        $data = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $noteUser->id]);
        $oldnotes = $data->notes;
        $text = trim($request->request->get('text'));
        $data->notes = $text == '' ? null : $text;
        
        $em->getManager()->flush();
        
        $this->get('auditor')->userBookLog($item, $noteUser, array('notes' => array($oldnotes, $text)));
        
        return $this->json(array('status' => "OK"));
    }
}
