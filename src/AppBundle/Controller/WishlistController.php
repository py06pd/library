<?php
/** src/AppBundle/Controller/WishlistController.php */
namespace AppBundle\Controller;

use AppBundle\Repositories\BookRepository;
use AppBundle\Services\BookService;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Book;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class WishlistController
 * @package AppBundle\Controller
 */
class WishlistController extends Controller
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
     * @param EntityManager $em
     * @param BookService $bookService
     * @param TokenStorage $tokenStorage
     */
    public function __construct(EntityManager $em, BookService $bookService, TokenStorage $tokenStorage)
    {
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
        $start = $request->request->get('start', 0);

        if ($userId != $this->user->getId() && !$this->user->getGroupUsers()->containsKey($userId)) {
            return $this->formatError("Invalid request");
        }

        $books = $this->bookService->search(
            $total,
            [(object)['field' => 'wishlist', 'operator' => 'equals', 'value' => $userId]],
            $start
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
     * @Route("/wishlist/gift")
     */
    public function giftAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->formatError("You must be logged in to make request");
        }
        
        $em = $this->getDoctrine()->getManager();
        $item = $em->getRepository(Book::class)->findOneBy(array('id' => $request->request->get('id')));
        if (!$item) {
            return $this->formatError("Invalid request");
        }
        
        $userIds = $this->get('app.group')->getLinkedUsers($user->id);
        
        if (!in_array($request->request->get('userid'), $userIds)) {
            return $this->formatError("Invalid request");
        }
        
        $bookuser = $em->getRepository(User::class)->findOneBy(array('id' => $request->request->get('userid')));
        if (!$bookuser) {
            return $this->formatError("Invalid request");
        }
               
        $userbook = $em->getRepository(UserBook::class)->findOneBy(['id' => $item->getId(), 'userid' => $bookuser->id]);
        if (!$userbook || !$userbook->wishlist) {
            return $this->formatError("This book is not on the wishlist");
        }
        
        if ($userbook->giftfromid != 0) {
            return $this->formatError("This has already been gifted");
        }

        $user = $this->getUser();
        $userbook->giftfromid = $user ? $user->id : -1;
        
        $em->flush();
        
        $this->get('auditor')->userBookLog($item, $bookuser, array('giftfromid' => array(0, $userbook->giftfromid)));
        
        return $this->json(array('status' => "OK"));
    }

    private function formatError($message)
    {
        return $this->json(array('status' => "error", 'errorMessage' => $message));
    }
}
