<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized - Owner access required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];

// Get owner's restaurant
$stmt = $conn->prepare("SELECT id FROM restaurants WHERE owner_id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$restaurant = $stmt->get_result()->fetch_assoc();

if (!$restaurant) {
    http_response_code(403);
    echo json_encode(['error' => 'Restaurant not found']);
    exit;
}

$restaurant_id = $restaurant['id'];

// GET - fetch restaurant orders
if ($request_method === 'GET') {
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    
    $query = "SELECT o.id, o.user_id, u.name as customer_name, u.phone, o.total_amount, o.status, 
              o.delivery_address, o.created_at 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE o.restaurant_id = ?";
    $types = 'i';
    $params = [$restaurant_id];
    
    if ($status) {
        $query .= " AND o.status = ?";
        $types .= 's';
        $params[] = $status;
    }
    
    $query .= " ORDER BY o.created_at DESC LIMIT 100";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
}

// PUT - update order status
elseif ($request_method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['order_id'], $data['status'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $order_id = (int)$data['order_id'];
    $status = trim($data['status']);
    
    $valid_statuses = ['pending', 'preparing', 'ready', 'delivered', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid status']);
        exit;
    }
    
    // Verify order belongs to this restaurant
    $stmt = $conn->prepare("SELECT id FROM orders WHERE id = ? AND restaurant_id = ?");
    $stmt->bind_param('ii', $order_id, $restaurant_id);
    $stmt->execute();
    
    if (!$stmt->get_result()->fetch_assoc()) {
        http_response_code(403);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }
    
    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param('si', $status, $order_id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Order status updated']);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
