-- ============================================================
-- BookHeaven: Step 2 — Migrate Existing Data to New 4 Tables
-- Run AFTER 001_create_new_schema.sql
-- Safe to re-run (uses ON DUPLICATE KEY UPDATE to skip existing)
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- MIGRATE 1: `user` → `users` (role = 'user')
-- Preserves original UserIDs so orders keep their user_id refs
-- ============================================================
INSERT INTO `users` (`id`, `email`, `password`, `name`, `address`, `phone`, `role`, `created_at`)
SELECT
    `UserID`,
    `Email`,
    `Password`,
    `Name`,
    `Address`,
    `Phone`,
    'user',
    NOW()
FROM `user`
ON DUPLICATE KEY UPDATE `role` = VALUES(`role`);

SELECT CONCAT('Users migrated: ', ROW_COUNT()) AS step1_users;

-- ============================================================
-- MIGRATE 2: `admin` → `users` (role = 'admin')
-- AdminIDs are NOT preserved (no FKs point to admin table)
-- Username mapped to `name` column
-- ============================================================
INSERT INTO `users` (`email`, `password`, `name`, `address`, `phone`, `role`, `created_at`)
SELECT
    `Email`,
    `Password`,
    `Username`,
    `Address`,
    `Phone`,
    'admin',
    NOW()
FROM `admin`
ON DUPLICATE KEY UPDATE `role` = 'admin';

SELECT CONCAT('Admins migrated: ', ROW_COUNT()) AS step2_admins;

-- ============================================================
-- MIGRATE 3: `book` + `author` + `bookauthor` → `books`
-- Authors are embedded as JSON array per book
-- Preserves original BookIDs so order items references still work
-- ============================================================
INSERT INTO `books` (`id`, `isbn`, `title`, `description`, `price`, `stock_quantity`, `format`, `publication_date`, `rating`, `cover_image_url`, `category`, `authors`, `created_at`)
SELECT
    b.`BookID`,
    b.`ISBN`,
    b.`Title`,
    b.`Description`,
    b.`Price`,
    b.`StockQuantity`,
    b.`Format`,
    b.`PublicationDate`,
    b.`Rating`,
    'https://picsum.photos/400/600',
    (SELECT MIN(cat.`Name`) FROM `bookcategory` bc JOIN `category` cat ON bc.`CategoryID` = cat.`CategoryID` WHERE bc.`BookID` = b.`BookID`),
    COALESCE(
        (
            SELECT JSON_ARRAYAGG(a.`Name`)
            FROM `bookauthor` ba
            JOIN `author` a ON ba.`AuthorID` = a.`AuthorID`
            WHERE ba.`BookID` = b.`BookID`
        ),
        JSON_ARRAY()
    ),
    NOW()
FROM `book` b
ON DUPLICATE KEY UPDATE `isbn` = VALUES(`isbn`), `category` = VALUES(`category`);

SELECT CONCAT('Books migrated: ', ROW_COUNT()) AS step3_books;

-- ============================================================
-- MIGRATE 4: `cart` + `cartitem` → `carts`
-- Cart items embedded as JSON array per user
-- ============================================================
INSERT INTO `carts` (`user_id`, `items`, `updated_at`)
SELECT
    c.`UserID`,
    COALESCE(
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'book_id',  ci.`BookID`,
                    'quantity', ci.`Quantity`
                )
            )
            FROM `cartitem` ci
            WHERE ci.`CartID` = c.`CartID`
        ),
        JSON_ARRAY()
    ),
    NOW()
FROM `cart` c
WHERE c.`UserID` IS NOT NULL AND c.`UserID` IN (SELECT id FROM `users`)
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

SELECT CONCAT('Carts migrated: ', ROW_COUNT()) AS step4_carts;

-- ============================================================
-- MIGRATE 5: `order` + `orderitem` + `payment` → `orders`
-- Order items embedded as JSON array (title snapshot included)
-- Payment fields embedded as columns
-- Preserves original OrderIDs
-- ============================================================
INSERT INTO `orders` (`id`, `user_id`, `total_amount`, `shipping_address`, `status`, `payment_method`, `payment_status`, `transaction_id`, `items`, `order_date`)
SELECT
    o.`OrderID`,
    o.`UserID`,
    o.`TotalAmount`,
    o.`ShippingAddress`,
    o.`OrderStatus`,
    COALESCE(p.`PaymentMethod`, 'COD'),
    COALESCE(p.`PaymentStatus`, 'Pending'),
    p.`TransactionID`,
    COALESCE(
        (
            SELECT JSON_ARRAYAGG(
                JSON_OBJECT(
                    'book_id',    oi.`BookID`,
                    'title',      bk.`Title`,
                    'quantity',   oi.`Quantity`,
                    'unit_price', CAST(oi.`UnitPrice` AS CHAR)
                )
            )
            FROM `orderitem` oi
            JOIN `book` bk ON oi.`BookID` = bk.`BookID`
            WHERE oi.`OrderID` = o.`OrderID`
        ),
        JSON_ARRAY()
    ),
    o.`OrderDate`
FROM `order` o
LEFT JOIN `payment` p ON p.`OrderID` = o.`OrderID`
WHERE o.`UserID` IS NOT NULL AND o.`UserID` IN (SELECT id FROM `users`)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

SELECT CONCAT('Orders migrated: ', ROW_COUNT()) AS step5_orders;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- FINAL SUMMARY: Row counts in all 4 new tables
-- ============================================================
SELECT 'Migration complete! Row counts:' AS result;

SELECT 'users'  AS table_name, COUNT(*) AS row_count FROM `users`
UNION ALL
SELECT 'books',  COUNT(*) FROM `books`
UNION ALL
SELECT 'carts',  COUNT(*) FROM `carts`
UNION ALL
SELECT 'orders', COUNT(*) FROM `orders`;
