<?php
require_once '../auth/check_auth.php';
requireAdmin();
require_once '../config/database.php';

$allowedStatuses = ['Pending', 'Out for Delivery', 'Completed', 'Cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');

    if ($orderId > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        $_SESSION['flash'] = ['success', "Order status updated to \"$newStatus\"."];
    } else {
        $_SESSION['flash'] = ['error', "Invalid status update request."];
    }
    $redirectFilter = $_POST['current_filter'] ?? 'All';
    header("Location: orders.php?status=" . urlencode($redirectFilter));
    exit();
}

$flashType = '';
$flashMsg  = '';
if (isset($_SESSION['flash'])) {
    [$flashType, $flashMsg] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

$filter = $_GET['status'] ?? 'All';
$sql = "SELECT o.*,
               COALESCE(GROUP_CONCAT(CONCAT(oi.quantity, 'x ', p.item_name) SEPARATOR ', '), '—') AS items_summary
        FROM orders o
        LEFT JOIN order_items oi ON oi.order_id = o.id
        LEFT JOIN products p ON p.id = oi.product_id";

$params = [];
if (in_array($filter, $allowedStatuses, true)) {
    $sql .= " WHERE o.status = ?";
    $params[] = $filter;
}
$sql .= " GROUP BY o.id ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders &amp; Deliveries | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">

    <header class="portal-header">
        <div class="portal-header-inner">
            <div class="portal-brand">
                <img src="../images/lrn_logo.png" alt="LRN Logo" class="portal-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; ORDERS &amp; TRANSACTIONS</h2>
                    <p>View customer orders and update delivery status</p>
                </div>
            </div>
            <nav class="portal-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php">Products (CRUD)</a>
                <a href="orders.php" class="active">Orders</a>
                <a href="../auth/logout.php" class="btn-logout">Log Out</a>
            </nav>
        </div>
    </header>

    <main class="portal-container">
        <?php if ($flashMsg !== ''): ?>
            <div class="alert <?= $flashType === 'success' ? 'alert-success' : 'alert-error' ?>">
                <?= htmlspecialchars($flashMsg) ?>
            </div>
        <?php endif; ?>

        <div class="panel-card">
            <div class="panel-header">
                <h3>All Orders (<?= count($orders) ?>)</h3>
                <div class="action-row">
                    <?php foreach (array_merge(['All'], $allowedStatuses) as $tab): ?>
                        <a href="orders.php?status=<?= urlencode($tab) ?>"
                           class="<?= $filter === $tab ? 'btn-primary' : 'btn-secondary' ?>">
                            <?= htmlspecialchars($tab) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order Code</th>
                            <th>Customer</th>
                            <th>Contact &amp; Address</th>
                            <th>Items</th>
                            <th>Type / Pay</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Update Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="8">No orders found for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $o): ?>
                        <?php $badge = 'badge-' . strtolower(str_replace(' ', '-', $o['status'])); ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($o['order_code']) ?></strong><br>
                                <small style="color:#64748b;"><?= htmlspecialchars($o['created_at']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($o['customer_name']) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($o['contact_number']) ?></strong><br>
                                <small><?= htmlspecialchars($o['delivery_address']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($o['items_summary']) ?></td>
                            <td>
                                <?= htmlspecialchars($o['order_type']) ?><br>
                                <small style="color:#64748b;"><?= htmlspecialchars($o['payment_method']) ?></small>
                            </td>
                            <td><strong>&#8369;<?= number_format((float)$o['total_amount'], 2) ?></strong></td>
                            <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($o['status']) ?></span></td>
                            <td>
                                <form method="POST" action="orders.php" class="action-row">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                                    <input type="hidden" name="current_filter" value="<?= htmlspecialchars($filter) ?>">
                                    <select name="status" style="padding:6px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px;">
                                        <?php foreach ($allowedStatuses as $st): ?>
                                            <option value="<?= $st ?>" <?= $o['status'] === $st ? 'selected' : '' ?>>
                                                <?= $st ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn-primary">Update</button>
                                </form>
                            </td>
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