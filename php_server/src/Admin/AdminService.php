<?php

namespace Admin;

use Core\Database;
use Exception;

class AdminService {
    /**
     * Authenticates an admin and returns a JWT token.
     * Now queries `users` table with role = 'admin'.
     */
    public function authenticateAdmin(string $email, string $password): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) return [null, null];

        if (password_verify($password, $admin['password'])) {
            $token = $this->generateToken($admin['id'], 'admin');
            // Return fields the controller expects: Username, Email
            $admin['Username'] = $admin['name'];
            $admin['Email']    = $admin['email'];
            return [$token, $admin];
        }

        return [null, null];
    }

    private function generateToken(int $adminId, string $role): string {
        $secretKey = $_ENV['SECRET_KEY'] ?? 'fallback_secret_key';
        $payload = [
            'iat'  => time(),
            'exp'  => time() + (24 * 60 * 60),
            'sub'  => (string) $adminId,
            'role' => $role
        ];
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
    }

    public function getAllAdmins(): array {
        $db = Database::get();
        $stmt = $db->query(
            "SELECT id AS AdminID, name AS Username, email AS Email
             FROM users WHERE role = 'admin'"
        );
        return $stmt->fetchAll();
    }

    public function getAdminById(int $adminId): ?array {
        $db = Database::get();
        $stmt = $db->prepare(
            "SELECT id AS AdminID, name AS Username, email AS Email,
                    address AS Address, phone AS Phone
             FROM users WHERE id = ? AND role = 'admin'"
        );
        $stmt->execute([$adminId]);
        $admin = $stmt->fetch();
        return $admin ?: null;
    }

    public function createAdmin(array $data): array {
        $db = Database::get();

        // Check if username (name) already taken among admins
        $stmt = $db->prepare("SELECT id FROM users WHERE name = ? AND role = 'admin'");
        $stmt->execute([$data['Username']]);
        if ($stmt->fetch()) return [null, "Username already exists"];

        $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            "INSERT INTO users (email, password, name, address, phone, role)
             VALUES (?, ?, ?, ?, ?, 'admin')"
        );
        try {
            $stmt->execute([
                $data['Email'],
                $hashedPassword,
                $data['Username'],
                $data['Address'] ?? null,
                $data['Phone']   ?? null
            ]);
            $adminId = $db->lastInsertId();
            return [$adminId, null];
        } catch (Exception $e) {
            return [null, $e->getMessage()];
        }
    }

    public function updateAdmin(int $adminId, array $data): bool {
        $db = Database::get();
        $fields = [];
        $params = [];

        if (isset($data['Username'])) { $fields[] = "name = ?";     $params[] = $data['Username']; }
        if (isset($data['Email']))    { $fields[] = "email = ?";    $params[] = $data['Email']; }
        if (isset($data['Address']))  { $fields[] = "address = ?";  $params[] = $data['Address']; }
        if (isset($data['Phone']))    { $fields[] = "phone = ?";    $params[] = $data['Phone']; }
        if (isset($data['Password'])) { $fields[] = "password = ?"; $params[] = password_hash($data['Password'], PASSWORD_DEFAULT); }

        if (empty($fields)) return true;

        $params[] = $adminId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ? AND role = 'admin'";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteAdmin(int $adminId): bool {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM users WHERE id = ? AND role = 'admin'");
        return $stmt->execute([$adminId]);
    }

    /**
     * Admin Dashboard Analytics
     * Now reads from `orders` and `books` instead of old tables.
     */
    public function getDashboardStats(): array {
        $db = Database::get();

        // 1. Total Revenue (from orders where payment succeeded)
        $stmt = $db->query("SELECT SUM(total_amount) AS total FROM orders WHERE payment_status = 'Success'");
        $revenue = $stmt->fetch()['total'] ?? 0;

        // 2. Active Users (users with at least one non-cancelled order)
        $stmt = $db->query("SELECT COUNT(DISTINCT user_id) AS count FROM orders WHERE status != 'Cancelled'");
        $activeUsers = $stmt->fetch()['count'] ?? 0;

        // 3. Low Stock Alerts (stock < 10)
        $stmt = $db->query(
            "SELECT id AS BookID, title AS Title, stock_quantity AS StockQuantity
             FROM books WHERE stock_quantity < 10"
        );
        $lowStockBooks = $stmt->fetchAll();

        return [
            "total_revenue"    => (string) $revenue,
            "active_users"     => (int) $activeUsers,
            "low_stock_count"  => count($lowStockBooks),
            "low_stock_alerts" => $lowStockBooks,
            "timestamp"        => date('Y-m-d H:i:s')
        ];
    }
}
