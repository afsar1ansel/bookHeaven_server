<?php

namespace Books;

use Core\Database;
use Exception;
use PDO;

class BookService {
    /**
     * Get all books. Authors decoded from JSON column.
     */
    public function getAllBooks(): array {
        $db = Database::get();
        $stmt = $db->query("SELECT * FROM books ORDER BY id ASC");
        $books = $stmt->fetchAll();

        foreach ($books as &$book) {
            $book = $this->formatBook($book);
        }

        return $books;
    }

    public function getBookById(int $bookId): ?array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();

        if (!$book) return null;

        return $this->formatBook($book);
    }

    /**
     * Maps new snake_case DB columns to the PascalCase field names
     * the frontend and API docs expect. Decodes the JSON authors column.
     */
    private function formatBook(array $book): array {
        $book['BookID']          = $book['id'];
        $book['ISBN']            = $book['isbn'];
        $book['Title']           = $book['title'];
        $book['Description']     = $book['description'];
        $book['Price']           = (string) $book['price'];
        $book['StockQuantity']   = (int) $book['stock_quantity'];
        $book['Format']          = $book['format'];
        $book['PublicationDate'] = $book['publication_date'];
        $book['Rating']          = $book['rating'];
        $book['Category']        = $book['category'];
        $book['CoverImageURL']   = $book['cover_image_url'] ?? 'https://picsum.photos/400/600';
        $book['Authors']         = json_decode($book['authors'] ?? '[]', true) ?? [];
        return $book;
    }

    public function createBook(array $data): int {
        $db = Database::get();

        $authors = (isset($data['Authors']) && is_array($data['Authors']))
            ? json_encode($data['Authors'])
            : '[]';

        $stmt = $db->prepare("
            INSERT INTO books (isbn, title, description, price, stock_quantity, format, publication_date, rating, category, cover_image_url, authors)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['ISBN']            ?? null,
            $data['Title'],
            $data['Description']     ?? null,
            $data['Price'],
            $data['StockQuantity']   ?? 0,
            $data['Format']          ?? null,
            $data['PublicationDate'] ?? null,
            $data['Rating']          ?? 0,
            $data['Category']        ?? null,
            $data['CoverImageURL']   ?? null,
            $authors
        ]);

        return (int) $db->lastInsertId();
    }

    public function updateBook(int $bookId, array $data): bool {
        $db = Database::get();
        $fields = [];
        $params = [];

        // Map from API field names → DB column names
        $fieldMap = [
            'ISBN'            => 'isbn',
            'Title'           => 'title',
            'Description'     => 'description',
            'Price'           => 'price',
            'StockQuantity'   => 'stock_quantity',
            'Format'          => 'format',
            'PublicationDate' => 'publication_date',
            'Rating'          => 'rating',
            'Category'        => 'category',
            'CoverImageURL'   => 'cover_image_url',
        ];

        foreach ($fieldMap as $apiKey => $dbCol) {
            if (isset($data[$apiKey])) {
                $fields[] = "{$dbCol} = ?";
                $params[] = $data[$apiKey];
            }
        }

        if (isset($data['Authors']) && is_array($data['Authors'])) {
            $fields[] = "authors = ?";
            $params[] = json_encode($data['Authors']);
        }

        if (empty($fields)) return true;

        $params[] = $bookId;
        $sql = "UPDATE books SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteBook(int $bookId): bool {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
        return $stmt->execute([$bookId]);
    }

    /**
     * Home page data: featured, new arrivals, and distinct categories.
     * Categories are now pulled from the `books.category` column.
     */
    public function getHomeData(): array {
        $db = Database::get();

        // Featured (top rated)
        $stmt = $db->query("SELECT * FROM books ORDER BY rating DESC LIMIT 5");
        $featured = $stmt->fetchAll();
        foreach ($featured as &$b) {
            $b = $this->formatBook($b);
        }

        // New Arrivals
        $stmt = $db->query("SELECT * FROM books ORDER BY id DESC LIMIT 10");
        $newArrivals = $stmt->fetchAll();
        foreach ($newArrivals as &$b) {
            $b = $this->formatBook($b);
        }

        // Distinct categories from books table
        $stmt = $db->query(
            "SELECT DISTINCT category AS name FROM books
             WHERE category IS NOT NULL AND category != ''
             ORDER BY category ASC
             LIMIT 6"
        );
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            "featured"     => $featured,
            "new_arrivals" => $newArrivals,
            "categories"   => $categories
        ];
    }
}
