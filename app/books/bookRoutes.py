from flask import Blueprint, jsonify, request
from . import bookService
from ..auth import token_required, admin_required

books_bp = Blueprint('books', __name__)

@books_bp.route('/', methods=['GET'])
@token_required
def list_books():
    books = bookService.get_all_books()
    return jsonify([{
        "BookID": b.BookID,
        "ISBN": b.ISBN,
        "Title": b.Title,
        "Description": b.Description,
        "Price": str(b.Price),
        "StockQuantity": b.StockQuantity,
        "Format": b.Format,
        "CoverImageURL": "https://picsum.photos/400/600",
        "Authors": [a.Name for a in b.authors]
    } for b in books])

@books_bp.route('/<int:book_id>', methods=['GET'])
@token_required
def get_book(book_id):
    book = bookService.get_book_by_id(book_id)
    if not book:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({
        "BookID": book.BookID,
        "ISBN": book.ISBN,
        "Title": book.Title,
        "Description": book.Description,
        "Price": str(book.Price),
        "StockQuantity": book.StockQuantity,
        "Format": book.Format,
        "PublicationDate": str(book.PublicationDate) if book.PublicationDate else None,
        "CoverImageURL": "https://picsum.photos/400/600",
        "Authors": [a.Name for a in book.authors]
    })

@books_bp.route('/', methods=['POST'])
@admin_required
def add_book():
    data = request.json
    try:
        book = bookService.create_book(data)
        return jsonify({"message": "Book created successfully", "BookID": book.BookID}), 201
    except Exception as e:
        return jsonify({"error": str(e)}), 400

@books_bp.route('/<int:book_id>', methods=['PUT'])
@admin_required
def edit_book(book_id):
    data = request.json
    book = bookService.update_book(book_id, data)
    if not book:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({"message": "Book updated successfully"})

@books_bp.route('/<int:book_id>', methods=['DELETE'])
@admin_required
def remove_book(book_id):
    success = bookService.delete_book(book_id)
    if not success:
        return jsonify({"error": "Book not found"}), 404
    return jsonify({"message": "Book deleted successfully"})
