from ..database import db

class Admin(db.Model):
    __tablename__ = 'admin'
    AdminID = db.Column(db.Integer, primary_key=True)
    Username = db.Column(db.String(50), unique=True, nullable=False)
    Email = db.Column(db.String(255), unique=True, nullable=False)
    Password = db.Column(db.String(255), nullable=False)
    Address = db.Column(db.Text)
    Phone = db.Column(db.String(20))
