from ..database import db

class User(db.Model):
    __tablename__ = 'User'
    UserID = db.Column(db.Integer, primary_key=True)
    Email = db.Column(db.String(255), unique=True, nullable=False)
    Password = db.Column(db.String(255), nullable=False)
    Name = db.Column(db.String(100), nullable=False)
    Address = db.Column(db.Text)
    Phone = db.Column(db.String(20))
