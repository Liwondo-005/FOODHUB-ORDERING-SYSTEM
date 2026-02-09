<?php
require "../config/db.php";
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit;
}

$res = $conn->query("SELECT id,name,email,role,status FROM users");
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
?>