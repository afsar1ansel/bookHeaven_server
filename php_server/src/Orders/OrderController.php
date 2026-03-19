<?php

namespace Orders;

use Core\BaseController;
use Middleware\Auth;
use Exception;

class OrderController extends BaseController {
    private OrderService $orderService;

    public function __construct() {
        $this->orderService = new OrderService();
    }

    public function viewCart(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $cart = $this->orderService->getUserCart($userId);
        $this->json($cart);
    }

    public function addToCart(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $data = $this->getBodyData();
        
        if (empty($data['book_id'])) {
            $this->error("book_id is required", 400);
        }

        $success = $this->orderService->addToCart($userId, $data['book_id'], $data['quantity'] ?? 1);
        if (!$success) {
            $this->error("Failed to add item to cart", 500);
        }

        $this->json(["message" => "Item added to cart"]);
    }

    public function updateCart(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $data = $this->getBodyData();

        if (!isset($data['book_id']) || !isset($data['quantity'])) {
            $this->error("book_id and quantity are required", 400);
        }

        $success = $this->orderService->updateCartItem($userId, $data['book_id'], $data['quantity']);
        if (!$success) {
            $this->error("Failed to update cart", 500);
        }

        $this->json(["message" => "Cart updated successfully"]);
    }

    public function removeCartItem(int $book_id): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        
        $success = $this->orderService->updateCartItem($userId, $book_id, 0); // 0 quantity deletes
        if (!$success) {
            $this->error("Failed to remove item", 500);
        }

        $this->json(["message" => "Item removed from cart"]);
    }

    public function clearCart(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        
        $success = $this->orderService->clearCart($userId);
        if (!$success) {
            $this->error("Failed to clear cart", 500);
        }

        $this->json(["message" => "Cart cleared successfully"]);
    }

    public function checkout(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $data = $this->getBodyData();

        if (empty($data['shipping_address'])) {
            $this->error("shipping_address is required", 400);
        }

        try {
            $result = $this->orderService->checkout($userId, $data['shipping_address'], $data['payment'] ?? []);
            $status = $result['status'] ?? 201;
            unset($result['status']);
            $this->json($result, $status);
        } catch (Exception $e) {
            $this->error($e->getMessage(), 400);
        }
    }

    public function history(): void {
        $payload = Auth::requireToken();
        $userId = (int) ($payload['sub'] ?? $payload['user_id']);
        $orders = $this->orderService->getUserOrderHistory($userId);
        $this->json($orders);
    }

    public function adminGetAll(): void {
        Auth::requireAdmin();
        $orders = $this->orderService->getAllOrders();
        $this->json($orders);
    }

    public function adminDispatch(int $order_id): void {
        Auth::requireAdmin();
        $success = $this->orderService->updateOrderStatus($order_id, 'Shipped');
        if (!$success) {
            $this->error("Order not found", 404);
        }
        $this->json(["message" => "Order dispatched successfully"]);
    }
}
