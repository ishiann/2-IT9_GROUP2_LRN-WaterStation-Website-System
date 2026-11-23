<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php?error=required");
    exit();
}

function requireAdmin() {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: ../pages/customer-portal.php");
        exit();
    }
}

function requireCustomer() {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: ../pages/dashboard.php");
        exit();
    }
}