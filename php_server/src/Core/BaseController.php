<?php

namespace Core;

class BaseController {
    /**
     * Get JSON input from request body.
     */
    protected function getBodyData(): array {
        $json = file_get_contents('php://input');
        return json_decode($json, true) ?? [];
    }

    /**
     * Send a JSON response.
     */
    protected function json($data, int $status = 200): void {
        Response::json($data, $status);
    }

    /**
     * Send an error response.
     */
    protected function error(string $message, int $status = 400): void {
        Response::error($message, $status);
    }
}
