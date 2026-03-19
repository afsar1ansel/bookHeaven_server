<?php

namespace User;

use Core\Database;
use Exception;

class UserService {
    /**
     * Registers a new user.
     * Replaces Flask's register_user().
     */
    public function registerUser(array $data): array {
        $db = Database::get();
        
        // Check if user exists
        $stmt = $db->prepare("SELECT UserID FROM user WHERE Email = ?");
        $stmt->execute([$data['Email']]);
        if ($stmt->fetch()) {
            return [null, "User with this email already exists"];
        }

        // Hash password (using bcrypt)
        $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO user (Email, Password, Name, Address, Phone) VALUES (?, ?, ?, ?, ?)");
        try {
            $stmt->execute([
                $data['Email'],
                $hashedPassword,
                $data['Name'],
                $data['Address'] ?? null,
                $data['Phone'] ?? null
            ]);
            $userId = $db->lastInsertId();
            return [$userId, null];
        } catch (Exception $e) {
            return [null, $e->getMessage()];
        }
    }

    /**
     * Authenticates a user and returns a JWT token.
     * Replaces Flask's authenticate_user().
     */
    public function authenticateUser(string $email, string $password): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM user WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) return [null, null];

        // Verify password
        if (password_verify($password, $user['Password'])) {
            $token = $this->generateToken($user['UserID'], 'user');
            return [$token, $user];
        }

        return [null, null];
    }

    /**
     * Generates a JWT token for a user/admin.
     */
    private function generateToken(int $userId, string $role): string {
        $secretKey = $_ENV['SECRET_KEY'] ?? 'fallback_secret_key';
        $payload = [
            'iat' => time(),
            'exp' => time() + (24 * 60 * 60), // 1 day expire
            'sub' => (string) $userId,
            'user_id' => $userId, // for backward compatibility with Flask
            'role' => $role
        ];
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
    }

    public function getUserById(int $userId): ?array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM user WHERE UserID = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateUser(int $userId, array $data): bool {
        $db = Database::get();
        
        $fields = [];
        $params = [];
        
        if (isset($data['Name'])) { $fields[] = "Name = ?"; $params[] = $data['Name']; }
        if (isset($data['Address'])) { $fields[] = "Address = ?"; $params[] = $data['Address']; }
        if (isset($data['Phone'])) { $fields[] = "Phone = ?"; $params[] = $data['Phone']; }
        if (isset($data['Password'])) { $fields[] = "Password = ?"; $params[] = password_hash($data['Password'], PASSWORD_DEFAULT); }

        if (empty($fields)) return true;

        $params[] = $userId;
        $sql = "UPDATE user SET " . implode(', ', $fields) . " WHERE UserID = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
