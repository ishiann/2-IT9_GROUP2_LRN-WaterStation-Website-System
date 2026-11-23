<?php
require_once '../auth/check_auth.php';
requireAdmin();
require_once '../config/database.php';

$flashType = '';
$flashMsg  = '';

// Handle POST Actions (Create, Update, Toggle Status, Delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $code     = strtoupper(trim($_POST['item_code'] ?? ''));
            $name     = trim($_POST['item_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price    = (float)($_POST['price'] ?? -1);
            $stock    = (int)($_POST['stock_qty'] ?? -1);
            $status   = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

            if ($code === '' || $name === '' || $category === '' || $price < 0 || $stock < 0) {
                throw new RuntimeException("All fields are required and price/stock cannot be negative.");
            }

            $stmt = $pdo->prepare(
                "INSERT INTO products (item_code, item_name, category, price, stock_qty, status)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([$code, $name, $category, $price, $stock, $status]);
            $_SESSION['flash'] = ['success', "Product \"$name\" ($code) added to MySQL."];

        } elseif ($action === 'update') {
            $id       = (int)($_POST['id'] ?? 0);
            $code     = strtoupper(trim($_POST['item_code'] ?? ''));
            $name     = trim($_POST['item_name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $price    = (float)($_POST['price'] ?? -1);
            $stock    = (int)($_POST['stock_qty'] ?? -1);
            $status   = ($_POST['status'] ?? 'Active') === 'Inactive' ? 'Inactive' : 'Active';

            if ($id <= 0 || $code === '' || $name === '' || $category === '' || $price < 0 || $stock < 0) {
                throw new RuntimeException("Invalid product update data.");
            }

            $stmt = $pdo->prepare(
                "UPDATE products
                 SET item_code = ?, item_name = ?, category = ?, price = ?, stock_qty = ?, status = ?
                 WHERE id = ?"
            );
            $stmt->execute([$code, $name, $category, $price, $stock, $status, $id]);
            $_SESSION['flash'] = ['success', "Product \"$name\" updated in MySQL."];

        } elseif ($action === 'toggle_status') {
            $id        = (int)($_POST['id'] ?? 0);
            $newStatus = ($_POST['new_status'] ?? 'Inactive') === 'Active' ? 'Active' : 'Inactive';
            $pdo->prepare("UPDATE products SET status = ? WHERE id = ?")->execute([$newStatus, $id]);
            $_SESSION['flash'] = ['success', "Product status changed to $newStatus."];

        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
            $_SESSION['flash'] = ['success', "Product permanently deleted from MySQL."];
        }
    } catch (RuntimeException $e) {
        $_SESSION['flash'] = ['error', $e->getMessage()];
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            if ($action === 'delete') {
                $_SESSION['flash'] = ['error', "Cannot permanently delete this product because it already has order history. Use 'Deactivate' instead."];
            } else {
                $_SESSION['flash'] = ['error', "Item Code already exists. Please use a unique Item Code."];
            }
        } else {
            $_SESSION['flash'] = ['error', "Database error while saving changes."];
        }
    }

    header("Location: products.php");
    exit();
}

if (isset($_SESSION['flash'])) {
    [$flashType, $flashMsg] = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Check if editing an existing product
$editProduct = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editProduct = $stmt->fetch();
}

$products = $pdo->query("SELECT * FROM products ORDER BY id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products CRUD | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>
<body class="portal-body">

    <header class="portal-header">
        <div class="portal-header-inner">
            <div class="portal-brand">
                <img src="../images/lrn_logo.png" alt="LRN Logo" class="portal-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; PRODUCT RECORDS</h2>
                    <p>Manage inventory, pricing, and availability</p>
                </div>
            </div>
            <nav class="portal-nav">
                <a href="dashboard.php">Dashboard</a>
                <a href="products.php" class="active">Products (CRUD)</a>
                <a href="orders.php">Orders</a>
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

        <!-- CREATE / UPDATE FORM -->
        <div class="panel-card">
            <div class="panel-header">
                <h3><?= $editProduct ? 'Edit Product: ' . htmlspecialchars($editProduct['item_name']) : 'Add New Product' ?></h3>
                <?php if ($editProduct): ?>
                    <a href="products.php" class="btn-secondary">Cancel Edit</a>
                <?php endif; ?>
            </div>

            <form method="POST" action="products.php">
                <input type="hidden" name="action" value="<?= $editProduct ? 'update' : 'create' ?>">
                <?php if ($editProduct): ?>
                    <input type="hidden" name="id" value="<?= (int)$editProduct['id'] ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="field-group">
                        <label for="item_code">Item Code *</label>
                        <input type="text" id="item_code" name="item_code" required placeholder="e.g. P8"
                               value="<?= htmlspecialchars($editProduct['item_code'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="item_name">Item Name *</label>
                        <input type="text" id="item_name" name="item_name" required placeholder="e.g. Alkaline 5-Gal Refill"
                               value="<?= htmlspecialchars($editProduct['item_name'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="category">Category *</label>
                        <?php $cat = $editProduct['category'] ?? 'Refill'; ?>
                        <select id="category" name="category" required>
                            <option value="Refill"    <?= $cat === 'Refill' ? 'selected' : '' ?>>Refill</option>
                            <option value="Container" <?= $cat === 'Container' ? 'selected' : '' ?>>Container</option>
                            <option value="Bottled"   <?= $cat === 'Bottled' ? 'selected' : '' ?>>Bottled</option>
                            <option value="Accessory" <?= $cat === 'Accessory' ? 'selected' : '' ?>>Accessory</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="price">Price (&#8369;) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required
                               value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="stock_qty">Stock Qty *</label>
                        <input type="number" id="stock_qty" name="stock_qty" min="0" required
                               value="<?= htmlspecialchars($editProduct['stock_qty'] ?? '0') ?>">
                    </div>
                    <div class="field-group">
                        <label for="status">Status *</label>
                        <?php $st = $editProduct['status'] ?? 'Active'; ?>
                        <select id="status" name="status">
                            <option value="Active"   <?= $st === 'Active' ? 'selected' : '' ?>>Active</option>
                            <option value="Inactive" <?= $st === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                </div>

                <div style="margin-top: 14px;">
                    <button type="submit" class="btn-primary">
                        <?= $editProduct ? 'Save Changes' : 'Add Product' ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- READ TABLE -->
        <div class="panel-card">
            <div class="panel-header">
                <h3>All Products (<?= count($products) ?>)</h3>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><?= (int)$p['id'] ?></td>
                            <td><strong><?= htmlspecialchars($p['item_code']) ?></strong></td>
                            <td><?= htmlspecialchars($p['item_name']) ?></td>
                            <td><?= htmlspecialchars($p['category']) ?></td>
                            <td>&#8369;<?= number_format((float)$p['price'], 2) ?></td>
                            <td><?= (int)$p['stock_qty'] ?></td>
                            <td>
                                <span class="badge <?= $p['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                                    <?= htmlspecialchars($p['status']) ?>
                                </span>
                            </td>
                            <td>
                                <div class="action-row">
                                    <a href="products.php?edit=<?= (int)$p['id'] ?>" class="btn-primary">Edit</a>

                                    <form method="POST" action="products.php" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $p['status'] === 'Active' ? 'Inactive' : 'Active' ?>">
                                        <button type="submit" class="btn-warning"
                                                onclick="return confirm('Change status of <?= htmlspecialchars($p['item_name'], ENT_QUOTES) ?>?')">
                                            <?= $p['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="products.php" style="display:inline;"
                                          onsubmit="return confirm('Permanently delete <?= htmlspecialchars($p['item_name'], ENT_QUOTES) ?>? This cannot be undone.')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>