-- ============================================================
-- BookHeaven: Step 1 — Create New 4-Table Schema
-- Run this FIRST. It only CREATES tables, touches nothing else.
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- -------------------------------------------------------
-- TABLE 1: users
-- Replaces: `user` + `admin`
-- Differentiated by role column: 'user' | 'admin'
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `email`      VARCHAR(255) NOT NULL UNIQUE,
    `password`   VARCHAR(255) NOT NULL,
    `name`       VARCHAR(255),
    `address`    TEXT,
    `phone`      VARCHAR(50),
    `role`       ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- TABLE 2: books
-- Replaces: `book` + `author` + `bookauthor` + `category`
-- authors stored as JSON array: ["J.K. Rowling", "Co Author"]
-- category stored as VARCHAR column
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `books` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `isbn`             VARCHAR(20) UNIQUE,
    `title`            VARCHAR(255) NOT NULL,
    `description`      TEXT,
    `price`            DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `stock_quantity`   INT NOT NULL DEFAULT 0,
    `format`           VARCHAR(50),
    `publication_date` DATE,
    `rating`           DECIMAL(3, 1) DEFAULT 0.0,
    `cover_image_url`  VARCHAR(500),
    `category`         VARCHAR(100),
    `authors`          JSON,
    `created_at`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- TABLE 3: carts
-- Replaces: `cart` + `cartitem`
-- One row per user. items = JSON array:
-- [{"book_id": 1, "quantity": 2}, ...]
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `carts` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`    INT NOT NULL UNIQUE,
    `items`      JSON,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- TABLE 4: orders
-- Replaces: `order` + `orderitem` + `payment`
-- items = JSON array of ordered books (snapshot at time of order)
-- payment info stored as inline columns
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `orders` (
    `id`               INT AUTO_INCREMENT PRIMARY KEY,
    `user_id`          INT NOT NULL,
    `total_amount`     DECIMAL(10, 2) NOT NULL,
    `shipping_address` TEXT,
    `status`           ENUM('Pending', 'Shipped', 'Delivered', 'Cancelled') DEFAULT 'Pending',
    `payment_method`   VARCHAR(50),
    `payment_status`   ENUM('Success', 'Failed', 'Pending') DEFAULT 'Pending',
    `transaction_id`   VARCHAR(100),
    `items`            JSON,
    `order_date`       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Step 1 complete: 4 new tables created successfully.' AS result;
