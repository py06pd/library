<?php
/** src/AppBundle/Controller/SeriesController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\Series;
use AppBundle\Entity\User;
use AppBundle\Entity\UserSeries;
use AppBundle\Services\BookService;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class SeriesController
 * @package AppBundle\Controller
 */
class SeriesController extends AbstractController
{
    /**
     * BookService
     * @var BookService
     */
    private $bookService;

    /**
     * EntityManager
     * @var EntityManager
     */
    private $em;

    /**
     * Logger
     * @var LoggerInterface
     */
    private $logger;

    /**
     * User
     * @var User
     */
    private $user;

    /**
     * BookController constructor.
     * @param EntityManager $em
     * @param BookService $bookService
     * @param TokenStorage $tokenStorage
     * @param LoggerInterface $logger
     */
    public function __construct(
        EntityManager $em,
        BookService $bookService,
        TokenStorage $tokenStorage,
        LoggerInterface $logger
    ) {
        $this->bookService = $bookService;
        $this->em = $em;
        $this->logger = $logger;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Gets books for a series
     * @Route("/series/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getSeries(Request $request)
    {
        $authorId = $request->request->get('authorId', 0);
        $seriesId = $request->request->get('seriesId', 0);
        
        if ($seriesId > 0) {
            $series = $this->em->getRepository(Series::class)->findOneBy(['seriesId' => $seriesId]);
            if (!$series) {
                return $this->formatError("Invalid request");
            }
        } elseif ($authorId > 0) {
            $series = (object)['seriesId' => 0, 'name' => "Standalone"];
        } else {
            return $this->formatError("Invalid request");
        }

        $filters = [];
        if ($authorId > 0) {
            $filters[] = (object)['field' => 'author', 'operator' => 'equals', 'value' => [$authorId]];
        }

        if ($seriesId > 0) {
            $filters[] = (object)['field' => 'series', 'operator' => 'equals', 'value' => [$seriesId]];
        }

        $books = $this->bookService->search($total, $filters, -1);
        
        $tracking = false;

        if ($seriesId > 0 &&
            $this->em->getRepository(UserSeries::class)->findOneBy([
                'seriesId' => $seriesId,
                'userId' => $this->user->getId()
            ])
        ) {
            $tracking = true;
        }
        
        $main = $other = [];
        foreach ($books as $book) {
            if ($seriesId > 0 && $book->getSeriesById($seriesId) && $book->getSeriesById($seriesId)->getNumber()) {
                $main[str_pad($book->getSeriesById($seriesId)->getNumber(), 6, '0') . $book->getId()] = $book;
            } else {
                $other[$book->getName()] = $book;
            }
        }
        
        ksort($main);
        ksort($other);
        
        return $this->json([
            'status' => "OK",
            'series' => $series,
            'main' => array_values($main),
            'other' => array_values($other),
            'tracking' => $tracking
        ]);
    }
    
    /**
     * Add series to user series list
     * @Route("/series/track")
     * @param Request $request
     * @return JsonResponse
     */
    public function track(Request $request)
    {
        $series = $this->em->getRepository(Series::class)->findOneBy([
            'seriesId' => $request->request->get('seriesId')
        ]);
        if (!$series) {
            return $this->formatError("Invalid request");
        }
        
        $item = $this->em->getRepository(UserSeries::class)->findOneBy([
            'seriesId' => $series->getId(),
            'userId' => $this->user->getId()
        ]);
        if ($item) {
            return $this->formatError("You already track this");
        }

        $item = new UserSeries($series->getId(), $this->user->getId());
        $this->em->persist($item);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e);
            return $this->formatError("Update failed");
        }
                
        return $this->json(['status' => "OK"]);
    }

    /**
     * Gets tracked series
     * @Route("/series/tracked")
     * @return JsonResponse
     */
    public function tracked()
    {
        /** @var UserSeries[] $series */
        $series = $this->em->getRepository(UserSeries::class)->findBy(['userId' => $this->user->getId()]);

        $seriesIds = [];
        foreach ($series as $us) {
            $seriesIds[] = $us->getSeriesId();
        }

        return $this->json(['status' => "OK", 'seriesIds' => $seriesIds]);
    }

    /**
     * Remove series from user series list
     * @Route("/series/untrack")
     * @param Request $request
     * @return JsonResponse
     */
    public function untrack(Request $request)
    {
        $series = $this->em->getRepository(Series::class)->findOneBy([
            'seriesId' => $request->request->get('seriesId')
        ]);
        if (!$series) {
            return $this->formatError("Invalid request");
        }

        $item = $this->em->getRepository(UserSeries::class)->findOneBy([
            'seriesId' => $series->getId(),
            'userId' => $this->user->getId()
        ]);
        if (!$item) {
            return $this->formatError("You are not tracking this");
        }

        $this->em->remove($item);

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e);
            return $this->formatError("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }
}
