from .models import Book
from ..database import db
from datetime import datetime

def get_all_books():
    return Book.query.all()

def get_book_by_isbn(isbn):
    return Book.query.get(isbn)

def create_book(data):
    # Basic validation could be added here
    new_book = Book(
        ISBN=data.get('ISBN'),
        Title=data.get('Title'),
        Description=data.get('Description'),
        Price=data.get('Price'),
        StockQuantity=data.get('StockQuantity', 0),
        Format=data.get('Format'),
        PublicationDate=datetime.strptime(data.get('PublicationDate'), '%Y-%m-%d').date() if data.get('PublicationDate') else None,
        CoverImageURL=data.get('CoverImageURL')
    )
    db.session.add(new_book)
    db.session.commit()
    return new_book

def update_book(isbn, data):
    book = Book.query.get(isbn)
    if not book:
        return None
    
    book.Title = data.get('Title', book.Title)
    book.Description = data.get('Description', book.Description)
    book.Price = data.get('Price', book.Price)
    book.StockQuantity = data.get('StockQuantity', book.StockQuantity)
    book.Format = data.get('Format', book.Format)
    if data.get('PublicationDate'):
        book.PublicationDate = datetime.strptime(data.get('PublicationDate'), '%Y-%m-%d').date()
    book.CoverImageURL = data.get('CoverImageURL', book.CoverImageURL)
    
    db.session.commit()
    return book

def delete_book(isbn):
    book = Book.query.get(isbn)
    if not book:
        return False
    
    db.session.delete(book)
    db.session.commit()
    return True
