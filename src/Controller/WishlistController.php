<?php
/** src/App/Controller/WishlistController.php */
namespace App\Controller;

use App\Entity\User;
use App\Services\BookService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class WishlistController
 * @package AppBundle\Controller
 */
class WishlistController extends AbstractController
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
     * User
     * @var User
     */
    private $user;

    /**
     * WishlistController constructor.
     * @param EntityManagerInterface $em
     * @param BookService $bookService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        BookService $bookService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->bookService = $bookService;
        $this->em = $em;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Get books on wishlist
     * @Route("/wishlist/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getBooks(Request $request)
    {
        $userId = $request->request->get('userId');

        if ($userId != $this->user->getId() && !$this->user->getGroupUser($userId)) {
            return $this->error("Invalid request");
        }

        $books = $this->bookService->search(
            $total,
            [(object)['field' => 'wishlist', 'operator' => 'equals', 'value' => [$userId]]],
            -1
        );

        if ($this->user->getId() == $userId) {
            foreach ($books as $book) {
                if ($book->getUserById($userId)->getGiftedFrom()) {
                    $book->getUserById($userId)->clearGiftedFrom();
                }
            }
        }
        
        return $this->json(['status' => "OK", 'books' => $books]);
    }
    
    /**
     * Gift book to user
     * @Route("/wishlist/gift")
     * @param Request $request
     * @return JsonResponse
     */
    public function gift(Request $request)
    {
        $bookId = $request->request->get('bookId');
        $userId = $request->request->get('userId');

        if (!$this->user->getGroupUser($userId)) {
            return $this->error("Invalid request");
        }

        $result = $this->bookService->gift($bookId, $userId, $this->user);
        if ($result !== true) {
            return $this->error($result);
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Save note
     * @Route("/notes/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function saveNote(Request $request)
    {
        $bookId = $request->request->get('bookId');
        $userId = $request->request->get('userId');
        $text = trim($request->request->get('text'));

        if ($userId != $this->user->getId() && !$this->user->getGroupUser($userId)) {
            return $this->error("Invalid request");
        }

        if (!$this->bookService->note($bookId, $userId, $text)) {
            return $this->error("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }
}
