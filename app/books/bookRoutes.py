from flask import Blueprint, jsonify, request
from . import bookService

books_bp = Blueprint('books', __name__)

@books_bp.route('/', methods=['GET'])
def list_books():
    books = bookService.get_all_books()
    return jsonify([{
        "ISBN": b.ISBN,
        "Title": b.Title,
        "Price": str(b.Price),
        "StockQuantity": b.StockQuantity,
        "Format": b.Format,
        "CoverImageURL": "https://picsum.photos/400/600"
    } for b in books])

@books_bp.route('/<isbn>', methods=['GET'])
def get_book(isbn):
    book = bookService.get_book_by_isbn(isbn)
    if not book:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({
        "ISBN": book.ISBN,
        "Title": book.Title,
        "Description": book.Description,
        "Price": str(book.Price),
        "StockQuantity": book.StockQuantity,
        "Format": book.Format,
        "PublicationDate": str(book.PublicationDate) if book.PublicationDate else None,
        "CoverImageURL": "https://picsum.photos/400/600"
    })

@books_bp.route('/', methods=['POST'])
def add_book():
    data = request.json
    try:
        book = bookService.create_book(data)
        return jsonify({"message": "Book created successfully", "ISBN": book.ISBN}), 201
    except Exception as e:
        return jsonify({"error": str(e)}), 400

@books_bp.route('/<isbn>', methods=['PUT'])
def edit_book(isbn):
    data = request.json
    book = bookService.update_book(isbn, data)
    if not book:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({"message": "Book updated successfully"})

@books_bp.route('/<isbn>', methods=['DELETE'])
def remove_book(isbn):
    success = bookService.delete_book(isbn)
    if not success:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({"message": "Book deleted successfully"})
