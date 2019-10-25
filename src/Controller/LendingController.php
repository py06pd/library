<?php
/** src/App/Controller/LendingController.php */
namespace App\Controller;

use App\Entity\Book;
use App\Entity\User;
use App\Repositories\BookRepository;
use App\Services\BookService;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class LendingController
 * @package App\Controller
 */
class LendingController extends AbstractController
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
     * BookController constructor.
     * @param EntityManagerInterface $em
     * @param BookService $bookService
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        EntityManagerInterface $em,
        BookService $bookService,
        TokenStorageInterface $tokenStorage
    ) {
        $this->em = $em;
        $this->bookService = $bookService;
        $this->user = $tokenStorage->getToken()->getUser();
    }

    /**
     * Cancel request to borrow a book
     * @Route("/lending/cancel")
     * @param Request $request
     * @return JsonResponse
     */
    public function cancel(Request $request)
    {
        if (!$this->bookService->cancel($request->request->get('bookId'), $this->user->getId())) {
            return $this->error("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Confirm requested book has been delivered
     * @Route("/lending/delivered")
     * @param Request $request
     * @return JsonResponse
     */
    public function delivered(Request $request)
    {
        if (!$this->bookService->delivered($request->request->get('bookId'), $this->user->getId())) {
            return $this->error("Update failed");
        }
        
        return $this->json(['status' => "OK"]);
    }
    
    /**
     * Gets books requested or borrowed from or by user
     * @Route("/lending/get")
     * @return JsonResponse
     */
    public function getLending()
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $books = $bookRepo->getLending($this->user->getId());

        $requested = $requesting = $borrowed = $borrowing = [];
                
        foreach ($books as $book) {
            if ($book->getUserById($this->user->getId())) {
                if ($book->getUserById($this->user->getId())->getBorrowedFrom()) {
                    $borrowing[] = $book;
                } elseif ($book->getUserById($this->user->getId())->getRequestedFrom()) {
                    $requesting[] = $book;
                }
            }

            foreach ($book->getUsers() as $user) {
                if ($user->getBorrowedFrom() && $user->getBorrowedFrom()->getId() === $this->user->getId()) {
                    $borrowed[] = $book;
                } elseif ($user->getRequestedFrom() && $user->getRequestedFrom()->getId() === $this->user->getId()) {
                    $requested[] = $book;
                }
            }
        }
        
        return $this->json([
            'status' => "OK",
            'borrowed' => $borrowed,
            'borrowing' => $borrowing,
            'requested' => $requested,
            'requesting' => $requesting
        ]);
    }
    
    /**
     * Reject request to borrow a book
     * @Route("/lending/reject")
     * @param Request $request
     * @return JsonResponse
     */
    public function reject(Request $request)
    {
        if (!$this->bookService->reject($request->request->get('bookId'), $request->request->get('userId'))) {
            return $this->error("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Requests to borrow a book
     * @Route("/lending/request")
     * @param Request $request
     * @return JsonResponse
     */
    public function request(Request $request)
    {
        $bookId = $request->request->get('bookId');

        $result = $this->bookService->request($bookId, $this->user);
        if ($result !== true) {
            return $this->error($result);
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Confirm borrowed book has been returned
     * @Route("/lending/returned")
     * @param Request $request
     * @return JsonResponse
     */
    public function returned(Request $request)
    {
        if (!$this->bookService->returned($request->request->get('bookId'), $request->request->get('userId'))) {
            return $this->error("Update failed");
        }

        return $this->json(['status' => "OK"]);
    }
}
