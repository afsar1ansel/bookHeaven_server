from ..database import db
from .models import Admin
from passlib.hash import pbkdf2_sha256
import jwt
import datetime
import os

from flask import current_app

def generate_admin_token(admin_id):
    payload = {
        'exp': datetime.datetime.utcnow() + datetime.timedelta(days=1),
        'iat': datetime.datetime.utcnow(),
        'sub': str(admin_id),
        'role': 'admin'
    }
    return jwt.encode(payload, current_app.config['SECRET_KEY'], algorithm='HS256')

def authenticate_admin(email, password):
    admin = Admin.query.filter_by(Email=email).first()
    if admin and pbkdf2_sha256.verify(password, admin.Password):
        return generate_admin_token(admin.AdminID), admin
    return None, None

def get_admin_by_id(admin_id):
    return Admin.query.get(admin_id)

def get_all_admins():
    return Admin.query.all()

def create_admin(data):
    if Admin.query.filter_by(Username=data.get('Username')).first():
        return None, "Username already exists"
    
    new_admin = Admin(
        Username=data.get('Username'),
        Email=data.get('Email'),
        Password=pbkdf2_sha256.hash(data.get('Password')),
        Address=data.get('Address'),
        Phone=data.get('Phone')
    )
    db.session.add(new_admin)
    db.session.commit()
    return new_admin, None

def update_admin(admin_id, data):
    admin = Admin.query.get(admin_id)
    if not admin:
        return None
    
    admin.Username = data.get('Username', admin.Username)
    admin.Email = data.get('Email', admin.Email)
    admin.Address = data.get('Address', admin.Address)
    admin.Phone = data.get('Phone', admin.Phone)
    
    if data.get('Password'):
        admin.Password = pbkdf2_sha256.hash(data.get('Password'))
        
    db.session.commit()
    return admin

def delete_admin(admin_id):
    admin = Admin.query.get(admin_id)
    if not admin:
        return False
    db.session.delete(admin)
    db.session.commit()
    return True
