<?php
require "../config/db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$stmt = $conn->prepare(
    "INSERT INTO order_items (order_id,menu_item_id,quantity,price)
     VALUES (?,?,?,?)"
);
$stmt->bind_param(
    "iiid",
    $data['order_id'],
    $data['menu_item_id'],
    $data['quantity'],
    $data['price']
);
$stmt->execute();

echo json_encode(["status"=>"item_added"]);
