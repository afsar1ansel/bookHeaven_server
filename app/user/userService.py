from .models import User
from ..database import db
import jwt
import datetime
import os
from passlib.hash import pbkdf2_sha256

def generate_token(user_id):
    payload = {
        'exp': datetime.datetime.utcnow() + datetime.timedelta(days=1),
        'iat': datetime.datetime.utcnow(),
        'sub': str(user_id)
    }
    return jwt.encode(payload, os.getenv('SECRET_KEY'), algorithm='HS256')

def register_user(data):
    if User.query.filter_by(Email=data.get('Email')).first():
        return None, "Email already exists"
    
    new_user = User(
        Email=data.get('Email'),
        Password=pbkdf2_sha256.hash(data.get('Password')),
        Name=data.get('Name'),
        Address=data.get('Address'),
        Phone=data.get('Phone')
    )
    db.session.add(new_user)
    db.session.commit()
    return new_user, None

def authenticate_user(email, password):
    user = User.query.filter_by(Email=email).first()
    if user and pbkdf2_sha256.verify(password, user.Password):
        return generate_token(user.UserID)
    return None

def get_user_by_id(user_id):
    return User.query.get(user_id)

# CRUD for Admin/General use
def get_all_users():
    return User.query.all()

def update_user(user_id, data):
    user = User.query.get(user_id)
    if not user:
        return None
    
    user.Name = data.get('Name', user.Name)
    user.Address = data.get('Address', user.Address)
    user.Phone = data.get('Phone', user.Phone)
    
    if data.get('Password'):
        user.Password = pbkdf2_sha256.hash(data.get('Password'))
        
    db.session.commit()
    return user

def delete_user(user_id):
    user = User.query.get(user_id)
    if not user:
        return False
    db.session.delete(user)
    db.session.commit()
    return True
