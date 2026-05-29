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

function sendSubscriptionEmail($email) {
    $resendApiKey = getenv("RESEND_API_KEY") ?: "re_iCLFNg2H_5rDLjcvfMUhHepZAxaJhbq9A";
    $mailFrom = getenv("MAIL_FROM") ?: "Climate Engage AU <onboarding@resend.dev>";
    $unsubscribeUrl = "https://interactive-engagement-for-climate.onrender.com/unsubscribe.php?email=" . rawurlencode($email);

    if ($resendApiKey === "") {
        return false;
    }

    $payload = [
        "from" => $mailFrom,
        "to" => [$email],
        "subject" => "Thanks for subscribing",
        "text" => "Thanks for subscribing\n\nUnsubscribe: " . $unsubscribeUrl,
        "html" => '<p>Thanks for subscribing</p><p><a href="' . htmlspecialchars($unsubscribeUrl, ENT_QUOTES, "UTF-8") . '" style="display:inline-block;background:#2f6f68;color:#ffffff;padding:12px 18px;border-radius:6px;text-decoration:none;font-weight:bold;">Unsubscribe</a></p>'
    ];

    $context = stream_context_create([
        "http" => [
            "method" => "POST",
            "header" => implode("\r\n", [
                "Authorization: Bearer " . $resendApiKey,
                "Content-Type: application/json"
            ]),
            "content" => json_encode($payload),
            "ignore_errors" => true,
            "timeout" => 20
        ]
    ]);

    $response = file_get_contents("https://api.resend.com/emails", false, $context);
    $statusLine = $http_response_header[0] ?? "";
    $statusCode = preg_match('/\s(\d{3})\s/', $statusLine, $matches) ? (int) $matches[1] : 0;

    return $response !== false && $statusCode >= 200 && $statusCode < 300;
}

try {
    $mongoUri = getenv("MONGODB_URI") ?: "mongodb+srv://zihengchen1_db_user:Password123%21@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority&tls=true";
    $client = new MongoDB\Client($mongoUri);
    $collection = $client->climate_db->subscribers;

    $existing = $collection->findOne([
        "email" => $email
    ]);

    if ($existing) {
        sendSubscriptionEmail($email);

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

    sendSubscriptionEmail($email);

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
