from flask import Blueprint, jsonify, request
from . import adminService

admin_bp = Blueprint('admin', __name__)

@admin_bp.route('/', methods=['GET'])
def list_admins():
    admins = adminService.get_all_admins()
    return jsonify([{
        "AdminID": a.AdminID,
        "Username": a.Username,
        "Email": a.Email
    } for a in admins])

@admin_bp.route('/', methods=['POST'])
def add_admin():
    data = request.json
    admin, error = adminService.create_admin(data)
    if error:
        return jsonify({"error": error}), 400
    return jsonify({"message": "Admin created", "AdminID": admin.AdminID}), 201

@admin_bp.route('/<int:admin_id>', methods=['PUT'])
def edit_admin(admin_id):
    data = request.json
    admin = adminService.update_admin(admin_id, data)
    if not admin:
        return jsonify({"error": "Admin not found"}), 404
    return jsonify({"message": "Admin updated successfully"})

@admin_bp.route('/<int:admin_id>', methods=['DELETE'])
def remove_admin(admin_id):
    if adminService.delete_admin(admin_id):
        return jsonify({"message": "Admin deleted successfully"})
    return jsonify({"error": "Admin not found"}), 404
