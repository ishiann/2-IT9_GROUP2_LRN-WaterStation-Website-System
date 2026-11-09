/* ==========================================================
        POS LOGIC - LRN PURIFIED DRINKING WATER
   ========================================================== */

document.addEventListener("DOMContentLoaded", function () {

    // 1. Water Station Products & Services (Philippine Peso ₱)
    const products = [
        { id: "P1", name: "Round 5-Gal Refill", price: 30.00, icon: "💧" },
        { id: "P2", name: "Slim 5-Gal Refill", price: 35.00, icon: "🚰" },
        { id: "P3", name: "Brand New Round + Water", price: 220.00, icon: "🔵" },
        { id: "P4", name: "Brand New Slim + Water", price: 250.00, icon: "🟦" },
        { id: "P5", name: "Bottled Water 500ml (24s Box)", price: 180.00, icon: "📦" },
        { id: "P6", name: "Replacement Faucet Spigot", price: 30.00, icon: "🔧" },
        { id: "P7", name: "Non-Spill Dispenser Cap", price: 15.00, icon: "🔘" }
    ];

    let cart = [];

    // DOM Elements
    const productGrid = document.getElementById("productGrid");
    const cartTableBody = document.getElementById("cartTableBody");
    const dispSubtotal = document.getElementById("dispSubtotal");
    const dispDelivery = document.getElementById("dispDelivery");
    const dispTotal = document.getElementById("dispTotal");
    const dispChange = document.getElementById("dispChange");
    const posCashTendered = document.getElementById("posCashTendered");
    const posOrderType = document.getElementById("posOrderType");
    const posCustomerName = document.getElementById("posCustomerName");
    const posPaymentMethod = document.getElementById("posPaymentMethod");
    const btnProcessPayment = document.getElementById("btnProcessPayment");
    const btnClearCart = document.getElementById("btnClearCart");

    // Modal Elements
    const receiptModal = document.getElementById("receiptModal");
    const btnCloseReceipt = document.getElementById("btnCloseReceipt");

    // 2. Render Product Catalog
    function renderCatalog() {
        productGrid.innerHTML = products.map(item => `
            <div class="product-card" onclick="addToCart('${item.id}')">
                <div class="product-icon">${item.icon}</div>
                <div class="product-name">${item.name}</div>
                <div class="product-price">₱${item.price.toFixed(2)}</div>
            </div>
        `).join("");
    }

    // 3. Add Item to Cart
    window.addToCart = function (productId) {
        const item = products.find(p => p.id === productId);
        if (!item) return;

        const existing = cart.find(c => c.id === productId);
        if (existing) {
            existing.qty += 1;
        } else {
            cart.push({ ...item, qty: 1 });
        }
        updateCartUI();
    };

    // 4. Update Quantity (+ / -)
    window.changeQty = function (productId, delta) {
        const item = cart.find(c => c.id === productId);
        if (!item) return;

        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(c => c.id !== productId);
        }
        updateCartUI();
    };

    // 5. Remove Item
    window.removeItem = function (productId) {
        cart = cart.filter(c => c.id !== productId);
        updateCartUI();
    };

    // 6. Calculate Totals & Change
    function calculateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        // Delivery is ₱10 flat rate if delivery is chosen; free for walk-in
        const deliveryFee = (posOrderType.value === "Home Delivery" && cart.length > 0) ? 10.00 : 0.00;
        const total = subtotal + deliveryFee;

        const cash = parseFloat(posCashTendered.value) || 0;
        const change = cash - total;

        dispSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
        dispDelivery.textContent = `₱${deliveryFee.toFixed(2)}`;
        dispTotal.textContent = `₱${total.toFixed(2)}`;

        if (change >= 0 && total > 0) {
            dispChange.textContent = `₱${change.toFixed(2)}`;
            dispChange.parentElement.style.color = "#15803d";
        } else {
            dispChange.textContent = `₱0.00`;
        }

        // Enable button only if cart has items, customer name exists, and cash is sufficient
        const isCashSufficient = posPaymentMethod.value === "GCash" || cash >= total;
        const hasCustomer = posCustomerName.value.trim().length > 0;

        btnProcessPayment.disabled = !(cart.length > 0 && isCashSufficient && hasCustomer);

        return { subtotal, deliveryFee, total, cash, change };
    }

    // 7. Update Cart Table UI
    function updateCartUI() {
        if (cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr class="empty-cart-row">
                    <td colspan="5">No items selected yet. Click products to add.</td>
                </tr>
            `;
        } else {
            cartTableBody.innerHTML = cart.map(item => `
                <tr>
                    <td><strong>${item.name}</strong></td>
                    <td>
                        <button class="qty-btn" onclick="changeQty('${item.id}', -1)">-</button>
                        ${item.qty}
                        <button class="qty-btn" onclick="changeQty('${item.id}', 1)">+</button>
                    </td>
                    <td>₱${item.price.toFixed(2)}</td>
                    <td>₱${(item.price * item.qty).toFixed(2)}</td>
                    <td><button class="btn-del-item" onclick="removeItem('${item.id}')">&times;</button></td>
                </tr>
            `).join("");
        }

        calculateTotals();
    }

    // 8. Event Listeners
    posCashTendered.addEventListener("input", calculateTotals);
    posOrderType.addEventListener("change", calculateTotals);
    posCustomerName.addEventListener("input", calculateTotals);
    posPaymentMethod.addEventListener("change", function () {
        if (posPaymentMethod.value === "GCash") {
            posCashTendered.value = calculateTotals().total;
        }
        calculateTotals();
    });

    btnClearCart.addEventListener("click", function () {
        if (confirm("Clear all items in the current transaction?")) {
            cart = [];
            posCashTendered.value = "";
            updateCartUI();
        }
    });

    // 9. Process Payment & Build Receipt
    btnProcessPayment.addEventListener("click", function () {
        const { subtotal, deliveryFee, total, cash, change } = calculateTotals();

        const receiptNo = "LRN-" + Math.floor(100000 + Math.random() * 900000);
        const now = new Date().toLocaleString();

        document.getElementById("recNo").textContent = receiptNo;
        document.getElementById("recDate").textContent = now;
        document.getElementById("recCustomer").textContent = posCustomerName.value.trim();
        document.getElementById("recOrderType").textContent = posOrderType.value;
        document.getElementById("recPayMode").textContent = posPaymentMethod.value;

        // Populate items in receipt
        document.getElementById("recItemsList").innerHTML = cart.map(i => `
            <div class="receipt-row">
                <span>${i.qty}x ${i.name}</span>
                <span>₱${(i.price * i.qty).toFixed(2)}</span>
            </div>
        `).join("");

        document.getElementById("recSubtotal").textContent = `₱${subtotal.toFixed(2)}`;
        document.getElementById("recDelivery").textContent = `₱${deliveryFee.toFixed(2)}`;
        document.getElementById("recTotal").textContent = `₱${total.toFixed(2)}`;
        document.getElementById("recPaid").textContent = `₱${cash.toFixed(2)}`;
        document.getElementById("recChange").textContent = `₱${Math.max(0, change).toFixed(2)}`;

        // Show Modal
        receiptModal.style.display = "flex";
    });

    // Close Receipt & Reset for Next Customer
    btnCloseReceipt.addEventListener("click", function () {
        receiptModal.style.display = "none";
        cart = [];
        posCustomerName.value = "";
        posCashTendered.value = "";
        updateCartUI();
    });

    // Init
    renderCatalog();
});