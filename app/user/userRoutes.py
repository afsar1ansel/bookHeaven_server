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
        user = userService.get_user_by_id(request.user_payload['sub'])
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
        return jsonify({"error": f"Profile error: {str(e)}"}), 401
