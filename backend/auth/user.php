<?php
require "../config/db.php";
session_start();

header('Content-Type: application/json');

// GET - current user info
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT id, name, email, phone, address, role FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'logged_in' => true,
            'user' => $user
        ]);
    } else {
        echo json_encode([
            'logged_in' => false,
            'user' => null
        ]);
    }
}

else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
?>
