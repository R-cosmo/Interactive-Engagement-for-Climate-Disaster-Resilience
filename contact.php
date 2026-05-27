<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

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

$smtpHost = "smtp.gmail.com";
$smtpPort = 587;
$smtpUsername = "maryscott10946@gmail.com";
$smtpPassword = "nxnfxytnnypsyegj";
$mailFrom = "maryscott10946@gmail.com";
$mailTo = "maryscott10946@gmail.com";

$safeSubject = "Climate Engage AU enquiry: " . str_replace(["\r", "\n"], "", $subject);
$safeFullName = str_replace(["\r", "\n"], "", $fullName);

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

try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $smtpHost;
    $mail->SMTPAuth = true;
    $mail->Username = $smtpUsername;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtpPort;
    $mail->CharSet = "UTF-8";

    $mail->setFrom($mailFrom, "Climate Engage AU");
    $mail->addAddress($mailTo);
    $mail->addReplyTo($email, $safeFullName);

    $mail->isHTML(false);
    $mail->Subject = $safeSubject;
    $mail->Body = $emailBody;

    $mail->send();
} catch (Exception $e) {
    $debugMessage = isset($mail) && $mail->ErrorInfo
        ? $mail->ErrorInfo
        : $e->getMessage();

    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Email could not be sent by SMTP",
        "debug" => $debugMessage
    ]);
    exit();
}

echo json_encode([
    "success" => true
]);
