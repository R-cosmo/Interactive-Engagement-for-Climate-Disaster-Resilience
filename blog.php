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
    $collection = $client->climate_db->blogs;

    if ($_SERVER["REQUEST_METHOD"] === "GET") {
        $cursor = $collection->find([], [
            "sort" => ["created_at" => -1]
        ]);

        $blogs = [];

        foreach ($cursor as $blog) {
            $createdAt = $blog["created_at"] ?? null;

            $blogs[] = [
                "id" => (string) $blog["_id"],
                "title" => $blog["title"] ?? "",
                "summary" => $blog["summary"] ?? "",
                "content" => $blog["content"] ?? "",
                "author" => $blog["author"] ?? "",
                "category" => $blog["category"] ?? "",
                "tags" => $blog["tags"] ?? [],
                "status" => $blog["status"] ?? "published",
                "created_at" => $createdAt instanceof MongoDB\BSON\UTCDateTime
                    ? $createdAt->toDateTime()->format(DateTimeInterface::ATOM)
                    : ""
            ];
        }

        echo json_encode([
            "success" => true,
            "blogs" => $blogs
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
        empty($data["summary"]) ||
        empty($data["content"]) ||
        empty($data["author"]) ||
        empty($data["category"])
    ) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Missing required fields"
        ]);
        exit();
    }

    $tags = [];
    if (!empty($data["tags"])) {
        $tags = array_values(array_filter(array_map("trim", explode(",", $data["tags"]))));
    }

    $result = $collection->insertOne([
        "title" => $data["title"],
        "summary" => $data["summary"],
        "content" => $data["content"],
        "author" => $data["author"],
        "category" => $data["category"],
        "tags" => $tags,
        "status" => $data["status"] ?? "published",
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
