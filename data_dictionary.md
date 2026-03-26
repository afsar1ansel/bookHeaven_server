# Data Dictionary: BookHeaven Database (4-Table Unified Model)

This document provides a detailed description of the database structure for the BookHeaven application. The database has been optimized from 13 tables down to 4 core tables using modern JSON embedding techniques.

---

## 🏗️ 1. TABLE: `users`
**Source Tables Merged**: `user`, `admin`  
**Purpose**: Stores all identity and authentication data for both customers and staff.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| **`id`** | `INT` | `PK`, `AI` | Unique identifier for each user profile. |
| **`email`** | `VARCHAR(255)` | `UNIQUE`, `NOT NULL` | The user's login email address. |
| **`password`** | `VARCHAR(255)` | `NOT NULL` | The hashed password (using PHP `password_hash`). |
| **`name`** | `VARCHAR(255)` | - | Full name (Username for admins). |
| **`address`** | `TEXT` | - | Primary shipping/contact address. |
| **`phone`** | `VARCHAR(50)` | - | Primary contact phone number. |
| **`role`** | `ENUM` | `NOT NULL` | The account type: `'user'` or `'admin'`. |
| **`created_at`** | `TIMESTAMP` | `DEFAULT NOW()` | The timestamp when the profile was created. |

---

## 📚 2. TABLE: `books`
**Source Tables Merged**: `book`, `author`, `bookauthor`, `category`  
**Purpose**: Stores all data related to the book catalog, including availability and classification.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| **`id`** | `INT` | `PK`, `AI` | Unique identifier for each specific book. |
| **`isbn`** | `VARCHAR(20)` | `UNIQUE` | International Standard Book Number. |
| **`title`** | `VARCHAR(255)` | `NOT NULL` | The main title of the book. |
| **`description`** | `TEXT` | - | A summary or synopsis of the book. |
| **`price`** | `DECIMAL(10,2)`| `NOT NULL` | The current unit price in USD. |
| **`stock_quantity`**| `INT` | `NOT NULL` | Current number of units in inventory. |
| **`format`** | `VARCHAR(50)` | - | Type of the book (e.g., Physical, Hardcover). |
| **`publication_date`**| `DATE` | - | The release date of the book. |
| **`rating`** | `DECIMAL(3,1)` | `DEFAULT 0.0` | Average customer review score (1–5). |
| **`cover_image_url`**| `VARCHAR(500)`| - | URL link to the book's cover art. |
| **`category`** | `VARCHAR(100)` | - | The primary genre (Fiction, Sci-Fi, etc.). |
| **`authors`** | `JSON` | - | A JSON array of author names. |
| **`created_at`** | `TIMESTAMP` | `DEFAULT NOW()` | The timestamp when the book was added to catalog. |

---

## 🛒 3. TABLE: `carts`
**Source Tables Merged**: `cart`, `cartitem`  
**Purpose**: Stores the active shopping cart state for each registered customer.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| **`id`** | `INT` | `PK`, `AI` | Internal cart record identifier. |
| **`user_id`** | `INT` | `FK`, `UNIQUE` | Links to `users.id`. One cart per user. |
| **`items`** | `JSON` | - | Array of objects: `[{"book_id": id, "quantity": n}, ...]`. |
| **`updated_at`** | `TIMESTAMP` | `ON UPDATE NOW()` | The last time the user modified their cart. |

---

## 📦 4. TABLE: `orders`
**Source Tables Merged**: `order`, `orderitem`, `payment`  
**Purpose**: Stores the history of all placed transactions and their current status.

| Column Name | Data Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| **`id`** | `INT` | `PK`, `AI` | Unique Order ID. |
| **`user_id`** | `INT` | `FK`, `NOT NULL`| Links to `users.id`. The customer placing the order. |
| **`total_amount`** | `DECIMAL(10,2)`| `NOT NULL` | Final sum total amount paid by user. |
| **`shipping_address`**| `TEXT` | - | Snapshot of address at time of order. |
| **`status`** | `ENUM` | `DEFAULT 'Pending'`| Status: `Pending`, `Shipped`, `Delivered`, `Cancelled`. |
| **`payment_method`**| `VARCHAR(50)` | - | Method used (e.g., Credit Card, COD). |
| **`payment_status`**| `ENUM` | `DEFAULT 'Pending'`| Status: `Success`, `Failed`, `Pending`. |
| **`transaction_id`**| `VARCHAR(100)` | - | Reference ID from the payment gateway. |
| **`items`** | `JSON` | `NOT NULL` | Snapshot of book titles/prices at time of order. |
| **`order_date`** | `TIMESTAMP` | `DEFAULT NOW()` | The timestamp when order was placed. |

---

### *Legend:*
- **PK**: Primary Key
- **FK**: Foreign Key
- **AI**: Auto Increment
- **FK Reference Constraint**: All Foreign Keys are set to `ON DELETE CASCADE`.
