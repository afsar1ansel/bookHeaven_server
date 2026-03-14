from flask import Blueprint, jsonify, request
from .orderService import (
    process_checkout, 
    get_user_order_history,
    get_all_orders,
    update_order_status,
    get_user_cart,
    add_to_cart,
    update_cart_item,
    clear_user_cart
)
from ..auth import token_required, admin_required

orders_bp = Blueprint('orders', __name__)

# --- SHOPPING CART ROUTES ---

@orders_bp.route('/cart', methods=['GET'])
@token_required
def view_cart():
    """Retrieves the user's current shopping cart"""
    user_id = request.user_payload.get('user_id')
    result = get_user_cart(user_id)
    return jsonify(result), 200

@orders_bp.route('/cart/add', methods=['POST'])
@token_required
def add_cart_item():
    """Adds a book to the user's cart"""
    user_id = request.user_payload.get('user_id')
    data = request.json
    book_id = data.get('book_id')
    quantity = data.get('quantity', 1)
    
    if not book_id:
        return jsonify({"error": "book_id is required"}), 400
        
    result = add_to_cart(user_id, book_id, quantity)
    status = result.pop("status")
    return jsonify(result), status

@orders_bp.route('/cart/update', methods=['PUT'])
@token_required
def update_cart():
    """Updates the quantity of an item in the cart"""
    user_id = request.user_payload.get('user_id')
    data = request.json
    book_id = data.get('book_id')
    quantity = data.get('quantity')
    
    if book_id is None or quantity is None:
        return jsonify({"error": "book_id and quantity are required"}), 400
        
    result = update_cart_item(user_id, book_id, quantity)
    status = result.pop("status")
    return jsonify(result), status

@orders_bp.route('/cart/remove/<int:book_id>', methods=['DELETE'])
@token_required
def remove_cart_item(book_id):
    """Removes a book from the cart entirely"""
    user_id = request.user_payload.get('user_id')
    result = update_cart_item(user_id, book_id, 0)
    status = result.pop("status")
    return jsonify(result), status

@orders_bp.route('/cart/clear', methods=['DELETE'])
@token_required
def clear_cart():
    """Empties the user's cart"""
    user_id = request.user_payload.get('user_id')
    result = clear_user_cart(user_id)
    status = result.pop("status")
    return jsonify(result), status

# --- ORDER ROUTES ---

@orders_bp.route('/checkout', methods=['POST'])
@token_required
def checkout_route():
    """
    Initiates payment workflow. 
    MODIFIED: Now pulls items from database cart.
    Expects JSON:
    {
      "shipping_address": "123 Main St...",
      "payment": {"method": "Credit Card", "card_number": "1234567890123456"}
    }
    """
    user_id = request.user_payload.get('user_id')
    data = request.json
    
    if not data:
        return jsonify({"error": "Invalid request"}), 400
        
    shipping_address = data.get('shipping_address')
    payment_info = data.get('payment', {})
    
    # Process from database cart
    result = process_checkout(user_id, shipping_address, payment_info)
    
    status = result.pop("status")
    return jsonify(result), status

@orders_bp.route('/history', methods=['GET'])
@token_required
def history_route():
    """Fetches order history for a logged in user"""
    user_id = request.user_payload.get('user_id')
    result = get_user_order_history(user_id)
    
    status = result.pop("status")
    return jsonify(result.get("items", result)), status

# --- ADMIN ROUTES ---

@orders_bp.route('/admin/all', methods=['GET'])
@admin_required
def admin_get_orders():
    """Admin function: grabs all orders in system"""
    result = get_all_orders()
    status = result.pop("status")
    return jsonify(result.get("orders", result)), status

@orders_bp.route('/admin/<int:order_id>/dispatch', methods=['POST'])
@admin_required
def admin_dispatch_order(order_id):
    """Admin function: moves order from Pending to Shipped"""
    result = update_order_status(order_id, 'Shipped')
    status = result.pop("status")
    return jsonify(result), status
