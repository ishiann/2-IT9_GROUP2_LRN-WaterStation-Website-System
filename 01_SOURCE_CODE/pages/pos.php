<?php
require_once '../auth/check_auth.php';
requireCustomer();
require_once '../config/database.php';

// Load logged-in customer's saved contact & address
$uStmt = $pdo->prepare("SELECT full_name, contact_number, address FROM users WHERE id = ?");
$uStmt->execute([$_SESSION['user_id']]);
$customer = $uStmt->fetch() ?: [
    'full_name' => $_SESSION['full_name'] ?? 'Customer',
    'contact_number' => '',
    'address' => ''
];

// Load active products
$products = $pdo->query(
    "SELECT item_code, item_name, price, stock_qty
     FROM products WHERE status = 'Active' ORDER BY id"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Water | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="../assets/css/pos.css">
    <link rel="stylesheet" href="../assets/css/portal.css">
</head>

<body class="pos-body">

    <header class="portal-header">
        <div class="portal-header-inner">
            <div class="portal-brand">
                <img src="../images/lrn_logo.png" alt="LRN Logo" class="portal-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; ORDER TERMINAL</h2>
                    <p>Customer: <strong><?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?></strong> | Palm Hill Subd. Branch</p>
                </div>
            </div>
            <nav class="portal-nav">
                <a href="customer-portal.php">My Orders</a>
                <a href="pos.php" class="active">Order Water</a>
                <a href="../index.php">Home</a>
                <a href="../auth/logout.php" class="btn-logout">Log Out</a>
            </nav>
        </div>
    </header>

    <div class="pos-container">
        <!-- LEFT: Product Catalog -->
        <div class="pos-catalog">
            <div class="catalog-header">
                <h3>Select Water Refill / Product</h3>
                <span class="catalog-subtitle">Click an item to add it to your order.</span>
            </div>
            <div class="product-grid" id="productGrid"></div>
        </div>

        <!-- RIGHT: Order Cart & Checkout -->
        <div class="pos-cart-panel">
            <div class="cart-header">
                <h3>Your Order</h3>
                <button id="btnClearCart" class="btn-clear-cart">Clear All</button>
            </div>

            <div class="cart-customer-box">
                <div class="form-group-compact">
                    <label for="posCustomerName">Customer Name</label>
                    <input type="text" id="posCustomerName"
                           value="<?= htmlspecialchars($customer['full_name'], ENT_QUOTES, 'UTF-8') ?>" readonly>
                </div>
                <div class="form-group-compact">
                    <label for="posOrderType">Order Type</label>
                    <select id="posOrderType">
                        <option value="Home Delivery">Doorstep Delivery (&#8369;10.00 fee)</option>
                        <option value="Walk-in / Pick-up">Walk-in / Station Pick-up (Free)</option>
                    </select>
                </div>
                <div id="deliveryFields">
                    <div class="form-group-compact" style="margin-bottom: 8px;">
                        <label for="posContactNumber">Contact Number *</label>
                        <input type="tel" id="posContactNumber"
                               value="<?= htmlspecialchars($customer['contact_number'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="e.g. 0992 587 1266">
                    </div>
                    <div class="form-group-compact">
                        <label for="posDeliveryAddress">Delivery Address *</label>
                        <input type="text" id="posDeliveryAddress"
                               value="<?= htmlspecialchars($customer['address'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Block, Lot, Street, Landmark">
                    </div>
                </div>
            </div>

            <div class="cart-items-wrapper">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="cartTableBody">
                        <tr class="empty-cart-row">
                            <td colspan="5">No items selected yet. Click products to add.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="cart-summary-box">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <strong id="dispSubtotal">&#8369;0.00</strong>
                </div>
                <div class="summary-line">
                    <span>Delivery Fee:</span>
                    <strong id="dispDelivery">&#8369;0.00</strong>
                </div>
                <div class="summary-line total-highlight">
                    <span>Total Amount Due:</span>
                    <strong id="dispTotal">&#8369;0.00</strong>
                </div>

                <div class="payment-inputs">
                    <div class="form-group-compact">
                        <label for="posPaymentMethod">Payment Method</label>
                        <select id="posPaymentMethod">
                            <option value="Cash">Cash on Delivery / Pick-up</option>
                            <option value="GCash">GCash Transfer</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label for="posCashTendered">Prepare Change For (&#8369;) <small style="font-weight:normal; color:#64748b;">(optional — leave blank if exact)</small></label>
                        <input type="number" id="posCashTendered" min="0" step="1" placeholder="Leave blank for exact amount">
                    </div>

                    <div class="summary-line change-highlight">
                        <span>Change Needed:</span>
                        <strong id="dispChange">&#8369;0.00</strong>
                    </div>
                </div>

                <button id="btnProcessPayment" class="btn-process-payment" disabled>
                    Place Order &amp; View Receipt
                </button>
            </div>
        </div>
    </div>

    <!-- Printable Order Receipt Modal -->
    <div id="receiptModal" class="receipt-modal-backdrop" style="display: none;">
        <div class="receipt-modal-content">
            <div class="receipt-paper" id="printableReceipt">
                <div class="receipt-header">
                    <h4>LRN PURIFIED DRINKING WATER</h4>
                    <p class="receipt-sub">"Taste the Difference"</p>
                    <p>Block 1, Lot 24 Palm Hill Subd., Palo Alto</p>
                    <p>Hotlines: 0992-587-1266 / 0906-114-7586</p>
                    <div class="receipt-divider">--------------------------------</div>
                </div>
                <div class="receipt-body">
                    <p><strong>Order #:</strong> <span id="recNo"></span></p>
                    <p><strong>Date/Time:</strong> <span id="recDate"></span></p>
                    <p><strong>Customer:</strong> <span id="recCustomer"></span></p>
                    <p><strong>Order Type:</strong> <span id="recOrderType"></span></p>
                    <div class="receipt-divider">--------------------------------</div>
                    <div id="recItemsList"></div>
                    <div class="receipt-divider">--------------------------------</div>
                    <div class="receipt-row"><span>Subtotal:</span><strong id="recSubtotal"></strong></div>
                    <div class="receipt-row"><span>Delivery:</span><strong id="recDelivery"></strong></div>
                    <div class="receipt-row total-row"><span>TOTAL DUE:</span><strong id="recTotal"></strong></div>
                    <div class="receipt-row"><span>Payment (<span id="recPayMode"></span>):</span><strong id="recPaid"></strong></div>
                    <div class="receipt-row"><span>Change:</span><strong id="recChange"></strong></div>
                    <div class="receipt-divider">--------------------------------</div>
                </div>
                <div class="receipt-footer">
                    <p>Thank you for choosing LRN Water!</p>
                    <p>Status: Pending Staff Confirmation</p>
                </div>
            </div>

            <div class="receipt-actions">
                <button onclick="window.print()" class="btn-receipt-print">Print Receipt</button>
                <button id="btnCloseReceipt" class="btn-receipt-close">Go to My Orders</button>
            </div>
        </div>
    </div>

    <script>
        window.LRN_PRODUCTS = <?= json_encode($products, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    </script>
    <script src="../assets/js/pos.js"></script>
</body>
</html>