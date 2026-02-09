<?php
require "../config/db.php";
session_start();

if ($_SESSION['role'] !== 'owner') {
    http_response_code(403);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare(
    "INSERT INTO menu_items (restaurant_id,name,price)
     VALUES (?,?,?)"
);
$stmt->bind_param(
    "isd",
    $data['restaurant_id'],
    $data['name'],
    $data['price']
);

$stmt->execute();
echo json_encode(["status"=>"menu_added"]);
