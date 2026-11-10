<?php
// pages/dashboard.php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// Fetch summary metrics
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products WHERE status='Active'")->fetchColumn();
$totalOrders   = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='Pending'")->fetchColumn();
$totalSales    = $pdo->query("SELECT IFNULL(SUM(total_amount), 0) FROM orders WHERE status='Delivered'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Station Dashboard | LRN Purified Water</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body style="background:#f1f5f9;">

    <nav class="navbar navbar-expand-lg navbar-dark" style="background:#023047;">
        <div class="container">
            <a class="navbar-brand fw-bold" href="#">💧 LRN WATER POS & MANAGEMENT</a>
            <div class="d-flex align-items-center text-white gap-3">
                <span>Welcome, <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> (<?= ucfirst($_SESSION['role']) ?>)</span>
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">Log Out</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Navigation Bar for Internal Modules -->
        <div class="d-flex gap-2 mb-4">
            <a href="dashboard.php" class="btn btn-primary active">Dashboard</a>
            <a href="products.php" class="btn btn-outline-primary">Manage Products (CRUD)</a>
            <a href="transactions.php" class="btn btn-outline-primary">Process Orders (Transactions)</a>
        </div>

        <!-- Metric Counter Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="card p-3 shadow-sm border-start border-4 border-primary">
                    <h6 class="text-muted">Active Products</h6>
                    <h2 class="fw-bold mb-0"><?= $totalProducts ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm border-start border-4 border-info">
                    <h6 class="text-muted">Total Orders</h6>
                    <h2 class="fw-bold mb-0"><?= $totalOrders ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm border-start border-4 border-warning">
                    <h6 class="text-muted">Pending Deliveries</h6>
                    <h2 class="fw-bold mb-0"><?= $pendingOrders ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card p-3 shadow-sm border-start border-4 border-success">
                    <h6 class="text-muted">Completed Sales</h6>
                    <h2 class="fw-bold mb-0">₱<?= number_format($totalSales, 2) ?></h2>
                </div>
            </div>

            <div class="card p-4 shadow-sm text-center mb-4 border-0" style="background: #e0f2fe;">
                <h4 class="fw-bold" style="color: #023047;">Ready to process customer orders?</h4>
                <p class="text-muted">Launch the cashier point of sale terminal to ring up refills, compute change, and issue receipts.</p>
                <div>
                    <a href="pos.php" class="btn btn-primary btn-lg px-4 fw-bold" style="background:#0284c7; border-color:#0284c7;">
                        🛒 Launch Cashier POS Terminal
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>