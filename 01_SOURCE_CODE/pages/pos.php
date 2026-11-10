<?php
// This will kick unauthorized users back to login.html
require_once '../auth/check_auth.php'; 
require_once '../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashier POS Terminal | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/pos.css">
</head>

<body class="pos-body">

    <!-- Top POS Navigation Bar -->
    <header class="pos-header">
        <div class="pos-header-inner">
            <div class="pos-brand">
                <img src="images/lrn_logo.png" alt="LRN Logo" class="pos-logo">
                <div>
                    <h2>LRN PURIFIED WATER &bull; POS TERMINAL</h2>
                    <p>Station Cashier: <strong><?= htmlspecialchars($_SESSION['full_name']) ?></strong> | Palm Hill Subd. Branch</p>
                </div>
            </div>
            <div class="pos-nav-actions">
                <a href="index.html" class="pos-exit-btn">&larr; Exit to Home</a>
            </div>
        </div>
    </header>

    <!-- Main POS Workspace -->
    <div class="pos-container">
        
        <!-- LEFT: Product Catalog & Item Selector -->
        <div class="pos-catalog">
            <div class="catalog-header">
                <h3>Select Water Refill / Product</h3>
                <span class="catalog-subtitle">Click an item to add to the active customer order.</span>
            </div>

            <!-- Product Grid -->
            <div class="product-grid" id="productGrid">
                <!-- Injected dynamically by pos.js -->
            </div>
        </div>

        <!-- RIGHT: Live Order Cart & Payment Section -->
        <div class="pos-cart-panel">
            <div class="cart-header">
                <h3>Current Transaction</h3>
                <button id="btnClearCart" class="btn-clear-cart">Clear All</button>
            </div>

            <!-- Customer & Transaction Details -->
            <div class="cart-customer-box">
                <div class="form-group-compact">
                    <label for="posCustomerName">Customer Name / Account ID</label>
                    <input type="text" id="posCustomerName" placeholder="e.g. Walk-in or Juan Dela Cruz" required>
                </div>
                <div class="form-group-compact">
                    <label for="posOrderType">Order Type</label>
                    <select id="posOrderType">
                        <option value="Walk-in / Pick-up">Walk-in / Station Pick-up</option>
                        <option value="Home Delivery">Doorstep Delivery (Palm Hill Subd.)</option>
                    </select>
                </div>
            </div>

            <!-- Scrollable Cart Items Table -->
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

            <!-- Total Calculations & Payment -->
            <div class="cart-summary-box">
                <div class="summary-line">
                    <span>Subtotal:</span>
                    <strong id="dispSubtotal">₱0.00</strong>
                </div>
                <div class="summary-line">
                    <span>Delivery Fee:</span>
                    <strong id="dispDelivery">₱0.00</strong>
                </div>
                <div class="summary-line total-highlight">
                    <span>Total Amount Due:</span>
                    <strong id="dispTotal">₱0.00</strong>
                </div>

                <!-- Payment Handling -->
                <div class="payment-inputs">
                    <div class="form-group-compact">
                        <label for="posPaymentMethod">Payment Method</label>
                        <select id="posPaymentMethod">
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash Transfer</option>
                        </select>
                    </div>

                    <div class="form-group-compact">
                        <label for="posCashTendered">Cash Tendered (₱)</label>
                        <input type="number" id="posCashTendered" min="0" step="1" placeholder="Enter amount received">
                    </div>

                    <div class="summary-line change-highlight">
                        <span>Change:</span>
                        <strong id="dispChange">₱0.00</strong>
                    </div>
                </div>

                <!-- Action Button -->
                <button id="btnProcessPayment" class="btn-process-payment" disabled>
                    Complete Transaction &amp; Print Receipt
                </button>
            </div>

        </div>

    </div>

    <!-- Printable Receipt Modal -->
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
                    <p><strong>Receipt #:</strong> <span id="recNo"></span></p>
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
                    <p>Micro-filtered &amp; AlphaLab Certified Safe</p>
                </div>
            </div>

            <div class="receipt-actions">
                <button onclick="window.print()" class="btn-receipt-print">Print Receipt</button>
                <button id="btnCloseReceipt" class="btn-receipt-close">New Transaction</button>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="js/pos.js"></script>
</body>
</html>