<?php
session_start();
require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Connect to MongoDB
    $client = new MongoDB\Client("mongodb+srv://zihengchen1_db_user:Password123!@cluster0.exqvqaj.mongodb.net/?retryWrites=true&w=majority");
    $collection = $client->climate_db->users;

    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Find user
    $user = $collection->findOne(["username" => $username]);

    if (!$user) {
        $error = "User not found";
    } else if (!password_verify($password, $user['password'])) {
        $error = "Incorrect password";
    } else {
        // SUCCESS — set session
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        // Redirect based on role
        if ($user['role'] === 'admin') {
            header("Location: admin.php");
        } else {
            header("Location: home.php");
        }
        exit;
    }
}
?>

