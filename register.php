<?php
require "../config/db.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data['name']);
$email = filter_var($data['email'], FILTER_VALIDATE_EMAIL);
$password = $data['password'];
$role = $data['role'];

if (!$email || strlen($password) < 6) {
    echo json_encode(["status"=>"error","message"=>"Invalid input"]);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)"
);
$stmt->bind_param("ssss",$name,$email,$hash,$role);

if ($stmt->execute()) {
    echo json_encode(["status"=>"success"]);
} else {
    echo json_encode(["status"=>"error","message"=>"User exists"]);
}
