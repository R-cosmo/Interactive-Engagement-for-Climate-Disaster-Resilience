<?php
require 'vendor/autoload.php';

// ===== CORS 设置 =====
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

// 处理浏览器预检请求（必须有）
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

try {
    // ===== 连接 MongoDB =====
    $client = new MongoDB\Client("mongodb+srv://zihengchen1_db_user:Password123!@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority");
    $collection = $client->climate_db->contributions;

    // ===== 获取 JSON 数据 =====
    $data = json_decode(file_get_contents("php://input"), true);

    // ===== 基本检查 =====
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid JSON"
        ]);
        exit();
    }

    // ===== 必填字段验证 =====
    if (
        empty($data["name"]) ||
        empty($data["email"]) ||
        empty($data["contribution_type"]) ||
        empty($data["region"]) ||
        empty($data["title"]) ||
        empty($data["description"])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Missing required fields"
        ]);
        exit();
    }

    // ===== 插入数据库 =====
    $result = $collection->insertOne([
        "name" => $data["name"],
        "email" => $data["email"],
        "organisation" => $data["organisation"] ?? "",
        "contribution_type" => $data["contribution_type"],
        "region" => $data["region"],
        "title" => $data["title"],
        "description" => $data["description"],
        "status" => "pending",
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