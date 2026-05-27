<?php
require __DIR__ . "/vendor/autoload.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Invalid JSON"
    ]);
    exit();
}

$email = strtolower(trim($data["email"] ?? ""));

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Please enter a valid email address"
    ]);
    exit();
}

try {
    $mongoUri = getenv("MONGODB_URI") ?: "mongodb+srv://zihengchen1_db_user:Password123%21@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority&tls=true";
    $client = new MongoDB\Client($mongoUri);
    $collection = $client->climate_db->subscribers;

    $existing = $collection->findOne([
        "email" => $email
    ]);

    if ($existing) {
        echo json_encode([
            "success" => true,
            "already_subscribed" => true
        ]);
        exit();
    }

    $result = $collection->insertOne([
        "email" => $email,
        "status" => "subscribed",
        "source" => "contact_page_newsletter",
        "created_at" => new MongoDB\BSON\UTCDateTime()
    ]);

    echo json_encode([
        "success" => true,
        "id" => (string) $result->getInsertedId()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Subscription could not be saved"
    ]);
}
