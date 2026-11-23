<?php
require_once '../auth/check_auth.php';
requireAdmin();
require_once '../config/database.php';

$activeProducts = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Active'")->fetchColumn();
$lowStock       = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'Active' AND stock_qty <= 10")->fetchColumn();
$pendingOrders  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('Pending', 'Out for Delivery')")->fetchColumn();
$totalSales     = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE status = 'Completed'")->fetchColumn();

$recentOrders = $pdo->query(
    "SELECT order_code, customer_name, order_type, total_amount, status, created_at
     FROM orders ORDER BY created_at DESC LIMIT 5"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">

    <header class="portal-header">
        <div class="portal-header-inner">
            <div class="portal-brand">
                <img src="../images/lrn_logo.png" alt="LRN Logo" class="portal-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; ADMIN DASHBOARD</h2>
                    <p>Logged in as: <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'Admin', ENT_QUOTES, 'UTF-8') ?></strong> (Admin)</p>
                </div>
            </div>
            <nav class="portal-nav">
                <a href="dashboard.php" class="active">Dashboard</a>
                <a href="products.php">Products (CRUD)</a>
                <a href="orders.php">Orders</a>
                <a href="../auth/logout.php" class="btn-logout">Log Out</a>
            </nav>
        </div>
    </header>

    <main class="portal-container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Active Products</div>
                <div class="stat-value"><?= $activeProducts ?></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-label">Low Stock Items (&le; 10)</div>
                <div class="stat-value"><?= $lowStock ?></div>
            </div>
            <div class="stat-card navy">
                <div class="stat-label">Pending / Active Orders</div>
                <div class="stat-value"><?= $pendingOrders ?></div>
            </div>
            <div class="stat-card success">
                <div class="stat-label">Completed Sales</div>
                <div class="stat-value">&#8369;<?= number_format($totalSales, 2) ?></div>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <h3>Quick Navigation</h3>
            </div>
            <div class="action-row">
                <a href="pos.php" class="btn-primary">Open Cashier POS</a>
                <a href="products.php" class="btn-secondary">Manage Products &amp; Stock</a>
                <a href="orders.php" class="btn-secondary">View &amp; Update Orders</a>
            </div>
        </div>

        <div class="panel-card">
            <div class="panel-header">
                <h3>Recent Transactions</h3>
                <a href="orders.php" class="btn-primary">View All Orders &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Order Type</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="6">No orders recorded yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentOrders as $o): ?>
                        <?php $badge = 'badge-' . strtolower(str_replace(' ', '-', $o['status'])); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td><?= htmlspecialchars($o['order_type']) ?></td>
                            <td>&#8369;<?= number_format((float)$o['total_amount'], 2) ?></td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td><?= htmlspecialchars($o['created_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>