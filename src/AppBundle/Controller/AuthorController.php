<?php
/** src/AppBundle/Controller/AuthorController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\User;
use AppBundle\Entity\UserAuthor;
use AppBundle\Services\BookService;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class AuthorController
 * @package AppBundle\Controller
 */
class AuthorController extends AbstractController
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
     * Gets author books
     * @Route("/author/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getAuthor(Request $request)
    {
        $authorId = $request->request->get('authorId');

        $author = $this->em->getRepository(Author::class)->findOneBy(['authorId' => $authorId]);
        if (!$author) {
            return $this->formatError("Invalid request");
        }

        $filters = [(object)['field' => 'author', 'operator' => 'equals', 'value' => [$authorId]]];
        $books = $this->bookService->search($total, $filters, -1);

        $tracking = false;
        if ($this->em->getRepository(UserAuthor::class)->findOneBy([
            'authorId' => $authorId,
            'userId' => $this->user->getId()])
        ) {
            $tracking = true;
        }

        return $this->json([
            'status' => "OK",
            'author' => $author,
            'books' => $books,
            'tracking' => $tracking
        ]);
    }

    /**
     * Add author to user author list
     * @Route("/author/track")
     * @param Request $request
     * @return JsonResponse
     */
    public function track(Request $request)
    {
        $author = $this->em->getRepository(Author::class)->findOneBy([
            'authorId' => $request->request->get('authorId')
        ]);
        if (!$author) {
            return $this->formatError("Invalid request");
        }

        $item = $this->em->getRepository(UserAuthor::class)->findOneBy([
            'authorId' => $author->getId(),
            'userId' => $this->user->getId()
        ]);
        if ($item) {
            return $this->formatError("You already track this");
        }

        $item = new UserAuthor($author->getId(), $this->user->getId());
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
     * Gets tracked authors
     * @Route("/authors/tracked")
     * @return JsonResponse
     */
    public function tracked()
    {
        /** @var UserAuthor[] $authors */
        $authors = $this->em->getRepository(UserAuthor::class)->findBy(['userId' => $this->user->getId()]);
        
        $authorIds = [];
        foreach ($authors as $i) {
            $authorIds[] = $i->getAuthorId();
        }
        
        return $this->json(['status' => "OK", 'authorIds' => $authorIds]);
    }
    
    /**
     * Remove author from user author list
     * @Route("/author/untrack")
     * @param Request $request
     * @return JsonResponse
     */
    public function untrack(Request $request)
    {
        $author = $this->em->getRepository(Author::class)->findOneBy([
            'authorId' => $request->request->get('authorId')
        ]);
        if (!$author) {
            return $this->formatError("Invalid request");
        }

        $item = $this->em->getRepository(UserAuthor::class)->findOneBy([
            'authorId' => $author->getId(),
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
