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

$contentType = $_SERVER["CONTENT_TYPE"] ?? "";
$isMultipart = stripos($contentType, "multipart/form-data") !== false;
$data = $isMultipart ? $_POST : json_decode(file_get_contents("php://input"), true);

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
$attachment = $_FILES["attachment"] ?? null;

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

if ($attachment && ($attachment["error"] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    if ($attachment["error"] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Attachment upload failed"
        ]);
        exit();
    }

    $maxAttachmentSize = 5 * 1024 * 1024;
    if ($attachment["size"] > $maxAttachmentSize) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Attachment must be 5MB or smaller"
        ]);
        exit();
    }

    $allowedExtensions = ["pdf", "doc", "docx", "jpg", "jpeg", "png"];
    $originalFileName = basename($attachment["name"]);
    $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Attachment file type is not allowed"
        ]);
        exit();
    }

    $fileContent = file_get_contents($attachment["tmp_name"]);
    if ($fileContent === false) {
        http_response_code(400);
        echo json_encode([
            "success" => false,
            "error" => "Attachment could not be read"
        ]);
        exit();
    }

    $payload["attachments"] = [
        [
            "filename" => $originalFileName,
            "content" => base64_encode($fileContent)
        ]
    ];
}

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
