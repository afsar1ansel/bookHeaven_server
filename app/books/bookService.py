from .models import Book
from ..database import db
from datetime import datetime

def get_all_books():
    return Book.query.all()

def get_book_by_id(book_id):
    return Book.query.get(book_id)

def get_book_by_isbn(isbn):
    return Book.query.filter_by(ISBN=isbn).first()

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

def update_book(book_id, data):
    book = Book.query.get(book_id)
    if not book:
        return None
    
    book.ISBN = data.get('ISBN', book.ISBN)
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

def delete_book(book_id):
    book = Book.query.get(book_id)
    if not book:
        return False
    
    db.session.delete(book)
    db.session.commit()
    return True

def get_home_data():
    from .models import Category, Author, book_category
    from sqlalchemy import func, select
    
    # 1. Hero / Featured (Random 5)
    featured = Book.query.order_by(func.random()).limit(5).all()
    
    # 2. New Arrivals (Latest 10)
    new_arrivals = Book.query.order_by(Book.BookID.desc()).limit(10).all()
    
    # 3. Best Sellers (Random 8 for now)
    best_sellers = Book.query.order_by(func.random()).limit(8).all()
    
    # 4. Categories with Book Counts
    # categories = Category.query.all()
    # To get counts, we'll join with book_category
    category_data = []
    all_categories = Category.query.all()
    for cat in all_categories:
        count = db.session.query(func.count(book_category.c.BookID)).filter(book_category.c.CategoryID == cat.CategoryID).scalar()
        category_data.append({
            "name": cat.Name,
            "image": "https://picsum.photos/400/300", # Placeholder for category image
            "bookCount": count or 0
        })
        
    # 5. Stats
    total_books = Book.query.count()
    total_authors = Author.query.count()
    
    stats = {
        "TotalBooks": total_books,
        "HappyReaders": "10k+",
        "Authors": total_authors,
        "CountriesServed": 15
    }
    
    # 6. Book of the Week (Single random)
    botw = Book.query.order_by(func.random()).first()
    
    return {
        "hero": [{
            "id": b.BookID,
            "title": b.Title,
            "image": "https://picsum.photos/400/600",
            "tagline": b.Description[:100] + "..." if b.Description else "A great read.",
            "price": str(b.Price),
            "Rating": b.Rating
        } for b in featured],
        "sections": [
            {
                "title": "New Arrivals",
                "type": "slider",
                "books": [{
                    "BookID": b.BookID,
                    "Title": b.Title,
                    "CoverImageURL": "https://picsum.photos/400/600",
                    "Price": str(b.Price),
                    "Rating": b.Rating,
                    "Authors": [a.Name for a in b.authors]
                } for b in new_arrivals]
            },
            {
                "title": "Best Sellers",
                "type": "grid",
                "books": [{
                    "BookID": b.BookID,
                    "Title": b.Title,
                    "CoverImageURL": "https://picsum.photos/400/600",
                    "Price": str(b.Price),
                    "Rating": b.Rating,
                    "Authors": [a.Name for a in b.authors]
                } for b in best_sellers]
            }
        ],
        "categories": category_data,
        "stats": stats,
        "bookOfTheWeek": {
            "BookID": botw.BookID,
            "Title": botw.Title,
            "Description": botw.Description,
            "CoverImageURL": "https://picsum.photos/400/600",
            "Price": str(botw.Price),
            "Rating": botw.Rating,
            "Authors": [a.Name for a in botw.authors]
        } if botw else None
    }
