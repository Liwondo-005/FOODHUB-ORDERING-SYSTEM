<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

// Get all restaurants or search by name/area
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $area = isset($_GET['area']) ? trim($_GET['area']) : '';
    
    $query = "SELECT id, name, description, cuisine, area, rating, delivery_time, phone, address, image_url FROM restaurants WHERE status='active'";
    
    $params = [];
    $types = '';
    
    if ($search) {
        $query .= " AND (name LIKE ? OR cuisine LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= 'ss';
    }
    
    if ($area) {
        $query .= " AND area LIKE ?";
        $params[] = "%$area%";
        $types .= 's';
    }
    
    $query .= " ORDER BY rating DESC LIMIT 50";
    
    $stmt = $conn->prepare($query);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
