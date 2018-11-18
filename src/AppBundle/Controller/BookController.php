<?php
/** src/AppBundle/Controller/BookController.php */
namespace AppBundle\Controller;

use AppBundle\Entity\Author;
use AppBundle\Entity\Book;
use AppBundle\Entity\Genre;
use AppBundle\Entity\Series;
use AppBundle\Entity\Type;
use AppBundle\Entity\User;
use AppBundle\Entity\UserBook;
use AppBundle\Repositories\BookRepository;
use AppBundle\Services\BookService;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;

/**
 * Class BookController
 * @package AppBundle\Controller
 */
class BookController extends AbstractController
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
     * Delete books
     * @Route("/books/delete")
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request)
    {
        if (!$this->user->hasRole("ROLE_LIBRARIAN")) {
            return $this->formatError("Insufficient user rights");
        }

        $bookIds = $request->request->get('bookIds');

        /** @var Book[] $books */
        $books = $this->em->getRepository(Book::class)->findBy(['bookId' => $bookIds]);
        if (count($books) != count($bookIds)) {
            return $this->formatError("Invalid form data");
        }

        foreach ($books as $book) {
            if (!$this->user->hasRole("ROLE_ADMIN") && !$book->isOnlyUser($this->user)) {
                return $this->formatError("Insufficient user rights");
            }
        }

        $result = $this->bookService->delete($books);
        if ($result === false) {
            return $this->formatError("Delete failed");
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Sets owned flag for book for user
     * @Route("/book/disown")
     * @param Request $request
     * @return JsonResponse
     */
    public function disown(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->isOwned()) {
            $userBook->setOwned(false);
        } else {
            return $this->formatError("You don't own this");
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Display homepage
     * @Route("/", name="homepage")
     * @param Request $request
     * @return Response
     */
    public function homepage(Request $request)
    {
        return $this->render('main/index.html.twig', [
            'query' => $request->query->all(),
            'user' => $this->user->jsonSerialize()
        ]);
    }
    
    /**
     * Get search filters
     * @Route("/books/filters")
     * @param Request $request
     * @return JsonResponse
     */
    public function getBookFilters(Request $request)
    {
        $field = $request->request->get('field');
        $values = [];
        switch ($field) {
            case 'author':
                $values = $this->em->getRepository(Author::class)->findBy([], ['name' => 'ASC', 'forename' => 'ASC']);
                break;
            case 'genre':
                $values = $this->em->getRepository(Genre::class)->findBy([], ['name' => 'ASC']);
                break;
            case 'series':
                $values = $this->em->getRepository(Series::class)->findBy([], ['name' => 'ASC']);
                break;
            case 'type':
                $values = $this->em->getRepository(Type::class)->findBy([], ['name' => 'ASC']);
                break;
        }
        
        return $this->json(['status' => "OK", 'data' => $values]);
    }
    
    /**
     * Get books
     * @Route("/books/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getBooks(Request $request)
    {
        $start = $request->request->get('start', 0);
        
        $filters = json_decode($request->request->get('filters', json_encode([])));
        $data = $this->bookService->search($total, $filters, $start);
        
        return $this->json(['status' => "OK", 'data' => $data, 'total' => $total]);
    }
    
    /**
     * Get book details
     * @Route("/book/get")
     * @param Request $request
     * @return JsonResponse
     */
    public function getBook(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = new Book("");
        if ($bookId > 0) {
            $book = $bookRepo->getBookById($bookId);
            if (!$book) {
                return $this->formatError("Invalid request");
            }
        }
        
        $authors = $this->em->getRepository(Author::class)->findBy([], ['name' => "ASC", 'forename' => "ASC"]);
        $genres = $this->em->getRepository(Genre::class)->findBy([], ['name' => "ASC"]);
        $series = $this->em->getRepository(Series::class)->findBy([], ['name' => "ASC"]);
        $types = $this->em->getRepository(Type::class)->findBy([], ['name' => "ASC"]);
        
        return $this->json([
            'status' => "OK",
            'data' => $book,
            'authors' => $authors,
            'genres' => $genres,
            'types' => $types,
            'series' => $series
        ]);
    }
    
    /**
     * Sets owned flag for book for user
     * @Route("/book/own")
     * @param Request $request
     * @return JsonResponse
     */
    public function own(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->isOwned()) {
            return $this->formatError("You already own this");
        } elseif ($userBook) {
            $userBook->setOwned(true);
        } else {
            $book->addUser((new UserBook($this->user))->setOwned(true));
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }
        
        return $this->json(['status' => "OK"]);
    }
    
    /**
     * Sets read flag for book for user
     * @Route("/book/read")
     * @param Request $request
     * @return JsonResponse
     */
    public function read(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->isRead()) {
            return $this->formatError("You've already read this");
        } elseif ($userBook) {
            $userBook->setRead(true);
        } else {
            $book->addUser((new UserBook($this->user))->setRead(true));
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }

        return $this->json(['status' => "OK"]);
    }
    
    /**
     * Save book details
     * @Route("/book/save")
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        if (!$this->user->hasRole("ROLE_LIBRARIAN")) {
            return $this->formatError("Insufficient user rights");
        }

        $data = json_decode($request->request->get('data'), true);

        if ($data['bookId']) {
            /** @var BookRepository $bookRepo */
            $bookRepo = $this->em->getRepository(Book::class);

            $book = $bookRepo->getBookById($data['bookId']);
            if (!$book) {
                return $this->formatError('Invalid form data');
            }

            if (!$this->user->hasRole("ROLE_ADMIN") && !$book->isOnlyUser($this->user)) {
                return $this->formatError("Insufficient user rights");
            }
        }

        $book = new Book($data['name']);
        $book->setId($data['bookId']);
        $book->setCreator($this->user);

        $newTypes = [];
        if (isset($data['type'])) {
            if (isset($data['type']['typeId'])) {
                /** @var Type $type */
                $type = $this->em->getRepository(Type::class)->findOneBy(['typeId' => $data['type']['typeId']]);
                if ($type) {
                    $book->setType($type);
                }
            } else {
                $type = new Type(trim($data['type']['name']));
                $book->setType($type);
                $newTypes[] = $type;
            }
        }

        $newGenres = [];
        foreach ($data['genres'] as $a) {
            if (isset($a['genreId'])) {
                /** @var Genre $genre */
                $genre = $this->em->getRepository(Genre::class)->findOneBy(['genreId' => $a['genreId']]);
                if ($genre) {
                    $book->addGenre($genre);
                }
            } else {
                $genre = new Genre(trim($a['name']));
                $book->addGenre($genre);
                $newGenres[] = $genre;
            }
        }

        $newAuthors = [];
        foreach ($data['authors'] as $a) {
            if (isset($a['authorId'])) {
                /** @var Author $author */
                $author = $this->em->getRepository(Author::class)->findOneBy(['authorId' => $a['authorId']]);
                if ($author) {
                    $book->addAuthor($author);
                }
            } else {
                $author = new Author(trim($a['forename']));
                $book->addAuthor($author);
                $newAuthors[] = $author;
            }
        }
        
        $newSeries = [];
        foreach ($data['series'] as $s) {
            $number = (int)$s['number'] === 0 ? null : $s['number'];
            if (isset($s['seriesId'])) {
                /** @var Series $series */
                $series = $this->em->getRepository(Series::class)->findOneBy(['seriesId' => $s['seriesId']]);
                if ($series) {
                    $book->addSeries($series, $number);
                }
            } else {
                $series = new Series($s['name'], "sequence");
                $book->addSeries($series, $number);
                $newSeries[] = $series;
            }
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }
        
        return $this->json([
            'status' => "OK",
            'newAuthors' => $newAuthors,
            'newGenres' => $newGenres,
            'newSeries' => $newSeries,
            'newTypes' => $newTypes
        ]);
    }
    

    
    /**
     * Sets read flag for book for user
     * @Route("/book/unread")
     * @param Request $request
     * @return JsonResponse
     */
    public function unread(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->isRead()) {
            $userBook->setRead(false);
        } else {
            return $this->formatError("You haven't read this");
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Sets wishlist flag for book for user
     * @Route("/book/unwish")
     * @param Request $request
     * @return JsonResponse
     */
    public function unwish(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->onWishlist()) {
            $userBook->setWishlist(false);
        } else {
            return $this->formatError("You have not added this to your wishlist");
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }

        return $this->json(['status' => "OK"]);
    }

    /**
     * Sets wishlist flag for book for user
     * @Route("/book/wish")
     * @param Request $request
     * @return JsonResponse
     */
    public function wish(Request $request)
    {
        $bookId = $request->request->get('bookId');

        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);

        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return $this->formatError("Invalid request");
        }

        $userBook = $book->getUserById($this->user->getId());
        if ($userBook && $userBook->isOwned()) {
            return $this->formatError("You already own this");
        } elseif ($userBook && $userBook->onWishlist()) {
            return $this->formatError("You have already added this to your wishlist");
        } elseif ($userBook) {
            $userBook->setWishlist(true);
        } else {
            $book->addUser((new UserBook($this->user))->setWishlist(true));
        }

        if (!$this->bookService->save($book)) {
            return $this->formatError('Save failed');
        }

        return $this->json(['status' => "OK"]);
    }
}
