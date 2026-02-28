from flask import Blueprint, jsonify, request
from . import userService
from ..auth import token_required
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
    token, user = userService.authenticate_user(data.get('Email'), data.get('Password'))
    if not token:
        return jsonify({"error": "Invalid credentials"}), 401
    return jsonify({
        "token": token,
        "Name": user.Name,
        "Email": user.Email,
        "role": "user"
    })

@user_bp.route('/profile', methods=['GET'])
@token_required
def profile():
    try:
        user_payload = request.user_payload
        role = user_payload.get('role', 'user')
        user_id = user_payload.get('sub')
        
        if role == 'admin':
            from ..admin import adminService
            user = adminService.get_admin_by_id(user_id)
        else:
            user = userService.get_user_by_id(user_id)
            
        if not user:
            return jsonify({"error": f"{role.capitalize()} not found"}), 404
            
        return jsonify({
            "UserID": getattr(user, 'UserID', getattr(user, 'AdminID', None)),
            "Email": user.Email,
            "Name": getattr(user, 'Name', getattr(user, 'Username', None)),
            "Address": user.Address,
            "Phone": user.Phone,
            "role": role
        })
    except Exception as e:
        return jsonify({"error": f"Profile error: {str(e)}"}), 401

@user_bp.route('/profile', methods=['PUT'])
@token_required
def update_profile():
    try:
        data = request.json
        user_payload = request.user_payload
        role = user_payload.get('role', 'user')
        user_id = user_payload.get('sub')
        
        if not user_id:
            return jsonify({"error": "Invalid token payload: missing sub"}), 400
            
        if role == 'admin':
            from ..admin import adminService
            user = adminService.update_admin(user_id, data)
        else:
            user = userService.update_user(user_id, data)
            
        if not user:
            return jsonify({"error": f"{role.capitalize()} not found"}), 404
            
        return jsonify({"message": "Profile updated successfully"})
    except Exception as e:
        return jsonify({"error": str(e)}), 400
