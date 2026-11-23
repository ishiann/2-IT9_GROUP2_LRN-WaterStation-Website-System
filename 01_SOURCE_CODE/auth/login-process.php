<?php
session_start();
require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}

$loginUser = trim($_POST['loginUser'] ?? '');
$loginPass = $_POST['loginPass'] ?? '';

if ($loginUser === '' || $loginPass === '') {
    header("Location: ../login.php?error=empty");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, full_name, username, password, role
                           FROM users
                           WHERE username = ? OR email = ?
                           LIMIT 1");
    $stmt->execute([$loginUser, $loginUser]);
    $user = $stmt->fetch();

    if ($user && password_verify($loginPass, $user['password'])) {
        session_regenerate_id(true);
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['username']  = $user['username'];
        $_SESSION['role']      = $user['role'] ?: 'customer';

        if ($_SESSION['role'] === 'admin') {
            header("Location: ../pages/dashboard.php");
        } else {
            header("Location: ../pages/customer-portal.php");
        }
        exit();
    }

    header("Location: ../login.php?error=invalid");
    exit();

} catch (PDOException $ex) {
    header("Location: ../login.php?error=server");
    exit();
}