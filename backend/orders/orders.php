<?php
require "../config/db.php";
session_start();

if ($_SESSION['role'] !== 'customer') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare(
    "INSERT INTO orders (user_id,restaurant_id,total_amount)
     VALUES (?,?,?)"
);
$stmt->bind_param(
    "iid",
    $_SESSION['user_id'],
    $data['restaurant_id'],
    $data['total']
);
$stmt->execute();

echo json_encode(["status"=>"order_placed"]);
?>