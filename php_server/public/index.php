<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Core\Router;
use Core\Response;

// Load .env
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

// CORS Headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

use User\UserController;
use Admin\AdminController;
use Books\BookController;
use Orders\OrderController;

$router = new Router();

// --- USER ROUTES ---
$router->post('/api/user/register', [UserController::class, 'register']);
$router->post('/api/user/login', [UserController::class, 'login']);
$router->get('/api/user/profile', [UserController::class, 'profile']);
$router->put('/api/user/profile', [UserController::class, 'updateProfile']);

// --- ADMIN ROUTES ---
$router->post('/api/admin/login', [AdminController::class, 'login']);
$router->get('/api/admin/', [AdminController::class, 'list']);
$router->post('/api/admin/', [AdminController::class, 'add']);
$router->put('/api/admin/{admin_id}', [AdminController::class, 'update']);
$router->delete('/api/admin/{admin_id}', [AdminController::class, 'delete']);

// --- BOOKS ROUTES ---
$router->get('/api/books/', [BookController::class, 'list']);
$router->get('/api/books/home', [BookController::class, 'home']);
$router->get('/api/books/{book_id}', [BookController::class, 'get']);
$router->post('/api/books/', [BookController::class, 'add']);
$router->put('/api/books/{book_id}', [BookController::class, 'update']);
$router->delete('/api/books/{book_id}', [BookController::class, 'delete']);

// --- ORDERS & CART ROUTES ---
$router->get('/api/orders/cart', [OrderController::class, 'viewCart']);
$router->post('/api/orders/cart/add', [OrderController::class, 'addToCart']);
$router->put('/api/orders/cart/update', [OrderController::class, 'updateCart']);
$router->delete('/api/orders/cart/remove/{book_id}', [OrderController::class, 'removeCartItem']);
$router->delete('/api/orders/cart/clear', [OrderController::class, 'clearCart']);

$router->post('/api/orders/checkout', [OrderController::class, 'checkout']);
$router->get('/api/orders/history', [OrderController::class, 'history']);

$router->get('/api/orders/admin/all', [OrderController::class, 'adminGetAll']);
$router->post('/api/orders/admin/{order_id}/dispatch', [OrderController::class, 'adminDispatch']);

// --- GLOBAL ROUTES ---
$router->get('/health', function() {
    Response::json(['status' => 'healthy']);
});

$router->get('/', function() {
    Response::json([
        "message" => "Welcome to BookHeaven Modular PHP API",
        "version" => "1.0.0"
    ]);
});

// Dispatch
try {
    $router->dispatch();
} catch (\Exception $e) {
    Response::error("Internal Server Error: " . $e->getMessage(), 500);
}
