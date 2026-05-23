<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];

    // CHANGE THIS to your actual server path
    $apiUrl = "http://localhost:8080/login_api.php";

    $data = [
        "username" => $username,
        "password" => $password
    ];

    $options = [
        "http" => [
            "header"  => "Content-Type: application/json\r\n",
            "method"  => "POST",
            "content" => json_encode($data)
        ]
    ];

    $context = stream_context_create($options);
    $result = file_get_contents($apiUrl, false, $context);

    if ($result === FALSE) {
        echo "Login service error";
        exit;
    }

    $response = json_decode($result, true);

    if ($response["success"] === true) {
        $_SESSION["username"] = $username;

        if ($response["role"] === "admin") {
            header("Location: admin.php");
        } else {
            header("Location: home.php");
        }
        exit;
    } else {
        echo "Invalid username or password.";
    }
}
?>
