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

// GET cart
if ($request_method === 'GET') {
    $stmt = $conn->prepare("SELECT c.id, c.restaurant_id, c.subtotal, 
                           GROUP_CONCAT(JSON_OBJECT('id', ci.id, 'menu_item_id', ci.menu_item_id, 
                           'name', m.name, 'quantity', ci.quantity, 'price', ci.price) SEPARATOR ',') as items
                           FROM carts c 
                           LEFT JOIN cart_items ci ON c.id = ci.cart_id 
                           LEFT JOIN menu_items m ON ci.menu_item_id = m.id 
                           WHERE c.user_id = ? 
                           GROUP BY c.id");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $cart = $result->fetch_assoc();
    
    if ($cart && $cart['items']) {
        $cart['items'] = json_decode('[' . $cart['items'] . ']', true);
    } else {
        $cart = null;
    }
    
    echo json_encode($cart);
}

// POST - add to cart
elseif ($request_method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['restaurant_id'], $data['menu_item_id'], $data['quantity'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $restaurant_id = (int)$data['restaurant_id'];
    $menu_item_id = (int)$data['menu_item_id'];
    $quantity = (int)$data['quantity'];
    
    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid quantity']);
        exit;
    }
    
    // Get menu item price
    $stmt = $conn->prepare("SELECT price FROM menu_items WHERE id = ? AND restaurant_id = ?");
    $stmt->bind_param('ii', $menu_item_id, $restaurant_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();
    
    if (!$item) {
        http_response_code(404);
        echo json_encode(['error' => 'Menu item not found']);
        exit;
    }
    
    $price = $item['price'];
    
    // Get or create cart
    $stmt = $conn->prepare("SELECT id FROM carts WHERE user_id = ? AND restaurant_id = ?");
    $stmt->bind_param('ii', $user_id, $restaurant_id);
    $stmt->execute();
    $cart = $stmt->get_result()->fetch_assoc();
    
    if (!$cart) {
        $stmt = $conn->prepare("INSERT INTO carts (user_id, restaurant_id, subtotal) VALUES (?, ?, 0)");
        $stmt->bind_param('ii', $user_id, $restaurant_id);
        $stmt->execute();
        $cart_id = $conn->insert_id;
    } else {
        $cart_id = $cart['id'];
    }
    
    // Add/update cart item
    $stmt = $conn->prepare("INSERT INTO cart_items (cart_id, menu_item_id, quantity, price) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()");
    $stmt->bind_param('iiidi', $cart_id, $menu_item_id, $quantity, $price, $quantity);
    $stmt->execute();
    
    // Update cart subtotal
    $stmt = $conn->prepare("UPDATE carts SET subtotal = (SELECT SUM(quantity * price) FROM cart_items WHERE cart_id = ?) WHERE id = ?");
    $stmt->bind_param('ii', $cart_id, $cart_id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Item added to cart']);
}

// PUT - update cart item quantity
elseif ($request_method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data['cart_item_id'], $data['quantity'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing required fields']);
        exit;
    }
    
    $cart_item_id = (int)$data['cart_item_id'];
    $quantity = (int)$data['quantity'];
    
    if ($quantity < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid quantity']);
        exit;
    }
    
    if ($quantity === 0) {
        // Delete item
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE id = ?");
        $stmt->bind_param('i', $cart_item_id);
        $stmt->execute();
    } else {
        // Update quantity
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param('ii', $quantity, $cart_item_id);
        $stmt->execute();
    }
    
    // Update cart subtotal
    $stmt = $conn->prepare("UPDATE carts SET subtotal = (SELECT SUM(quantity * price) FROM cart_items WHERE cart_id = ?) WHERE id = (SELECT cart_id FROM cart_items WHERE id = ?)");
    $stmt->bind_param('ii', $cart_item_id, $cart_item_id);
    $stmt->execute();
    
    echo json_encode(['status' => 'success', 'message' => 'Cart updated']);
}

// DELETE - clear cart
elseif ($request_method === 'DELETE') {
    $restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : null;
    
    if ($restaurant_id) {
        $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ? AND restaurant_id = ?");
        $stmt->bind_param('ii', $user_id, $restaurant_id);
    } else {
        $stmt = $conn->prepare("DELETE FROM carts WHERE user_id = ?");
        $stmt->bind_param('i', $user_id);
    }
    
    $stmt->execute();
    echo json_encode(['status' => 'success', 'message' => 'Cart cleared']);
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
