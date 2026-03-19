<?php

namespace Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Core\Response;
use Exception;

class Auth {
    /**
     * Extracts and verifies the JWT token from the Authorization header.
     * Replaces Flask's @token_required.
     */
    public static function requireToken(): array {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            Response::error('Token missing or invalid format. Use Bearer <token>', 401);
        }

        $token = substr($authHeader, 7);
        $secretKey = $_ENV['SECRET_KEY'] ?? 'fallback_secret_key';

        try {
            $payload = JWT::decode($token, new Key($secretKey, 'HS256'));
            return (array) $payload;
        } catch (Exception $e) {
            Response::error('Invalid or expired token: ' . $e->getMessage(), 401);
        }
    }

    /**
     * Ensures the token payload has as 'role' of 'admin'.
     * Replaces Flask's @admin_required.
     */
    public static function requireAdmin(): array {
        $payload = self::requireToken();
        
        if (($payload['role'] ?? '') !== 'admin') {
            Response::error('Admin access required', 403);
        }

        return $payload;
    }
}
