<?php
require __DIR__ . "/vendor/autoload.php";

$email = strtolower(trim($_GET["email"] ?? ""));

if ($email === "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo "<!doctype html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>Unsubscribe</title></head><body><h1>Invalid unsubscribe link</h1><p>Please check the unsubscribe link and try again.</p></body></html>";
    exit();
}

try {
    $mongoUri = getenv("MONGODB_URI") ?: "mongodb+srv://zihengchen1_db_user:Password123%21@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority&tls=true";
    $client = new MongoDB\Client($mongoUri);
    $collection = $client->climate_db->subscribers;

    $result = $collection->updateOne(
        ["email" => $email],
        [
            '$set' => [
                "status" => "unsubscribed",
                "unsubscribed_at" => new MongoDB\BSON\UTCDateTime()
            ]
        ]
    );

    if ($result->getMatchedCount() === 0) {
        http_response_code(404);
        echo "<!doctype html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>Unsubscribe</title></head><body><h1>Email not found</h1><p>This email address was not found in the subscriber list.</p></body></html>";
        exit();
    }

    echo "<!doctype html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>Unsubscribed</title></head><body><h1>You have been unsubscribed</h1><p>You will no longer receive newsletter emails from Climate Engage AU.</p></body></html>";
} catch (Throwable $e) {
    http_response_code(500);
    echo "<!doctype html><html lang=\"en\"><head><meta charset=\"UTF-8\"><title>Unsubscribe</title></head><body><h1>Unsubscribe failed</h1><p>Please try again later.</p></body></html>";
}
