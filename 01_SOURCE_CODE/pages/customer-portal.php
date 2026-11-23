<?php
require_once '../auth/check_auth.php';
requireCustomer();
require_once '../config/database.php';

$userId = $_SESSION['user_id'];

// Escape output for HTML. Defined as a closure so it can't clash with a function name elsewhere.
$e = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

// Load the customer's profile, falling back to session data if the row is missing.
$customerStmt = $pdo->prepare("SELECT full_name, contact_number, address FROM users WHERE id = ?");
$customerStmt->execute([$userId]);
$customer = $customerStmt->fetch() ?: [
    'full_name'      => $_SESSION['full_name'],
    'contact_number' => '',
    'address'        => '',
];

// Load this customer's orders, newest first, with a summary of the items in each.
$ordersStmt = $pdo->prepare(
    "SELECT o.*,
            COALESCE(GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.item_name) SEPARATOR ', '), '—') AS items_summary
     FROM orders o
     LEFT JOIN order_items oi ON oi.order_id = o.id
     LEFT JOIN products p ON p.id = oi.product_id
     WHERE o.user_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$ordersStmt->execute([$userId]);
$orders = $ordersStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Portal | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">

    <header class="portal-header">
        <div class="portal-header-inner">
            <div class="portal-brand">
                <img src="../images/lrn_logo.png" alt="LRN Logo" class="portal-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; CUSTOMER PORTAL</h2>
                    <p>Welcome, <strong><?= $e($customer['full_name']) ?></strong></p>
                </div>
            </div>

            <nav class="portal-nav">
                <a href="customer-portal.php" class="active">My Orders</a>
                <a href="pos.php">Order Water</a>
                <a href="../index.php">Home</a>
                <a href="../auth/logout.php" class="btn-logout">Log Out</a>
            </nav>
        </div>
    </header>

    <main class="portal-container">

        <!-- Account summary -->
        <div class="panel-card">
            <div class="panel-header">
                <h3>Account Summary</h3>
                <a href="pos.php" class="btn-primary">+ Place New Water Order</a>
            </div>
            <p style="margin: 0 0 6px 0;">
                <strong>Contact:</strong>
                <?= $e($customer['contact_number'] ?: 'Not set') ?>
            </p>
            <p style="margin: 0;">
                <strong>Saved Delivery Address:</strong>
                <?= $e($customer['address'] ?: 'Not set') ?>
            </p>
        </div>

        <!-- Order history -->
        <div class="panel-card">
            <div class="panel-header">
                <h3>My Order History (<?= count($orders) ?>)</h3>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Items</th>
                            <th>Type &amp; Address</th>
                            <th>Payment</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="7">
                                You haven't placed any orders yet. Click <strong>+ Place New Water Order</strong> above!
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <?php
                            // Turn the status into a CSS class, e.g. "Out for Delivery" => "badge-out-for-delivery".
                            $badgeClass = 'badge-' . strtolower(str_replace(' ', '-', $order['status']));
                            ?>
                            <tr>
                                <td><strong><?= $e($order['order_code']) ?></strong></td>
                                <td><?= $e($order['items_summary']) ?></td>
                                <td>
                                    <?= $e($order['order_type']) ?><br>
                                    <small style="color: #64748b;"><?= $e($order['delivery_address']) ?></small>
                                </td>
                                <td><?= $e($order['payment_method']) ?></td>
                                <td><strong>&#8369;<?= number_format((float) $order['total_amount'], 2) ?></strong></td>
                                <td><span class="badge <?= $badgeClass ?>"><?= $e($order['status']) ?></span></td>
                                <td><?= $e($order['created_at']) ?></td>
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