<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
    http_response_code(200);
    exit();
}

try {
    require __DIR__ . "/vendor/autoload.php";

    $mongoUri = getenv("MONGODB_URI") ?: "mongodb+srv://zihengchen1_db_user:Password123%21@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority&tls=true";
    $client = new MongoDB\Client($mongoUri);
    $collection = $client->climate_db->events;

    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $cursor = $collection->find([], [
            "sort" => ["event_date" => 1]
        ]);

        $events = [];

        foreach ($cursor as $event) {
            $eventDate = $event["event_date"] ?? null;
            $createdAt = $event["created_at"] ?? null;

            $events[] = [
                "id" => (string) $event["_id"],
                "title" => $event["title"] ?? "",
                "description" => $event["description"] ?? "",
                "event_type" => $event["event_type"] ?? "",
                "location" => $event["location"] ?? "",
                "start_time" => $event["start_time"] ?? "",
                "registration_link" => $event["registration_link"] ?? "",
                "status" => $event["status"] ?? "",
                "event_date" => $eventDate instanceof MongoDB\BSON\UTCDateTime
                    ? $eventDate->toDateTime()->format("Y-m-d")
                    : "",
                "created_at" => $createdAt instanceof MongoDB\BSON\UTCDateTime
                    ? $createdAt->toDateTime()->format(DateTimeInterface::ATOM)
                    : ""
            ];
        }

        echo json_encode([
            "success" => true,
            "events" => $events
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

    if (
        empty($data["title"]) ||
        empty($data["description"]) ||
        empty($data["event_type"]) ||
        empty($data["location"]) ||
        empty($data["event_date"])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Missing required fields"
        ]);
        exit();
    }

    $eventDate = new DateTime($data["event_date"]);

    $result = $collection->insertOne([
        "title" => $data["title"],
        "description" => $data["description"],
        "event_type" => $data["event_type"],
        "location" => $data["location"],
        "start_time" => $data["start_time"] ?? "",
        "registration_link" => $data["registration_link"] ?? "",
        "status" => $data["status"] ?? "upcoming",
        "event_date" => new MongoDB\BSON\UTCDateTime($eventDate->getTimestamp() * 1000),
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
