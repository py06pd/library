<?php
/** src/AppBundle/Services/BookService.php */
namespace AppBundle\Services;

use AppBundle\DateTimeFactory;
use AppBundle\Entity\Book;
use AppBundle\Entity\UserBook;
use AppBundle\Entity\User;
use AppBundle\Repositories\BookRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Class BookService
 * @package AppBundle\Services
 */
class BookService
{
    /**
     * @var Auditor
     */
    private $auditor;

    /**
     * DateTime factory
     * @var DateTimeFactory
     */
    private $dateTime;

    /**
     * @var EntityManager
     */
    private $em;
    
     /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * BookService constructor.
     * @param EntityManager $em
     * @param DateTimeFactory $dateTime
     * @param Auditor $auditor
     * @param LoggerInterface $logger
     */
    public function __construct(EntityManager $em, DateTimeFactory $dateTime, Auditor $auditor, LoggerInterface $logger)
    {
        $this->auditor = $auditor;
        $this->dateTime = $dateTime;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * Cancel book request from user
     * @param int $bookId
     * @param int $userId
     * @return bool
     */
    public function cancel(int $bookId, int $userId)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book || !$book->getUserById($userId)) {
            return false;
        }

        $book->getUserById($userId)->setRequestedFrom(null);

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' request from cancelled");

