<?php

namespace Admin;

use Core\BaseController;
use Middleware\Auth;
use Exception;

class AdminController extends BaseController {
    private AdminService $adminService;

    public function __construct() {
        $this->adminService = new AdminService();
    }

    public function login(): void {
        $data = $this->getBodyData();
        if (empty($data['Email']) || empty($data['Password'])) {
            $this->error("Email and Password are required", 400);
        }

        [$token, $admin] = $this->adminService->authenticateAdmin($data['Email'], $data['Password']);
        if (!$token) {
            $this->error("Invalid admin credentials", 401);
        }

        $this->json([
            "token"    => $token,
            "Username" => $admin['Username'],
            "Email"    => $admin['Email'],
            "role"     => "admin"
        ]);
    }

    public function list(): void {
        Auth::requireAdmin();
        $admins = $this->adminService->getAllAdmins();
        $this->json($admins);
    }

    public function add(): void {
        Auth::requireAdmin();
        $data = $this->getBodyData();
        if (empty($data['Username']) || empty($data['Email']) || empty($data['Password'])) {
            $this->error("Missing required fields", 400);
        }

        [$adminId, $error] = $this->adminService->createAdmin($data);
        if ($error) {
            $this->error($error, 400);
        }

        $this->json(["message" => "Admin created", "AdminID" => $adminId], 201);
    }

    public function update(int $adminId): void {
        Auth::requireAdmin();
        $data = $this->getBodyData();
        $success = $this->adminService->updateAdmin($adminId, $data);
        
        if (!$success) {
            $this->error("Admin update failed", 404);
        }

        $this->json(["message" => "Admin updated successfully"]);
    }

    public function delete(int $adminId): void {
        Auth::requireAdmin();
        $success = $this->adminService->deleteAdmin($adminId);
        
        if (!$success) {
            $this->error("Admin not found", 404);
        }

        $this->json(["message" => "Admin deleted successfully"]);
    }
}
