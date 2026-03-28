<?php

namespace Orders;

use Core\Database;
use Exception;
use PDO;

class OrderService {

    // ============================================================
    // PRIVATE CART HELPERS
    // Cart items are stored as JSON in the `carts` table.
    // Format: [{"book_id": 1, "quantity": 2}, ...]
    // ============================================================

    /**
     * Returns raw cart items array for a user (from JSON column).
     */
    private function getRawCartItems(int $userId): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT items FROM carts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        if (!$row || $row['items'] === null) return [];
        return json_decode($row['items'], true) ?? [];
    }

    /**
     * Persists cart items array back to the JSON column (upsert).
     */
    private function saveCartItems(int $userId, array $items): void {
        $db = Database::get();
        $json = json_encode(array_values($items)); // re-index array
        $stmt = $db->prepare("
            INSERT INTO carts (user_id, items)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE items = VALUES(items), updated_at = NOW()
        ");
        $stmt->execute([$userId, $json]);
    }

    // ============================================================
    // CART METHODS
    // ============================================================

    /**
     * Get the user's cart with full book details joined in.
     */
    public function getUserCart(int $userId): array {
        $db = Database::get();
        $rawItems = $this->getRawCartItems($userId);

        if (empty($rawItems)) {
            return ['items' => [], 'total_amount' => '0', 'item_count' => 0];
        }

        // Fetch book details for all items in one query
        $bookIds      = array_column($rawItems, 'book_id');
        $placeholders = implode(',', array_fill(0, count($bookIds), '?'));
        $stmt         = $db->prepare("SELECT id, title, price, isbn FROM books WHERE id IN ({$placeholders})");
        $stmt->execute($bookIds);

        $booksById = [];
        foreach ($stmt->fetchAll() as $book) {
            $booksById[$book['id']] = $book;
        }

        $items = [];
        $total = 0.0;

        foreach ($rawItems as $cartItem) {
            $book = $booksById[$cartItem['book_id']] ?? null;
            if (!$book) continue; // skip if book was deleted

            $total += (float) $book['price'] * $cartItem['quantity'];
            $items[] = [
                'BookID'   => $cartItem['book_id'],
                'Title'    => $book['title'],
                'Price'    => (string) $book['price'],
                'ISBN'     => $book['isbn'],
                'Quantity' => $cartItem['quantity'],
            ];
        }

        return [
            'items'        => $items,
            'total_amount' => (string) $total,
            'item_count'   => count($items),
        ];
    }

    /**
     * Add a book to cart. Validates stock before adding.
     */
    public function addToCart(int $userId, int $bookId, int $quantity): bool {
        $db = Database::get();

        // Stock validation
        $stmt = $db->prepare("SELECT stock_quantity, title FROM books WHERE id = ?");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        if (!$book) throw new Exception("Book not found");

        if ($book['stock_quantity'] < $quantity) {
            throw new Exception("Only {$book['stock_quantity']} units of '{$book['title']}' are available in stock.");
        }

        $items = $this->getRawCartItems($userId);
        $found = false;

        foreach ($items as &$item) {
            if ((int)$item['book_id'] === $bookId) {
                $newQty = $item['quantity'] + $quantity;
                if ($book['stock_quantity'] < $newQty) {
                    throw new Exception("Cannot add more. Total in cart ({$newQty}) exceeds available stock ({$book['stock_quantity']}).");
                }
                $item['quantity'] = $newQty;
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $items[] = ['book_id' => $bookId, 'quantity' => $quantity];
        }

        $this->saveCartItems($userId, $items);
        return true;
    }

    /**
     * Update quantity of a cart item. quantity=0 removes the item.
     */
    public function updateCartItem(int $userId, int $bookId, int $quantity): bool {
        $db = Database::get();

        $items = $this->getRawCartItems($userId);

        if ($quantity <= 0) {
            // Remove item
            $items = array_filter($items, fn($i) => (int)$i['book_id'] !== $bookId);
        } else {
            // Stock validation
            $stmt = $db->prepare("SELECT stock_quantity FROM books WHERE id = ?");
            $stmt->execute([$bookId]);
            $stock = $stmt->fetchColumn();
            if ($stock !== false && $stock < $quantity) {
                throw new Exception("Requested quantity ($quantity) exceeds available stock ($stock).");
            }

            $found = false;
            foreach ($items as &$item) {
                if ((int)$item['book_id'] === $bookId) {
                    $item['quantity'] = $quantity;
                    $found = true;
                    break;
                }
            }
            unset($item);

            if (!$found) {
                $items[] = ['book_id' => $bookId, 'quantity' => $quantity];
            }
        }

        $this->saveCartItems($userId, $items);
        return true;
    }

    /**
     * Empty the user's cart.
     */
    public function clearCart(int $userId): bool {
        $this->saveCartItems($userId, []);
        return true;
    }

    // ============================================================
    // ORDER METHODS
    // ============================================================

    /**
     * Checkout: validates stock, creates order with embedded items
     * and payment info, decrements stock, clears cart.
     */
    public function checkout(int $userId, string $shippingAddress, array $paymentInfo): array {
        $db   = Database::get();
        $cart = $this->getUserCart($userId);

        if (empty($cart['items'])) {
            throw new Exception("Cart is empty");
        }

        $db->beginTransaction();
        try {
            $orderItems = [];

            foreach ($cart['items'] as $item) {
                // Final stock check with row lock
                $stmt = $db->prepare("SELECT stock_quantity FROM books WHERE id = ? FOR UPDATE");
                $stmt->execute([$item['BookID']]);
                $currentStock = $stmt->fetchColumn();

                if ($currentStock < $item['Quantity']) {
                    throw new Exception("Stock changed for '{$item['Title']}'. Only {$currentStock} available.");
                }

                // Decrement stock
                $stmt = $db->prepare("UPDATE books SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['Quantity'], $item['BookID']]);

                // Build order items snapshot (title captured at time of order)
                $orderItems[] = [
                    'BookID'    => $item['BookID'],
                    'Title'     => $item['Title'],
                    'Quantity'  => $item['Quantity'],
                    'UnitPrice' => $item['Price'],
                ];
            }

            $txnId = 'PHP_TXN_' . uniqid();

            // Create order with embedded items + payment
            $stmt = $db->prepare("
                INSERT INTO orders
                    (user_id, total_amount, shipping_address, status, payment_method, payment_status, transaction_id, items)
                VALUES
                    (?, ?, ?, 'Pending', ?, 'Success', ?, ?)
            ");
            $stmt->execute([
                $userId,
                $cart['total_amount'],
                $shippingAddress,
                $paymentInfo['method'] ?? 'COD',
                $txnId,
                json_encode($orderItems)
            ]);
            $orderId = $db->lastInsertId();

            // Clear cart
            $this->clearCart($userId);

            $db->commit();
            return [
                "message"  => "Order placed successfully",
                "OrderID"  => $orderId,
                "status"   => 201
            ];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Get order history for a user. Items decoded from JSON.
     */
    public function getUserOrderHistory(int $userId): array {
        $db   = Database::get();
        $stmt = $db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['OrderID']         = $order['id'];
            $order['UserID']          = $order['user_id'];
            $order['TotalAmount']     = (string) $order['total_amount'];
            $order['ShippingAddress'] = $order['shipping_address'];
            $order['OrderStatus']     = $order['status'];
            $order['OrderDate']       = $order['order_date'];
            $order['PaymentStatus']   = $order['payment_status'];
            $order['Items']           = json_decode($order['items'] ?? '[]', true) ?? [];
        }

        return $orders;
    }

    /**
     * Get all orders (admin view). Joins with users for email.
     */
    public function getAllOrders(): array {
        $db   = Database::get();
        $stmt = $db->query("
            SELECT o.*, u.email AS Email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            ORDER BY o.order_date DESC
        ");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['OrderID']     = $order['id'];
            $order['UserID']      = $order['user_id'];
            $order['TotalAmount'] = (string) $order['total_amount'];
            $order['OrderStatus'] = $order['status'];
            $order['OrderDate']   = $order['order_date'];
        }

        return $orders;
    }

    /**
     * Update order status (e.g., Pending → Shipped).
     */
    public function updateOrderStatus(int $orderId, string $status): bool {
        $db   = Database::get();
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $orderId]);
    }
}
