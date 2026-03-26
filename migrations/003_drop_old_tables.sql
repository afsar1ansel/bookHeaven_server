-- ============================================================
-- BookHeaven: Step 3 — Drop Old Tables
-- ⚠️  WARNING: ONLY run this AFTER verifying migration is correct
-- ⚠️  TAKE A DATABASE BACKUP before running this!
-- ⚠️  THIS CANNOT BE UNDONE
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- Drop join/child tables first (they reference the parent tables)
DROP TABLE IF EXISTS `cartitem`;
DROP TABLE IF EXISTS `orderitem`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `bookauthor`;

-- Drop parent tables
DROP TABLE IF EXISTS `cart`;
DROP TABLE IF EXISTS `order`;
DROP TABLE IF EXISTS `book`;
DROP TABLE IF EXISTS `author`;
DROP TABLE IF EXISTS `category`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `admin`;

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Step 3 complete: Old tables dropped. Database now has 4 tables only.' AS result;

-- Verify what remains
SHOW TABLES;
