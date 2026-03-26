# BookHeaven — Database Migration
## 13 Tables → 4 Tables

This folder contains the SQL migration scripts to reduce the BookHeaven
database from the original 11-13 tables down to **4 clean tables**.

---

## New Schema (4 Tables)

| Table    | Absorbs                                          |
|----------|--------------------------------------------------|
| `users`  | `user` + `admin` (unified via `role` column)     |
| `books`  | `book` + `author` + `bookauthor` + `category`   |
| `carts`  | `cart` + `cartitem` (items stored as JSON)       |
| `orders` | `order` + `orderitem` + `payment` (items as JSON)|

---

## How to Run (in DBeaver)

### Step 1 — Create new tables
Open `001_create_new_schema.sql` in DBeaver and run it.
This creates the 4 new tables (does NOT touch existing data).

### Step 2 — Migrate your data
Open `002_migrate_data.sql` in DBeaver and run it.
This copies all existing data from old tables into the new ones.
Check the row counts printed at the end to verify!

### Step 3 — Verify everything works
- Start the PHP server: `php -S localhost:5000 -t public`
- Test your API endpoints (login, books, cart, orders)
- Open DBeaver — you should see the 4 new tables with data

### Step 4 — Drop old tables (POINT OF NO RETURN ⚠️)
Only run `003_drop_old_tables.sql` AFTER you confirm everything works.
**Take a DB backup before this step.**

---

## Requirements
- MySQL 5.7.22+ or MySQL 8.0+ (for JSON_ARRAYAGG support)
- Run scripts in order: 001 → 002 → 003
