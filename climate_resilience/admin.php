<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.html");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: home.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<nav>
    <a href="admin.php">Admin Dashboard</a> |
    <a href="home.php">Home</a> |
    <strong>Admin: <?php echo $_SESSION['username']; ?></strong> |
    <a href="logout.php">Logout</a>
</nav>

<h1>Admin Panel</h1>
<p>Only admins can see this page.</p>

</body>
</html>
