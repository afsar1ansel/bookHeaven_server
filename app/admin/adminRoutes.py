from flask import Blueprint, jsonify, request, current_app
from . import adminService
from ..auth import admin_required
import jwt
import os

admin_bp = Blueprint('admin', __name__)

@admin_bp.route('/login', methods=['POST'])
def admin_login():
    data = request.json
    # We now verify Email and Password as requested
    token, admin = adminService.authenticate_admin(data.get('Email'), data.get('Password'))
    if not token:
        return jsonify({"error": "Invalid admin credentials"}), 401
    # We return the token, username, email and role
    return jsonify({
        "token": token,
        "Username": admin.Username,
        "Email": admin.Email,
        "role": "admin"
    })

@admin_bp.route('/', methods=['GET'])
@admin_required
def list_admins():
    admins = adminService.get_all_admins()
    return jsonify([{
        "AdminID": a.AdminID,
        "Username": a.Username,
        "Email": a.Email
    } for a in admins])

@admin_bp.route('/', methods=['POST'])
@admin_required
def add_admin():
    data = request.json
    admin, error = adminService.create_admin(data)
    if error:
        return jsonify({"error": error}), 400
    return jsonify({"message": "Admin created", "AdminID": admin.AdminID}), 201

@admin_bp.route('/<int:admin_id>', methods=['PUT'])
@admin_required
def edit_admin(admin_id):
    data = request.json
    admin = adminService.update_admin(admin_id, data)
    if not admin:
        return jsonify({"error": "Admin not found"}), 404
    return jsonify({"message": "Admin updated successfully"})

@admin_bp.route('/<int:admin_id>', methods=['DELETE'])
@admin_required
def remove_admin(admin_id):
    if adminService.delete_admin(admin_id):
        return jsonify({"message": "Admin deleted successfully"})
    return jsonify({"error": "Admin not found"}), 404
