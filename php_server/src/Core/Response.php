<?php

namespace Core;

class Response {
    /**
     * Send a JSON response and exit.
     * Replaces Flask's jsonify().
     */
    public static function json($data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Send a formatted JSON error response and exit.
     */
    public static function error(string $message, int $status = 400): void {
        self::json(['error' => $message], $status);
    }
}
