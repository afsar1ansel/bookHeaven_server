import requests
import json

BASE_URL = "http://127.0.0.1:5000/api"

def verify_api():
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

    # 2. Get all books
    response = requests.get(f"{BASE_URL}/books/", headers=headers)
    if response.status_code == 200:
        books = response.json()
        print(f"Total books retrieved: {len(books)}")
        if len(books) > 0:
            print(f"First book authors: {books[0].get('Authors')}")
    else:
        print(f"Failed to get books: {response.text}")

    # 3. Get a specific book
    if len(books) > 0:
        book_id = books[0]["BookID"]
        response = requests.get(f"{BASE_URL}/books/{book_id}", headers=headers)
        if response.status_code == 200:
            book = response.json()
            print(f"Book {book_id} authors: {book.get('Authors')}")
        else:
            print(f"Failed to get book {book_id}: {response.text}")

if __name__ == "__main__":
    verify_api()
