<?php
// pages/products.php
require_once '../auth/check_auth.php';
require_once '../config/database.php';

// CREATE: Add new product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    $code  = trim($_POST['item_code']);
    $name  = trim($_POST['item_name']);
    $cat   = trim($_POST['category']);
    $price = floatval($_POST['price']);
    $stock = intval($_POST['stock_qty']);

    $stmt = $pdo->prepare("INSERT INTO products (item_code, item_name, category, price, stock_qty) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$code, $name, $cat, $price, $stock]);
    header("Location: products.php?msg=created");
    exit();
}

// READ: Retrieve all product records
$products = $pdo->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product CRUD | LRN Water</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container my-4">
        <a href="dashboard.php" class="btn btn-secondary btn-sm mb-3">&larr; Return to Dashboard</a>
        <h3 class="fw-bold mb-3">Product &amp; Container Inventory (CRUD Management)</h3>

        <!-- CREATE Form -->
        <div class="card p-3 shadow-sm mb-4">
            <h5 class="fw-bold">Add New Product (CREATE)</h5>
            <form action="products.php" method="POST" class="row g-2">
                <input type="hidden" name="action" value="create">
                <div class="col-md-2">
                    <input type="text" name="item_code" class="form-control" placeholder="Code (e.g. REF-003)" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="item_name" class="form-control" placeholder="Item Name" required>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select" required>
                        <option value="Refill">Refill</option>
                        <option value="Container">Container</option>
                        <option value="Bottled">Bottled</option>
                        <option value="Accessory">Accessory</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <input type="number" step="0.5" name="price" class="form-control" placeholder="Price (₱)" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="stock_qty" class="form-control" placeholder="Stock Qty" required>
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-success w-100">Add</button>
                </div>
            </form>
        </div>

        <!-- READ Table -->
        <div class="card p-3 shadow-sm">
            <h5 class="fw-bold">Current Product Records (READ)</h5>
            <table class="table table-bordered table-hover align-middle mt-2">
                <thead class="table-dark">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
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
                        <td><strong><?= htmlspecialchars($p['item_code']) ?></strong></td>
                        <td><?= htmlspecialchars($p['item_name']) ?></td>
                        <td><?= htmlspecialchars($p['category']) ?></td>
                        <td>₱<?= number_format($p['price'], 2) ?></td>
                        <td><?= $p['stock_qty'] ?></td>
                        <td>
                            <span class="badge <?= $p['status'] === 'Active' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $p['status'] ?>
                            </span>
                        </td>
                        <td>
                            <!-- UPDATE Link -->
                            <a href="edit_product.php?id=<?= $p['id'] ?>" class="btn btn-warning btn-sm">Edit</a>
                            <!-- DELETE Link -->
                            <a href="delete_product.php?id=<?= $p['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Confirm deletion of this record?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>