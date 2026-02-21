from flask import Blueprint, jsonify, request
from . import userService
import jwt
import os

user_bp = Blueprint('user', __name__)

@user_bp.route('/register', methods=['POST'])
def register():
    data = request.json
    user, error = userService.register_user(data)
    if error:
        return jsonify({"error": error}), 400
    return jsonify({"message": "User registered successfully"}), 201

@user_bp.route('/login', methods=['POST'])
def login():
    data = request.json
    token = userService.authenticate_user(data.get('Email'), data.get('Password'))
    if not token:
        return jsonify({"error": "Invalid credentials"}), 401
    return jsonify({"token": token})

@user_bp.route('/profile', methods=['GET'])
def profile():
    auth_header = request.headers.get('Authorization')
    if not auth_header:
        return jsonify({"error": "Token missing"}), 401
    
    try:
        # Bearer <token>
        token = auth_header.split(" ")[1]
        payload = jwt.decode(token, os.getenv('SECRET_KEY'), algorithms=['HS256'])
        user = userService.get_user_by_id(payload['sub'])
        if not user:
            return jsonify({"error": "User not found"}), 404
        return jsonify({
            "UserID": user.UserID,
            "Email": user.Email,
            "Name": user.Name,
            "Address": user.Address,
            "Phone": user.Phone
        })
    except Exception as e:
        return jsonify({"error": "Invalid token"}), 401
