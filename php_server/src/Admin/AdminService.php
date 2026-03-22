<?php

namespace Admin;

use Core\Database;
use Exception;

class AdminService {
    /**
     * Authenticates an admin and returns a JWT token.
     * Replaces Flask's authenticate_admin().
     */
    public function authenticateAdmin(string $email, string $password): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM admin WHERE Email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin) return [null, null];

        // Verify password (bcrypt)
        if (password_verify($password, $admin['Password'])) {
            $token = $this->generateToken($admin['AdminID'], 'admin');
            return [$token, $admin];
        }

        return [null, null];
    }

    private function generateToken(int $adminId, string $role): string {
        $secretKey = $_ENV['SECRET_KEY'] ?? 'fallback_secret_key';
        $payload = [
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), 
            'sub' => (string) $adminId,
            'role' => $role
        ];
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
    }

    public function getAllAdmins(): array {
        $db = Database::get();
        $stmt = $db->query("SELECT AdminID, Username, Email FROM admin");
        return $stmt->fetchAll();
    }

    public function createAdmin(array $data): array {
        $db = Database::get();
        
        // Check username
        $stmt = $db->prepare("SELECT AdminID FROM admin WHERE Username = ?");
        $stmt->execute([$data['Username']]);
        if ($stmt->fetch()) return [null, "Username already exists"];

        $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO admin (Username, Email, Password, Address, Phone) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $data['Username'],
                $data['Email'],
                $hashedPassword,
                $data['Address'] ?? null,
                $data['Phone'] ?? null
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
        
        if (isset($data['Username'])) { $fields[] = "Username = ?"; $params[] = $data['Username']; }
        if (isset($data['Email'])) { $fields[] = "Email = ?"; $params[] = $data['Email']; }
        if (isset($data['Address'])) { $fields[] = "Address = ?"; $params[] = $data['Address']; }
        if (isset($data['Phone'])) { $fields[] = "Phone = ?"; $params[] = $data['Phone']; }
        if (isset($data['Password'])) { $fields[] = "Password = ?"; $params[] = password_hash($data['Password'], PASSWORD_DEFAULT); }

        if (empty($fields)) return true;

        $params[] = $adminId;
        $sql = "UPDATE admin SET " . implode(', ', $fields) . " WHERE AdminID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }

    public function deleteAdmin(int $adminId): bool {
        $db = Database::get();
        $stmt = $db->prepare("DELETE FROM admin WHERE AdminID = ?");
        return $stmt->execute([$adminId]);
    }

    /**
     * Module 3: Admin Dashboard & Analytics
     */
    public function getDashboardStats(): array {
        $db = Database::get();

        // 1. Total Revenue
        $stmt = $db->query("SELECT SUM(Amount) as total FROM payment WHERE PaymentStatus = 'Success'");
        $revenue = $stmt->fetch()['total'] ?? 0;

        // 2. Active Users (Users with at least one order)
        $stmt = $db->query("SELECT COUNT(DISTINCT UserID) as count FROM `order` WHERE OrderStatus != 'Cancelled'");
        $activeUsers = $stmt->fetch()['count'] ?? 0;

        // 3. Low Stock Alerts (Stock < 10)
        $stmt = $db->query("SELECT BookID, Title, StockQuantity FROM book WHERE StockQuantity < 10");
        $lowStockBooks = $stmt->fetchAll();

        return [
            "total_revenue" => (string) $revenue,
            "active_users" => (int) $activeUsers,
            "low_stock_count" => count($lowStockBooks),
            "low_stock_alerts" => $lowStockBooks,
            "timestamp" => date('Y-m-d H:i:s')
        ];
    }
}
