<?php
// auth/check_auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.html?error=unauthorized");
    exit();
}
?>