<?php
// pages/update_order_status.php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_POST['order_id']);
    $status  = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);

    header("Location: transactions.php?msg=status_updated");
    exit();
}
?>