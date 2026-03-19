<?php

namespace User;

use Core\BaseController;
use Middleware\Auth;
use Exception;

class UserController extends BaseController {
    private UserService $userService;

    public function __construct() {
        $this->userService = new UserService();
    }

    public function register(): void {
        $data = $this->getBodyData();
        if (empty($data['Email']) || empty($data['Password']) || empty($data['Name'])) {
            $this->error("Missing required fields", 400);
        }

        [$userId, $error] = $this->userService->registerUser($data);
        if ($error) {
            $this->error($error, 400);
        }

        $this->json(["message" => "User registered successfully"], 201);
    }

    public function login(): void {
        $data = $this->getBodyData();
        if (empty($data['Email']) || empty($data['Password'])) {
            $this->error("Email and Password are required", 400);
        }

        [$token, $user] = $this->userService->authenticateUser($data['Email'], $data['Password']);
        if (!$token) {
            $this->error("Invalid credentials", 401);
        }

        $this->json([
            "token" => $token,
            "Name"  => $user['Name'],
            "Email" => $user['Email'],
            "role"  => "user"
        ]);
    }

    public function profile(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $role = $payload['role'] ?? 'user';

        if ($role === 'admin') {
            // Placeholder: Admins might have a different service, but let's handle if requested
            $this->error("Admin profile not implemented in UserController", 400);
        }

        $user = $this->userService->getUserById($userId);
        if (!$user) {
            $this->error("User not found", 404);
        }

        $this->json([
            "UserID"  => $user['UserID'],
            "Email"   => $user['Email'],
            "Name"    => $user['Name'],
            "Address" => $user['Address'],
            "Phone"   => $user['Phone'],
            "role"    => $role
        ]);
    }

    public function updateProfile(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        
        $data = $this->getBodyData();
        $success = $this->userService->updateUser($userId, $data);
        
        if (!$success) {
            $this->error("Profile update failed", 500);
        }

        $this->json(["message" => "Profile updated successfully"]);
    }
}
