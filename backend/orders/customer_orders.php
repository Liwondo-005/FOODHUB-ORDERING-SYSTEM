<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$request_method = $_SERVER['REQUEST_METHOD'];

// POST - create order
if ($request_method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['restaurant_id'], $data['delivery_address'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $restaurant_id = (int)$data['restaurant_id'];
    $delivery_address = trim($data['delivery_address']);
    $delivery_phone = isset($data['delivery_phone']) ? trim($data['delivery_phone']) : '';
    $notes = isset($data['notes']) ? trim($data['notes']) : '';
    $payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : 'cash';
    
    // Get cart for validation
    $stmt = $conn->prepare("SELECT id, subtotal FROM carts WHERE user_id = ? AND restaurant_id = ?");
    $stmt->bind_param('ii', $user_id, $restaurant_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart not found']);
        exit;
    }
    
    $cart_id = $cart['id'];
    $total_amount = $cart['subtotal'];
    
    // Get cart items
    $stmt = $conn->prepare("SELECT menu_item_id, quantity, price FROM cart_items WHERE cart_id = ?");
    $stmt->bind_param('i', $cart_id);
    $stmt->execute();
    $cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    if (empty($cart_items)) {
        http_response_code(400);
        echo json_encode(['error' => 'Cart is empty']);
        exit;
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Create order
        $stmt = $conn->prepare("INSERT INTO orders (user_id, restaurant_id, total_amount, delivery_address, delivery_phone, notes) 
                               VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('iidss', $user_id, $restaurant_id, $total_amount, $delivery_address, $delivery_phone, $notes);
        $stmt->execute();
        
        $order_id = $conn->insert_id;
        
        // Add order items
        $stmt = $conn->prepare("INSERT INTO order_items (order_id, menu_item_id, quantity, price) VALUES (?, ?, ?, ?)");
        
        foreach ($cart_items as $item) {
            $stmt->bind_param('iiii', $order_id, $item['menu_item_id'], $item['quantity'], $item['price']);
            $stmt->execute();
        }
        
        // Create payment record
        $stmt = $conn->prepare("INSERT INTO payments (order_id, user_id, method, amount, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->bind_param('iiss', $order_id, $user_id, $payment_method, $total_amount);
        $stmt->execute();
        
        // Clear cart
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE cart_id = ?");
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM carts WHERE id = ?");
        $stmt->bind_param('i', $cart_id);
        $stmt->execute();
        
        $conn->commit();
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Order placed successfully',
            'order_id' => $order_id,
            'total_amount' => $total_amount
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['error' => 'Failed to place order: ' . $e->getMessage()]);
    }
}

// GET - fetch orders
elseif ($request_method === 'GET') {
    $order_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    
    if ($order_id) {
        // Get specific order with items
        $stmt = $conn->prepare("SELECT o.id, o.user_id, o.restaurant_id, o.total_amount, o.status, o.payment_status, o.created_at,
                               GROUP_CONCAT(JSON_OBJECT('id', oi.id, 'menu_item_id', oi.menu_item_id, 'name', m.name, 'quantity', oi.quantity, 'price', oi.price) SEPARATOR ',') as items
                               FROM orders o 
                               LEFT JOIN order_items oi ON o.id = oi.order_id 
                               LEFT JOIN menu_items m ON oi.menu_item_id = m.id 
                               WHERE o.id = ? AND o.user_id = ?
                               GROUP BY o.id");
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        if ($result && $result['items']) {
            $result['items'] = json_decode('[' . $result['items'] . ']', true);
        }
        
        echo json_encode($result);
    } else {
        // Get all orders for user
        $status = isset($_GET['status']) ? trim($_GET['status']) : '';
        
        $query = "SELECT id, restaurant_id, total_amount, status, payment_status, created_at FROM orders WHERE user_id = ?";
        $types = 'i';
        $params = [$user_id];
        
        if ($status) {
            $query .= " AND status = ?";
            $types .= 's';
            $params[] = $status;
        }
        
        $query .= " ORDER BY created_at DESC LIMIT 50";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        
        echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
