<?php

namespace Books;

use Core\Database;
use Exception;
use PDO;

class BookService {
    /**
     * Get all books with their authors.
     * Replaces Flask's get_all_books().
     */
    public function getAllBooks(): array {
        $db = Database::get();
        $stmt = $db->query("SELECT * FROM book");
        $books = $stmt->fetchAll();

        foreach ($books as &$book) {
            $book['Authors'] = $this->getBookAuthors($book['BookID']);
            $book['Price'] = (string) $book['Price'];
            $book['CoverImageURL'] = "https://picsum.photos/400/600"; // Matching Flask placeholder
        }

        return $books;
    }

    public function getBookById(int $bookId): ?array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM book WHERE BookID = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book) return null;

        $book['Authors'] = $this->getBookAuthors($bookId);
        $book['Price'] = (string) $book['Price'];
        $book['CoverImageURL'] = "https://picsum.photos/400/600";
        
        return $book;
    }

    private function getBookAuthors(int $bookId): array {
        $db = Database::get();
        $stmt = $db->prepare("
            SELECT a.Name 
            FROM author a 
            JOIN bookauthor ba ON a.AuthorID = ba.AuthorID 
            WHERE ba.BookID = ?
        ");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function createBook(array $data): int {
        $db = Database::get();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("
                INSERT INTO book (ISBN, Title, Description, Price, StockQuantity, Format, PublicationDate, Rating) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['ISBN'],
                $data['Title'],
                $data['Description'] ?? null,
                $data['Price'],
                $data['StockQuantity'] ?? 0,
                $data['Format'],
                $data['PublicationDate'] ?? null,
                $data['Rating'] ?? 0
            ]);
            $bookId = $db->lastInsertId();

            // Handle authors if provided
            if (isset($data['Authors']) && is_array($data['Authors'])) {
                foreach ($data['Authors'] as $authorName) {
                    // Find or create author
                    $stmt = $db->prepare("SELECT AuthorID FROM author WHERE Name = ?");
                    $stmt->execute([$authorName]);
                    $author = $stmt->fetch();
                    
                    if ($author) {
                        $authorId = $author['AuthorID'];
                    } else {
                        $stmt = $db->prepare("INSERT INTO author (Name) VALUES (?)");
                        $stmt->execute([$authorName]);
                        $authorId = $db->lastInsertId();
                    }

                    // Link to book
                    $stmt = $db->prepare("INSERT INTO bookauthor (BookID, AuthorID) VALUES (?, ?)");
                    $stmt->execute([$bookId, $authorId]);
                }
            }

            $db->commit();
            return (int) $bookId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function updateBook(int $bookId, array $data): bool {
        $db = Database::get();
        
        $fields = [];
        $params = [];
        
        $allowed = ['ISBN', 'Title', 'Description', 'Price', 'StockQuantity', 'Format', 'PublicationDate', 'Rating'];
        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $fields[] = "{$field} = ?";
                $params[] = $data[$field];
            }
        }

        if (empty($fields)) return true;

        $params[] = $bookId;
        $sql = "UPDATE book SET " . implode(', ', $fields) . " WHERE BookID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteBook(int $bookId): bool {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM book WHERE BookID = ?");
        return $stmt->execute([$bookId]);
    }

    /**
     * Replaces Flask's get_home_data()
     */
    public function getHomeData(): array {
        $db = Database::get();
        
        // Featured (random for now)
        $stmt = $db->query("SELECT * FROM book ORDER BY Rating DESC LIMIT 5");
        $featured = $stmt->fetchAll();
        foreach ($featured as &$b) {
            $b['Authors'] = $this->getBookAuthors($b['BookID']);
            $b['Price'] = (string) $b['Price'];
            $b['CoverImageURL'] = "https://picsum.photos/400/600";
        }

        // New Arrivals
        $stmt = $db->query("SELECT * FROM book ORDER BY BookID DESC LIMIT 10");
        $newArrivals = $stmt->fetchAll();
        foreach ($newArrivals as &$b) {
            $b['Authors'] = $this->getBookAuthors($b['BookID']);
            $b['Price'] = (string) $b['Price'];
            $b['CoverImageURL'] = "https://picsum.photos/400/600";
        }

        return [
            "featured" => $featured,
            "new_arrivals" => $newArrivals,
            "categories" => $db->query("SELECT * FROM category LIMIT 6")->fetchAll()
        ];
    }
}
