from flask import Blueprint, jsonify

orders_bp = Blueprint('orders', __name__)

@orders_bp.route('/')
def get_orders():
    return jsonify({"message": "Orders module active"})
