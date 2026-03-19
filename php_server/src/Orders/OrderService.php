<?php

namespace Orders;

use Core\Database;
use Exception;
use PDO;

class OrderService {
    // --- CART METHODS ---

    public function getUserCart(int $userId): array {
        $db = Database::get();
        
        // Ensure cart exists
        $stmt = $db->prepare("INSERT IGNORE INTO cart (UserID) VALUES (?)");
        $stmt->execute([$userId]);
        
        $stmt = $db->prepare("SELECT CartID FROM cart WHERE UserID = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        $cartId = $cart['CartID'];

        $stmt = $db->prepare("
            SELECT ci.BookID, ci.Quantity, b.Title, b.Price, b.ISBN 
            FROM cartitem ci
            JOIN book b ON ci.BookID = b.BookID
            WHERE ci.CartID = ?
        ");
        $stmt->execute([$cartId]);
        $items = $stmt->fetchAll();

        $total = 0;
        foreach ($items as &$item) {
            $item['Price'] = (string) $item['Price'];
            $total += (float)$item['Price'] * $item['Quantity'];
        }

        return [
            "items" => $items,
            "total_amount" => (string) $total,
            "item_count" => count($items)
        ];
    }

    public function addToCart(int $userId, int $bookId, int $quantity): bool {
        $db = Database::get();
        $this->ensureCartExists($userId);
        $cartId = $this->getCartId($userId);

        // Check if item exists
        $stmt = $db->prepare("SELECT CartItemID, Quantity FROM cartitem WHERE CartID = ? AND BookID = ?");
        $stmt->execute([$cartId, $bookId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newQuantity = $existing['Quantity'] + $quantity;
            $stmt = $db->prepare("UPDATE cartitem SET Quantity = ? WHERE CartItemID = ?");
            return $stmt->execute([$newQuantity, $existing['CartItemID']]);
        } else {
            $stmt = $db->prepare("INSERT INTO cartitem (CartID, BookID, Quantity) VALUES (?, ?, ?)");
            return $stmt->execute([$cartId, $bookId, $quantity]);
        }
    }

    public function updateCartItem(int $userId, int $bookId, int $quantity): bool {
        $db = Database::get();
        $cartId = $this->getCartId($userId);

        if ($quantity <= 0) {
            $stmt = $db->prepare("DELETE FROM cartitem WHERE CartID = ? AND BookID = ?");
            return $stmt->execute([$cartId, $bookId]);
        }

        $stmt = $db->prepare("UPDATE cartitem SET Quantity = ? WHERE CartID = ? AND BookID = ?");
        return $stmt->execute([$quantity, $cartId, $bookId]);
    }

    public function clearCart(int $userId): bool {
        $db = Database::get();
        $cartId = $this->getCartId($userId);
        $stmt = $db->prepare("DELETE FROM cartitem WHERE CartID = ?");
        return $stmt->execute([$cartId]);
    }

    // --- ORDER METHODS ---

    public function checkout(int $userId, string $shippingAddress, array $paymentInfo): array {
        $db = Database::get();
        $cart = $this->getUserCart($userId);
        
        if (empty($cart['items'])) {
            throw new Exception("Cart is empty");
        }

        $db->beginTransaction();
        try {
            // 1. Create Order
            $stmt = $db->prepare("INSERT INTO `order` (UserID, TotalAmount, ShippingAddress, OrderStatus) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$userId, $cart['total_amount'], $shippingAddress]);
            $orderId = $db->lastInsertId();

            // 2. Create Order Items
            foreach ($cart['items'] as $item) {
                $stmt = $db->prepare("INSERT INTO orderitem (OrderID, BookID, Quantity, UnitPrice) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $item['BookID'], $item['Quantity'], $item['Price']]);
            }

            // 3. Create Payment record
            $stmt = $db->prepare("INSERT INTO payment (OrderID, PaymentMethod, TransactionID, PaymentStatus, Amount, PaymentDate) VALUES (?, ?, ?, 'Success', ?, NOW())");
            $stmt->execute([$orderId, $paymentInfo['method'] ?? 'COD', 'PHP_TXN_' . uniqid(), $cart['total_amount']]);

            // 4. Clear Cart
            $this->clearCart($userId);

            $db->commit();
            return ["message" => "Order placed successfully", "OrderID" => $orderId, "status" => 201];
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    public function getUserOrderHistory(int $userId): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM `order` WHERE UserID = ? ORDER BY OrderDate DESC");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['TotalAmount'] = (string) $order['TotalAmount'];
            $order['Items'] = $this->getOrderItems($order['OrderID']);
        }

        return $orders;
    }

    public function getAllOrders(): array {
        $db = Database::get();
        $stmt = $db->query("SELECT o.*, u.Email FROM `order` o JOIN user u ON o.UserID = u.UserID ORDER BY o.OrderDate DESC");
        $orders = $stmt->fetchAll();

        foreach ($orders as &$order) {
            $order['TotalAmount'] = (string) $order['TotalAmount'];
        }

        return $orders;
    }

    public function updateOrderStatus(int $orderId, string $status): bool {
        $db = Database::get();
        $stmt = $db->prepare("UPDATE `order` SET OrderStatus = ? WHERE OrderID = ?");
        return $stmt->execute([$status, $orderId]);
    }

    // --- HELPERS ---

    private function ensureCartExists(int $userId): void {
        $db = Database::get();
        $stmt = $db->prepare("INSERT IGNORE INTO cart (UserID) VALUES (?)");
        $stmt->execute([$userId]);
    }

    private function getCartId(int $userId): int {
        $db = Database::get();
        $stmt = $db->prepare("SELECT CartID FROM cart WHERE UserID = ?");
        $stmt->execute([$userId]);
        $cart = $stmt->fetch();
        return (int) $cart['CartID'];
    }

    private function getOrderItems(int $orderId): array {
        $db = Database::get();
        $stmt = $db->prepare("
            SELECT oi.*, b.Title 
            FROM orderitem oi 
            JOIN book b ON oi.BookID = b.BookID 
            WHERE oi.OrderID = ?
        ");
        $stmt->execute([$orderId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['UnitPrice'] = (string) $item['UnitPrice'];
        }
        return $items;
    }
}