        return true;
    }

    /**
     * Delete books
     * @param array $bookIds
     * @return bool
     */
    public function delete(array $bookIds) : bool
    {
        /** @var Book[] $books */
        $books = $this->em->getRepository(Book::class)->findBy(['bookId' => $bookIds]);
        if (count($books) != count($bookIds)) {
            return false;
        }

        foreach ($books as $book) {
            $this->em->remove($book);
        }

        try {
            $this->em->flush();
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        foreach ($books as $book) {
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' deleted");
        }

        return true;
    }

    /**
     * Set requested book to borrowed
     * @param int $bookId
     * @param int $userId
     * @return bool
     */
    public function delivered(int $bookId, int $userId)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book || !$book->getUserById($userId)) {
            return false;
        }

        $userBook = $book->getUserById($userId);
        $userBook->setBorrowedFrom($userBook->getRequestedFrom());
        $userBook->setBorrowedTime($this->dateTime->getNow());

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' borrowed from '<log.user.name>'",
            [
                'user' => [
                    'userId' => $userBook->getBorrowedFrom()->getId(),
                    'name' => $userBook->getBorrowedFrom()->getName(),
                ]
            ]
        );

        return true;
    }

    /**
     * Gift book
     * @param int $bookId
     * @param int $userId
     * @param User $fromUser
     * @return bool|string
     */
    public function gift(int $bookId, int $userId, User $fromUser)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return "Invalid request";
        }

        $userbook = $book->getUserById($userId);
        if (!$userbook || !$userbook->onWishlist()) {
            return "This book is not on the wishlist";
        }

        if ($userbook->getGiftedFrom()) {
            return "This has already been gifted";
        }

        $userbook->setGiftedFrom($fromUser);

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return "Update failed";
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' gifted to '<log.user.name>'",
            [
                'user' => [
                    'userId' => $userbook->getUser()->getId(),
                    'name' => $userbook->getUser()->getName()
                ]
            ]
        );

        return true;
    }

    /**
     * Set wishlist note
     * @param int $bookId
     * @param int $userId
     * @param string $text
     * @return bool
     */
    public function note(int $bookId, int $userId, string $text)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book || !$book->getUserById($userId)) {
            return false;
        }

        $userbook = $book->getUserById($userId);
        $oldText = $userbook->getNotes();
        $userbook->setNotes($text);

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' updated",
            [
                'user' => [
                    'userId' => $userbook->getUser()->getId(),
                    'name' => $userbook->getUser()->getName(),
                    'changes' => ['notes' => [$oldText, $text]]
                ]
            ]
        );

        return true;
    }

    /**
     * Reject book request from user
     * @param int $bookId
     * @param int $userId
     * @return bool
     */
    public function reject(int $bookId, int $userId)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book || !$book->getUserById($userId)) {
            return false;
        }

        $book->getUserById($userId)->setRequestedFrom(null);

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' request from '<log.user.name>' rejected",
            [
                'user' => [
                    'userId' => $userId,
                    'name' => $book->getUserById($userId)->getUser()->getName(),
                ]
            ]
        );

        return true;
    }

    /**
     * Request book from user
     * @param int $bookId
     * @param User $user
     * @return bool|string
     */
    public function request(int $bookId, User $user)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book) {
            return "Invalid request";
        }

        $bookUser = $book->getUserById($user->getId());
        if ($bookUser) {
            if ($bookUser->isOwned()) {
                return "You own this";
            }

            if ($bookUser->getBorrowedFrom()) {
                return "You are already borrowing this";
            }

            if ($bookUser->getRequestedFrom()) {
                return "You have already requested this";
            }
        } else {
            $bookUser = new UserBook($user);
            $book->addUser($bookUser);
        }

        $total = [];
        $groupUsers = $user->getGroupUsers();
        foreach ($book->getUsers() as $record) {
            $userId = $record->getUser()->getId();
            if ($groupUsers->get($userId)) {
                if ($record->isOwned()) {
                    if (!isset($total[$userId])) {
                        $total[$userId] = $record->getStock();
                    } else {
                        $total[$userId] += $record->getStock();
                    }
                } elseif ($record->getBorrowedFrom()) {
                    if (!isset($total[$record->getBorrowedFrom()->getId()])) {
                        $total[$record->getBorrowedFrom()->getId()] = -1;
                    } else {
                        $total[$record->getBorrowedFrom()->getId()] -= 1;
                    }
                } elseif ($record->getRequestedFrom()) {
                    if (!isset($total[$record->getRequestedFrom()->getId()])) {
                        $total[$record->getRequestedFrom()->getId()] = -1;
                    } else {
                        $total[$record->getRequestedFrom()->getId()] -= 1;
                    }
                }
            }
        }

        if (array_sum($total) <= 0) {
            return "None available to borrow";
        }

        foreach ($total as $ownerId => $stock) {
            if ($stock > 0) {
                $bookUser->setRequestedFrom($groupUsers->get($ownerId))
                    ->setRequestedTime($this->dateTime->getNow());
                break;
            }
        }

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return "Update failed";
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' requested from '<log.user.name>'",
            [
                'user' => [
                    'userId' => $bookUser->getRequestedFrom()->getId(),
                    'name' => $bookUser->getRequestedFrom()->getName(),
                ]
            ]
        );

        return true;
    }

    /**
     * Return book borrowed by user
     * @param int $bookId
     * @param int $userId
     * @return bool
     */
    public function returned(int $bookId, int $userId)
    {
        /** @var BookRepository $bookRepo */
        $bookRepo = $this->em->getRepository(Book::class);
        $book = $bookRepo->getBookById($bookId);
        if (!$book || !$book->getUserById($userId)) {
            return false;
        }

        $book->getUserById($userId)->setBorrowedFrom(null);

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        $this->auditor->log(
            $book->getId(),
            $book->getName(),
            "book '<log.itemname>' borrowed by '<log.user.name>' returned",
            [
                'user' => [
                    'userId' => $userId,
                    'name' => $book->getUserById($userId)->getUser()->getName(),
                ]
            ]
        );

        return true;
    }

    /**
     * Save book
     * @param Book $newBook
     * @return bool
     */
    public function save(Book $newBook)
    {
        $bookId = $newBook->getId();
        if ($bookId) {
            /** @var BookRepository $bookRepo */
            $bookRepo = $this->em->getRepository(Book::class);

            $book = $bookRepo->getBookById($bookId);
            if (!$book) {
                return false;
            }

            $oldBook = $book->jsonSerialize();
            foreach ($book->getAuthors() as $author) {
                if (!$newBook->hasAuthor($author)) {
                    $book->removeAuthor($author);
                }
            }

            foreach ($book->getSeries() as $series) {
                if (!$newBook->inSeries($series->getSeries())) {
                    $book->removeSeries($series->getSeries());
                }
            }

            $book->setName($newBook->getName());
            $book->setType($newBook->getType());
            $book->setGenres($newBook->getGenres());
            foreach ($newBook->getAuthors() as $author) {
                if (!$book->hasAuthor($author)) {
                    $book->addAuthor($author);
                }
            }

            foreach ($newBook->getSeries() as $series) {
                if ($book->inSeries($series->getSeries())) {
                    $book->getSeriesById($series->getSeries()->getId())->setNumber($series->getNumber());
                } else {
                    $book->addSeries($series->getSeries(), $series->getNumber());
                }
            }

            foreach ($newBook->getUsers() as $user) {
                if ($book->getUserById($user->getUser()->getId())) {
                    $book->getUserById($user->getUser()->getId())
                        ->setOwned($user->isOwned())
                        ->setRead($user->isRead())
                        ->setWishlist($user->onWishlist());
                } else {
                    $book->addUser($user);
                }
            }
        } else {
            $book = $newBook;
            $this->em->persist($book);
        }

        try {
            $this->em->flush($book);
        } catch (Exception $e) {
            $this->logger->error($e);
            return false;
        }

        if ($bookId) {
            $bookArray = $book->jsonSerialize();

            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' updated", ['changes' => [
                'name' => [$oldBook['name'], $book->getName()],
                'type' => [$oldBook['type'], $book->getType()],
                'authors' => [$oldBook['authors'], $bookArray['authors']],
                'genres' => [$oldBook['genres'], $book->getGenres()],
                'series' => [$oldBook['series'], $bookArray['series']],
                'users' => [$oldBook['users'], $bookArray['users']]
            ]]);
        } else {
            $this->auditor->log($book->getId(), $book->getName(), "book '<log.itemname>' created");
        }

        return true;
    }

    /**
     * Search for books
     * @param int $total
     * @param array $filters
     * @param int $first
     * @return Book[]
     */
    public function search(int &$total = null, array $filters = [], int $first = 0)
    {
        $eq = $neq = $like = [];
        if (count($filters) > 0) {
            $eq = $this->parseFilters($filters, 'equals');
            $neq = $this->parseFilters($filters, 'does not equal');

            foreach ($filters as $filter) {
                if ($filter->operator == 'like') {
                    $like[] = $filter->value[0];
                }
            }
        }

        /** @var BookRepository $repo */
        $repo = $this->em->getRepository(Book::class);

        $books = [];
        $total = $repo->getSearchResultCount($eq, $neq, $like);
        if ($total > 0) {
            $allBookIds = $repo->getSearchResults($eq, $neq, $like);
            if ($first >= 0) {
                $bookIds = array_slice(array_unique($allBookIds), $first, 15);
            } else {
                $bookIds = $allBookIds;
            }

            $books = $repo->getBooksById($bookIds);
        }
        
        return $books;
    }
    
    public function borrow($id, $userId, $userIds)
    {
        if (!in_array($userId, $userIds)) {
            return "Invalid request";
        }
        
        $item = $this->em->getRepository(BookEntity::class)->findOneBy(array('id' => $id));
        if (!$item) {
            return "Invalid request";
        }
        
        $user = $this->em->getRepository(User::class)->findOneBy(array('id' => $userId));
        if (!$user) {
            return "Invalid request";
        }
        
        $userbook = $this->em->getRepository(UserBook::class)->findOneBy(array('id' => $id, 'userid' => $userId));
        if ($userbook) {
            if ($userbook->owned) {
                return "You own this";
            }

            if ($userbook->borrowedfromid != 0) {
                return "You are already borrowing this";
            }
        } else {
            $userbook = new UserBook();
            $userbook->id = $id;
            $userbook->userid = $userId;
            $this->em->persist($userbook);
        }
        
        if ($userbook->requestedfromid != 0) {
            $userbook->borrowedfromid = $userbook->requestedfromid;
        } else {
            $history = $this->em->getRepository(UserBook::class)->findBy(array(
                'id' => $item->getId(),
                'userid' => $userIds
            ));

            $total = array();
            foreach ($history as $record) {
                if ($record->owned) {
                    if (!isset($total[$record->userid])) {
                        $total[$record->userid] = $record->stock;
                    } else {
                        $total[$record->userid] += $record->stock;
                    }
                } elseif ($record->borrowedfromid != 0) {
                    if (!isset($total[$record->borrowedfromid])) {
                        $total[$record->borrowedfromid] = -1;
                    } else {
                        $total[$record->borrowedfromid] -= 1;
                    }
                } elseif ($record->requestedfromid != 0) {
                    if (!isset($total[$record->requestedfromid])) {
                        $total[$record->requestedfromid] = -1;
                    } else {
                        $total[$record->requestedfromid] -= 1;
                    }
                }
            }

            if (array_sum($total) <= 0) {
                return "None available to borrow";
            }

            foreach ($total as $ownerId => $stock) {
                if ($stock > 0) {
                    $userbook->borrowedfromid = $ownerId;
                    break;
                }
            }
        }
        
        $oldid = $userbook->requestedfromid;
        $oldtime = $userbook->requestedtime;
        $userbook->requestedfromid = 0;
        $userbook->requestedtime = null;
        $userbook->borrowedtime = time();
        
        $this->em->flush();
        
        $this->auditor->userBookLog($item, $user, array(
            'requestedfromid' => array($oldid, 0),
            'requestedtime' => array($oldtime, null),
            'borrowedfromid' => array(0, $userbook->borrowedfromid),
            'borrowedtime' => array(null, $userbook->borrowedtime)
        ));
        
        return true;
    }
    
    private function parseFilters($filters, $op)
    {
        $map = [
            'author' => 'a{nonce}.authorId',
            'genre' => 'b{nonce}.genres',
            'owner' => 'bu{nonce}.owned',
            'read' => 'bu{nonce}.read',
            'series' => 's{nonce}.seriesId',
            'type' => 'b{nonce}.type',
            'wishlist' => 'bu{nonce}.wishlist',
        ];

        $query = [];
        foreach ($filters as $filter) {
            if ($filter->operator == $op) {
                $query[] = [$map[$filter->field], $filter->value];
            }
        }
        
        return $query;
    }


}
