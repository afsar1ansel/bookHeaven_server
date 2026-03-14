import uuid
import hashlib
from datetime import datetime, timedelta
from ..database import db
from .models import Order, OrderItem, Payment, DigitalDownload, Cart, CartItem
from ..books.models import Book

# Logic for Payment validation & DRM
def validate_payment(order_id):
    # Retrieve order and payment details
    payment = Payment.query.filter_by(OrderID=order_id).first()
    if not payment:
        return {"error": "Payment record not found", "status": 404}
    return {"message": "Payment verified", "payment_status": payment.PaymentStatus, "status": 200}

def manage_drm(order_item_id):
    # Generates or fetches a DRM link for an eBook
    download = DigitalDownload.query.filter_by(OrderItemID=order_item_id).first()
    if not download:
        return {"error": "Download link not found", "status": 404}
    
    if download.ExpiryDate < datetime.utcnow():
        return {"error": "Download link expired", "status": 403}
        
    return {"download_url": download.DownloadLink, "status": 200}

def _mock_payment_gateway(payment_info):
    """
    Simulates a payment gateway response.
    Returns a success token if credit card is not '0000', otherwise fails.
    """
    card_number = payment_info.get("card_number", "")
    if card_number == "0000000000000000":
        return {"status": "Fail", "message": "Insufficient funds"}
    return {"status": "Success", "transaction_id": f"txn_{uuid.uuid4().hex[:10]}"}

def _get_or_create_cart(user_id):
    """Internal helper to get or create a user's cart"""
    cart = Cart.query.filter_by(UserID=user_id).first()
    if not cart:
        cart = Cart(UserID=user_id)
        db.session.add(cart)
        db.session.commit()
    return cart

def get_user_cart(user_id):
    """Fetches the user's cart and items with calculated totals"""
    cart = _get_or_create_cart(user_id)
    cart_items = CartItem.query.filter_by(CartID=cart.CartID).all()
    
    items_data = []
    total_amount = 0
    
    for item in cart_items:
        book = Book.query.get(item.BookID)
        if book:
            subtotal = book.Price * item.Quantity
            total_amount += subtotal
            items_data.append({
                "book_id": book.BookID,
                "title": book.Title,
                "price": str(book.Price),
                "quantity": item.Quantity,
                "subtotal": str(subtotal),
                "format": book.Format,
                "cover_image": book.CoverImageURL
            })
            
    return {
        "cart_id": cart.CartID,
        "items": items_data,
        "total_amount": str(total_amount),
        "item_count": sum(item.Quantity for item in cart_items)
    }

def add_to_cart(user_id, book_id, quantity=1):
    """Adds a book to the cart or increments quantity"""
    book = Book.query.get(book_id)
    if not book:
        return {"error": "Book not found", "status": 404}
        
    cart = _get_or_create_cart(user_id)
    
    # Check if item already exists
    cart_item = CartItem.query.filter_by(CartID=cart.CartID, BookID=book_id).first()
    
    if cart_item:
        cart_item.Quantity += quantity
    else:
        cart_item = CartItem(CartID=cart.CartID, BookID=book_id, Quantity=quantity)
        db.session.add(cart_item)
        
    db.session.commit()
    return {"message": f"Added {book.Title} to cart", "status": 200}

def update_cart_item(user_id, book_id, quantity):
    """Updates the quantity of a cart item. If 0, removes it."""
    cart = _get_or_create_cart(user_id)
    cart_item = CartItem.query.filter_by(CartID=cart.CartID, BookID=book_id).first()
    
    if not cart_item:
        return {"error": "Item not in cart", "status": 404}
        
    if quantity <= 0:
        db.session.delete(cart_item)
        msg = "Item removed from cart"
    else:
        cart_item.Quantity = quantity
        msg = "Cart updated"
        
    db.session.commit()
    return {"message": msg, "status": 200}

def clear_user_cart(user_id):
    """Empties the user's cart"""
    cart = Cart.query.filter_by(UserID=user_id).first()
    if cart:
        CartItem.query.filter_by(CartID=cart.CartID).delete()
        db.session.commit()
    return {"message": "Cart cleared", "status": 200}

