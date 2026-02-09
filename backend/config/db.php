<?php
header("Content-Type: application/json");

$conn = new mysqli("localhost", "root", "", "foodhub_db");

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(["error" => "DB connection failed"]);
    exit;
}
?>