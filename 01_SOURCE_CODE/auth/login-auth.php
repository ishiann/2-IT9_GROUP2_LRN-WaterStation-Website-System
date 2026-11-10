<?php
// auth/login-auth.php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $identifier = trim($_POST['customerUser'] ?? '');
    $password   = $_POST['customerPassword'] ?? '';

    if (empty($identifier) || empty($password)) {
        die("Please fill in all fields.");
    }

    // Find user by username or email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
    $stmt->execute([$identifier, $identifier]);
    $user = $stmt->fetch();

    // Verify password hash
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['username']  = $user['username'];

        header("Location: ../pages/dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid username or password!'); window.location.href='../login.html';</script>";
        exit();
    }
}
?>