def process_checkout(user_id, shipping_address, payment_info):
    """
    MODIFIED: Now pulls items directly from the database Cart.
    """
    cart = Cart.query.filter_by(UserID=user_id).first()
    if not cart or not cart.items:
        return {"error": "Your cart is empty", "status": 400}

    # 1. Validation & Inventory Check
    total_amount = 0
    book_objects = []
    
    for item in cart.items:
        book = Book.query.get(item.BookID)
        if not book:
            continue
            
        if book.Format == 'Physical' and book.StockQuantity < item.Quantity:
            return {"error": f"Insufficient stock for {book.Title}. Only {book.StockQuantity} left.", "status": 400}
            
        total_amount += (book.Price * item.Quantity)
        book_objects.append({"book": book, "quantity": item.Quantity})

    if not book_objects:
         return {"error": "Cart contains invalid books", "status": 400}

    # 2. Financial Transaction (Dummy)
    payment_response = _mock_payment_gateway(payment_info)
    if payment_response["status"] == "Fail":
        return {"error": payment_response["message"], "status": 402}

    # 3. Execution Flow
    try:
        # Create Order
        new_order = Order(
            UserID=user_id,
            TotalAmount=total_amount,
            ShippingAddress=shipping_address,
            OrderStatus='Pending'
        )
        db.session.add(new_order)
        db.session.flush()

        # Payment Record
        new_payment = Payment(
            OrderID=new_order.OrderID,
            PaymentMethod=payment_info.get("method", "Credit Card"),
            TransactionID=payment_response["transaction_id"],
            PaymentStatus='Success',
            PaymentDate=datetime.utcnow(),
            Amount=total_amount
        )
        db.session.add(new_payment)

        # Process Items
        has_physical = False
        has_digital = False
        
        for item_data in book_objects:
            book = item_data["book"]
            quantity = item_data["quantity"]
            
            order_item = OrderItem(
                OrderID=new_order.OrderID,
                BookID=book.BookID,
                Quantity=quantity,
                UnitPrice=book.Price
            )
            db.session.add(order_item)
            db.session.flush()
            
            if book.Format == 'Physical':
                book.StockQuantity -= quantity
                db.session.add(book)
                has_physical = True
            elif book.Format in ['E-book', 'Audiobook']:
                unique_hash = hashlib.sha256(f"{user_id}_{book.BookID}_{uuid.uuid4()}".encode()).hexdigest()
                download = DigitalDownload(
                    OrderItemID=order_item.OrderItemID,
                    DownloadLink=f"/api/orders/download/{unique_hash}",
                    ExpiryDate=datetime.utcnow() + timedelta(days=365)
                )
                db.session.add(download)
                has_digital = True

        # 4. Clear the Cart after SUCCESS
        CartItem.query.filter_by(CartID=cart.CartID).delete()

        db.session.commit()
        
        # Prepare Response
        response_data = {
            "message": "Order placed successfully!",
            "order_id": new_order.OrderID,
            "transaction_id": payment_response["transaction_id"],
            "total_amount": str(total_amount)
        }
        
        if has_physical:
            response_data["estimated_delivery"] = (datetime.utcnow() + timedelta(days=5)).strftime("%Y-%m-%d")
        if has_digital:
            response_data["digital_fulfillment"] = "Check your history for downloads."
            
        return {**response_data, "status": 201}

    except Exception as e:
        db.session.rollback()
        return {"error": f"Internal server error: {str(e)}", "status": 500}

def get_user_order_history(user_id):
    """
    Fetches the order history for a specific user, including items
    and download links for digital products.
    """
    orders = Order.query.filter_by(UserID=user_id).order_by(Order.OrderDate.desc()).all()
    history = []
    
    for order in orders:
        items_data = []
        payment = Payment.query.filter_by(OrderID=order.OrderID).first()
        order_items = OrderItem.query.filter_by(OrderID=order.OrderID).all()
        
        for item in order_items:
            book = Book.query.get(item.BookID)
            item_dict = {
                "book_id": item.BookID,
                "title": book.Title if book else "Unknown",
                "format": book.Format if book else "Unknown",
                "quantity": item.Quantity,
                "unit_price": str(item.UnitPrice)
            }
            
            # Fetch download link if digital
            if book and book.Format in ['E-book', 'Audiobook']:
                download = DigitalDownload.query.filter_by(OrderItemID=item.OrderItemID).first()
                if download:
                    item_dict["download_link"] = download.DownloadLink
                    
            items_data.append(item_dict)
            
        history.append({
            "order_id": order.OrderID,
            "date": order.OrderDate.strftime("%Y-%m-%d %H:%M:%S") if order.OrderDate else None,
            "status": order.OrderStatus,
            "total_amount": str(order.TotalAmount),
            "payment_status": payment.PaymentStatus if payment else "Pending",
            "items": items_data
        })
        
    return {"items": history, "status": 200}
    
def get_all_orders():
    """Admin: Fetches all orders"""
    orders = Order.query.order_by(Order.OrderDate.desc()).all()
    all_orders = []
    
    for order in orders:
        all_orders.append({
            "order_id": order.OrderID,
            "user_id": order.UserID,
            "date": order.OrderDate.strftime("%Y-%m-%d %H:%M:%S") if order.OrderDate else None,
            "status": order.OrderStatus,
            "total_amount": str(order.TotalAmount)
        })
    return {"orders": all_orders, "status": 200}

def update_order_status(order_id, new_status):
    """Admin: Updates the fulfillment status of an order."""
    allowed_statuses = ['Pending', 'Shipped', 'Delivered', 'Cancelled']
    if new_status not in allowed_statuses:
        return {"error": f"Invalid status. Allowed: {allowed_statuses}", "status": 400}
        
    order = Order.query.get(order_id)
    if not order:
        return {"error": "Order not found", "status": 404}
        
    order.OrderStatus = new_status
    db.session.commit()
    
    return {"message": f"Order {order_id} status updated to {new_status}", "status": 200}
