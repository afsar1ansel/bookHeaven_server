<?php

namespace Books;

use Core\BaseController;
use Middleware\Auth;
use Exception;

class BookController extends BaseController {
    private BookService $bookService;

    public function __construct() {
        $this->bookService = new BookService();
    }

    public function list(): void {
        Auth::requireToken();
        $books = $this->bookService->getAllBooks();
        $this->json($books);
    }

    public function get(int $book_id): void {
        Auth::requireToken();
        $book = $this->bookService->getBookById($book_id);
        if (!$book) {
            $this->error("Book not found", 404);
        }
        $this->json($book);
    }

    public function add(): void {
        Auth::requireAdmin();
        $data = $this->getBodyData();
        try {
            $bookId = $this->bookService->createBook($data);
            $this->json(["message" => "Book created successfully", "BookID" => $bookId], 201);
        } catch (Exception $e) {
            $this->error($e->getMessage(), 400);
        }
    }

    public function update(int $book_id): void {
        Auth::requireAdmin();
        $data = $this->getBodyData();
        $success = $this->bookService->updateBook($book_id, $data);
        if (!$success) {
            $this->error("Book not found", 404);
        }
        $this->json(["message" => "Book updated successfully"]);
    }

    public function delete(int $book_id): void {
        Auth::requireAdmin();
        $success = $this->bookService->deleteBook($book_id);
        if (!$success) {
            $this->error("Book not found", 404);
        }
        $this->json(["message" => "Book deleted successfully"]);
    }

    public function home(): void {
        Auth::requireToken();
        try {
            $data = $this->bookService->getHomeData();
            $this->json($data);
        } catch (Exception $e) {
            $this->error($e->getMessage(), 400);
        }
    }
}
