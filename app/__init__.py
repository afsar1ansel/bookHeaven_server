from flask import Flask, jsonify
from flask_cors import CORS
from flask_migrate import Migrate
from dotenv import load_dotenv
import os
from .database import db

def create_app():
    load_dotenv()
    
    app = Flask(__name__)
    
    # Configuration
    app.config['SQLALCHEMY_DATABASE_URI'] = os.getenv('DATABASE_URL')
    app.config['SQLALCHEMY_TRACK_MODIFICATIONS'] = False
    app.config['SECRET_KEY'] = os.getenv('SECRET_KEY', 'fallback_secret_key')
    
    # Initialize Extensions
    CORS(app)
    db.init_app(app)
    Migrate(app, db)
    
    # Register Blueprints
    from .user.userRoutes import user_bp
    from .books.bookRoutes import books_bp
    from .orders.orderRoutes import orders_bp
    from .admin.adminRoutes import admin_bp
    
    app.register_blueprint(user_bp, url_prefix='/api/user')
    app.register_blueprint(books_bp, url_prefix='/api/books')
    app.register_blueprint(orders_bp, url_prefix='/api/orders')
    app.register_blueprint(admin_bp, url_prefix='/api/admin')
    
    # Import models here to ensure they are registered with SQLAlchemy for Migrations
    from .user import models as user_models
    from .books import models as book_models
    from .orders import models as order_models
    from .admin import models as admin_models
    
    @app.route('/')
    def index():
        return jsonify({
            "message": "Welcome to BookHeaven Modular API",
            "version": "1.0.0"
        })
        
    @app.route('/health')
    def health():
        return jsonify({"status": "healthy"})
        
    return app
