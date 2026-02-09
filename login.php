<?php
require "../config/db.php";
session_start();

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'];
$password = $data['password'];

$stmt = $conn->prepare(
    "SELECT id,password,role FROM users WHERE email=? AND status='active'"
);
$stmt->bind_param("s",$email);
$stmt->execute();
$res = $stmt->get_result();

if ($user = $res->fetch_assoc()) {
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];

        echo json_encode([
            "status"=>"success",
            "role"=>$user['role']
        ]);
    } else {
        echo json_encode(["status"=>"error","message"=>"Wrong password"]);
    }
} else {
    echo json_encode(["status"=>"error","message"=>"User not found"]);
}
