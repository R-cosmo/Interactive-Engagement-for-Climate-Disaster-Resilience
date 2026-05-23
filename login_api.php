<?php
require 'vendor/autoload.php';

// ===== CORS =====
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ===== Connect MongoDB =====
    $client = new MongoDB\Client("mongodb+srv://zihengchen1_db_user:Password123!@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority");
    $collection = $client->climate_db->users;

    // ===== Read JSON =====
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "Invalid JSON"]);
        exit();
    }

    if (empty($data["username"]) || empty($data["password"])) {
        http_response_code(400);
        echo json_encode(["success" => false, "error" => "username & password required"]);
        exit();
    }

    // ===== Find user =====
    $user = $collection->findOne(["username" => $data["username"]]);

    if (!$user) {
        echo json_encode(["success" => false, "error" => "User not found"]);
        exit();
    }

    // ===== Verify password =====
    if (!password_verify($data["password"], $user["password"])) {
        echo json_encode(["success" => false, "error" => "Incorrect password"]);
        exit();
    }

    // ===== Login success =====
    echo json_encode([
        "success" => true,
        "username" => $user["username"],
        "role" => $user["role"] ?? "user"
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}