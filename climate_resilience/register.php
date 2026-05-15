<?php
require 'vendor/autoload.php';

// ===== CORS =====
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ===== 连接 MongoDB =====
    $client = new MongoDB\Client("mongodb+srv://zihengchen1_db_user:Password123!@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority");
    $collection = $client->climate_db->users;

    // ===== 获取 JSON 数据 =====
    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid JSON"
        ]);
        exit();
    }

    // ===== 必填验证 =====
    if (empty($data["username"]) || empty($data["password"])) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "username & password required"
        ]);
        exit();
    }

    // ===== 检查是否已存在 =====
    $exists = $collection->findOne([
        "username" => $data["username"]
    ]);

    if ($exists) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Username already exists"
        ]);
        exit();
    }

    // ===== 密码加密 =====
    $hashedPassword = password_hash($data["password"], PASSWORD_DEFAULT);

    // ===== 插入数据库 =====
    $result = $collection->insertOne([
        "username" => $data["username"],
        "email" => $data["email"] ?? "",
        "password" => $hashedPassword,
        "role" => $data["role"] ?? "user",
        "created_at" => new MongoDB\BSON\UTCDateTime()
    ]);

    // ===== 返回结果 =====
    echo json_encode([
        "success" => true,
        "id" => (string)$result->getInsertedId()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}