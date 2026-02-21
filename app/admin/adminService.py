from ..database import db
from .models import Admin
from passlib.hash import pbkdf2_sha256

def get_all_admins():
    return Admin.query.all()

def create_admin(data):
    if Admin.query.filter_by(Username=data.get('Username')).first():
        return None, "Username already exists"
    
    new_admin = Admin(
        Username=data.get('Username'),
        Email=data.get('Email'),
        Password=pbkdf2_sha256.hash(data.get('Password'))
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
