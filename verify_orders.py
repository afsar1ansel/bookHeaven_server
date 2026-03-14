import requests
import json

BASE_URL = "http://127.0.0.1:5000/api"

def verify_orders_with_cart():
    # 1. Login to get token
    login_data = {
        "Email": "afsar@gmail.com",
        "Password": "afsar@123"
    }
    response = requests.post(f"{BASE_URL}/admin/login", json=login_data)
    if response.status_code != 200:
        print(f"Login failed: {response.text}")
        return
    
    token = response.json().get("token")
    headers = {"Authorization": f"Bearer {token}"}
    print("✓ Logged in")

    # 2. Get a book to order
    response = requests.get(f"{BASE_URL}/books/", headers=headers)
    books = response.json()
    if response.status_code != 200 or len(books) == 0:
        print("Required books not available for testing")
        return
    book_id = books[0]["BookID"]
    print(f"✓ Found book: {books[0]['Title']} (ID: {book_id})")

    # 3. Add to Cart
    print("-> Adding book to cart...")
    cart_payload = {"book_id": book_id, "quantity": 1}
    response = requests.post(f"{BASE_URL}/orders/cart/add", json=cart_payload, headers=headers)
    if response.status_code == 200:
        print(f"✓ {response.json().get('message')}")
    else:
        print(f"✗ Failed to add to cart: {response.text}")
        return

    # 4. View Cart
    response = requests.get(f"{BASE_URL}/orders/cart", headers=headers)
    if response.status_code == 200:
        cart_data = response.json()
        print(f"✓ Cart fetched. Total items: {cart_data.get('item_count')}, Total Amount: {cart_data.get('total_amount')}")
    else:
        print(f"✗ Failed to fetch cart: {response.text}")
        return

    # 5. Test Checkout (Pulling from DB Cart)
    print("-> Initiating checkout...")
    checkout_payload = {
        "shipping_address": "456 Backend Lane, Server City",
        "payment": {"method": "Credit Card", "card_number": "1234567890123456"}
    }
    response = requests.post(f"{BASE_URL}/orders/checkout", json=checkout_payload, headers=headers)
    
    if response.status_code == 201:
        order_data = response.json()
        print(f"✓ Checkout Successful! Order ID: {order_data.get('order_id')}")
        order_id = order_data.get('order_id')
    else:
        print(f"✗ Checkout Failed: {response.text}")
        return

    # 6. Verify Cart is cleared
    response = requests.get(f"{BASE_URL}/orders/cart", headers=headers)
    if response.status_code == 200:
        if response.json().get('item_count') == 0:
             print("✓ Cart cleared after successful checkout")
        else:
             print("✗ Cart was NOT cleared after checkout")

    # 7. Test Order History
    response = requests.get(f"{BASE_URL}/orders/history", headers=headers)
    if response.status_code == 200:
        history = response.json()
        print(f"✓ History fetched. Total orders: {len(history)}")
    else:
        print(f"✗ History fetch failed: {response.text}")

    # 8. Admin Dispatch
    response = requests.post(f"{BASE_URL}/orders/admin/{order_id}/dispatch", headers=headers)
    if response.status_code == 200:
        print(f"✓ Admin dispatched order {order_id} successfully.")
    else:
        print(f"✗ Admin dispatch failed: {response.text}")

if __name__ == "__main__":
    verify_orders_with_cart()
