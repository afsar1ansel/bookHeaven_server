from ..database import db

# Association tables
book_author = db.Table('bookauthor',
    db.Column('BookID', db.Integer, db.ForeignKey('book.BookID', ondelete='CASCADE'), primary_key=True),
    db.Column('AuthorID', db.Integer, db.ForeignKey('author.AuthorID', ondelete='CASCADE'), primary_key=True)
)

book_category = db.Table('bookcategory',
    db.Column('BookID', db.Integer, db.ForeignKey('book.BookID', ondelete='CASCADE'), primary_key=True),
    db.Column('CategoryID', db.Integer, db.ForeignKey('category.CategoryID', ondelete='CASCADE'), primary_key=True)
)

class Book(db.Model):
    __tablename__ = 'book'
    BookID = db.Column(db.Integer, primary_key=True)
    ISBN = db.Column(db.String(20), unique=True, nullable=False)
    Title = db.Column(db.String(255), nullable=False)
    Description = db.Column(db.Text)
    Price = db.Column(db.Numeric(10, 2), nullable=False)
    StockQuantity = db.Column(db.Integer, default=0)
    Format = db.Column(db.Enum('Physical', 'E-book', 'Audiobook'), nullable=False)
    PublicationDate = db.Column(db.Date)
    CoverImageURL = db.Column(db.String(255))
    Rating = db.Column(db.Integer, default=0)
    
    # Relationships
    authors = db.relationship('Author', secondary=book_author, backref='books')

class Author(db.Model):
    __tablename__ = 'author'
    AuthorID = db.Column(db.Integer, primary_key=True)
    Name = db.Column(db.String(100), nullable=False)
    Biography = db.Column(db.Text)

class Category(db.Model):
    __tablename__ = 'category'
    CategoryID = db.Column(db.Integer, primary_key=True)
    Name = db.Column(db.String(50), nullable=False)
    Description = db.Column(db.Text)
