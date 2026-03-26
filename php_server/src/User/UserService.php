<?php

namespace User;

use Core\Database;
use Exception;

class UserService {
    /**
     * Registers a new user.
     * Now inserts into `users` table with role = 'user'.
     */
    public function registerUser(array $data): array {
        $db = Database::get();

        // Check if email already exists for a user (not admin)
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND role = 'user'");
        $stmt->execute([$data['Email']]);
        if ($stmt->fetch()) {
            return [null, "User with this email already exists"];
        }

        $hashedPassword = password_hash($data['Password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare(
            "INSERT INTO users (email, password, name, address, phone, role)
             VALUES (?, ?, ?, ?, ?, 'user')"
        );
        try {
            $stmt->execute([
                $data['Email'],
                $hashedPassword,
                $data['Name'],
                $data['Address'] ?? null,
                $data['Phone']   ?? null
            ]);
            $userId = $db->lastInsertId();
            return [$userId, null];
        } catch (Exception $e) {
            return [null, $e->getMessage()];
        }
    }

    /**
     * Authenticates a user and returns a JWT token.
     * Now queries `users` table with role = 'user'.
     */
    public function authenticateUser(string $email, string $password): array {
        $db = Database::get();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? AND role = 'user'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) return [null, null];

        if (password_verify($password, $user['password'])) {
            $token = $this->generateToken($user['id'], 'user');
            // Map to field names the controller expects: Name, Email
            $user['Name']  = $user['name'];
            $user['Email'] = $user['email'];
            return [$token, $user];
        }

        return [null, null];
    }

    private function generateToken(int $userId, string $role): string {
        $secretKey = $_ENV['SECRET_KEY'] ?? 'fallback_secret_key';
        $payload = [
            'iat'     => time(),
            'exp'     => time() + (24 * 60 * 60),
            'sub'     => (string) $userId,
            'user_id' => $userId, // backward compatibility
            'role'    => $role
        ];
        return \Firebase\JWT\JWT::encode($payload, $secretKey, 'HS256');
    }

    public function getUserById(int $userId): ?array {
        $db = Database::get();
        $stmt = $db->prepare(
            "SELECT id AS UserID, email AS Email, name AS Name, address AS Address, phone AS Phone
             FROM users WHERE id = ?"
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateUser(int $userId, array $data): bool {
        $db = Database::get();
        $fields = [];
        $params = [];

        if (isset($data['Name']))     { $fields[] = "name = ?";     $params[] = $data['Name']; }
        if (isset($data['Address']))  { $fields[] = "address = ?";  $params[] = $data['Address']; }
        if (isset($data['Phone']))    { $fields[] = "phone = ?";    $params[] = $data['Phone']; }
        if (isset($data['Password'])) { $fields[] = "password = ?"; $params[] = password_hash($data['Password'], PASSWORD_DEFAULT); }

        if (empty($fields)) return true;

        $params[] = $userId;
        $sql = "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    }
}
