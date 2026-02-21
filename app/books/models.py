from ..database import db

# Association tables
book_author = db.Table('BookAuthor',
    db.Column('ISBN', db.String(20), db.ForeignKey('Book.ISBN', ondelete='CASCADE'), primary_key=True),
    db.Column('AuthorID', db.Integer, db.ForeignKey('Author.AuthorID', ondelete='CASCADE'), primary_key=True)
)

book_category = db.Table('BookCategory',
    db.Column('ISBN', db.String(20), db.ForeignKey('Book.ISBN', ondelete='CASCADE'), primary_key=True),
    db.Column('CategoryID', db.Integer, db.ForeignKey('Category.CategoryID', ondelete='CASCADE'), primary_key=True)
)

class Book(db.Model):
    __tablename__ = 'Book'
    ISBN = db.Column(db.String(20), primary_key=True)
    Title = db.Column(db.String(255), nullable=False)
    Description = db.Column(db.Text)
    Price = db.Column(db.Numeric(10, 2), nullable=False)
    StockQuantity = db.Column(db.Integer, default=0)
    Format = db.Column(db.Enum('Physical', 'eBook'), nullable=False)
    PublicationDate = db.Column(db.Date)
    CoverImageURL = db.Column(db.String(255))

class Author(db.Model):
    __tablename__ = 'Author'
    AuthorID = db.Column(db.Integer, primary_key=True)
    Name = db.Column(db.String(100), nullable=False)
    Biography = db.Column(db.Text)

class Category(db.Model):
    __tablename__ = 'Category'
    CategoryID = db.Column(db.Integer, primary_key=True)
    Name = db.Column(db.String(50), nullable=False)
    Description = db.Column(db.Text)
