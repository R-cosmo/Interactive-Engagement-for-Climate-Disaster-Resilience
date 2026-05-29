<?php
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

$fullName = trim($data["fullName"] ?? "");
$email = trim($data["email"] ?? "");
$organisation = trim($data["organisation"] ?? "");
$subject = trim($data["subject"] ?? "");
$message = trim($data["message"] ?? "");

if ($fullName === "" || $email === "" || $subject === "" || $message === "") {
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

$resendApiKey = getenv("RESEND_API_KEY") ?: "re_iCLFNg2H_5rDLjcvfMUhHepZAxaJhbq9A";
$mailFrom = getenv("MAIL_FROM") ?: "Climate Engage AU <onboarding@resend.dev>";
$mailTo = getenv("MAIL_TO") ?: "maryscott10946@gmail.com";

if ($resendApiKey === "") {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Email API key is not configured"
    ]);
    exit();
}

$safeSubject = "Climate Engage AU enquiry: " . str_replace(["\r", "\n"], "", $subject);

$emailBody = implode("\n", [
    "A new enquiry has been submitted from the Climate Engage AU contact form.",
    "",
    "Name: " . $fullName,
    "Email: " . $email,
    "Organisation: " . ($organisation !== "" ? $organisation : "Not provided"),
    "Subject: " . $subject,
    "",
    "Message:",
    $message
]);

$payload = [
    "from" => $mailFrom,
    "to" => [$mailTo],
    "reply_to" => $email,
    "subject" => $safeSubject,
    "text" => $emailBody
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

if ($response === false || $statusCode < 200 || $statusCode >= 300) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Email could not be sent by HTTP email API",
        "debug" => $response ? json_decode($response, true) : $statusLine
    ]);
    exit();
}

echo json_encode([
    "success" => true
]);
