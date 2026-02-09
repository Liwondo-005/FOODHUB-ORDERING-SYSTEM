<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

$restaurant_id = isset($_GET['restaurant_id']) ? (int)$_GET['restaurant_id'] : 0;

if (!$restaurant_id) {
    http_response_code(400);
    echo json_encode(['error' => 'restaurant_id required']);
    exit;
}

// Get menu items by restaurant
$query = "SELECT m.id, m.name, m.description, m.price, m.image_url, m.is_available, c.name as category 
          FROM menu_items m 
          LEFT JOIN categories c ON m.category_id = c.id 
          WHERE m.restaurant_id = ? AND m.is_available = 1 
          ORDER BY c.name, m.name";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $restaurant_id);
$stmt->execute();
$result = $stmt->get_result();

echo json_encode($result->fetch_all(MYSQLI_ASSOC));
?>
