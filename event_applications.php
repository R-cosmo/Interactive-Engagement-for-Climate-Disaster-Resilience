<?php
require __DIR__ . "/vendor/autoload.php";

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

try {
    $mongoUri = getenv("MONGODB_URI") ?: "mongodb+srv://zihengchen1_db_user:Password123%21@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority&tls=true";
    $client = new MongoDB\Client($mongoUri);
    $collection = $client->climate_db->event_applications;

    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $cursor = $collection->find([], [
            "sort" => ["created_at" => -1]
        ]);

        $applications = [];

        foreach ($cursor as $application) {
            $createdAt = $application["created_at"] ?? null;

            $applications[] = [
                "id" => (string) $application["_id"],
                "event_id" => $application["event_id"] ?? "",
                "event_title" => $application["event_title"] ?? "",
                "name" => $application["name"] ?? "",
                "email" => $application["email"] ?? "",
                "message" => $application["message"] ?? "",
                "status" => $application["status"] ?? "pending",
                "created_at" => $createdAt instanceof MongoDB\BSON\UTCDateTime
                    ? $createdAt->toDateTime()->format(DateTimeInterface::ATOM)
                    : ""
            ];
        }

        echo json_encode([
            "success" => true,
            "applications" => $applications
        ]);
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

    $eventId = trim($data["event_id"] ?? "");
    $eventTitle = trim($data["event_title"] ?? "");
    $name = trim($data["name"] ?? "");
    $email = trim($data["email"] ?? "");
    $message = trim($data["message"] ?? "");

    if ($eventId === "" || $eventTitle === "" || $name === "" || $email === "") {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Missing required fields"
        ]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Invalid email address"
        ]);
        exit();
    }

    $result = $collection->insertOne([
        "event_id" => $eventId,
        "event_title" => $eventTitle,
        "name" => $name,
        "email" => $email,
        "message" => $message,
        "status" => "pending",
        "created_at" => new MongoDB\BSON\UTCDateTime()
    ]);

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "id" => (string) $result->getInsertedId()
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
