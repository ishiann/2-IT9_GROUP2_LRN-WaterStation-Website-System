/* ==========================================================
        POS LOGIC - LRN PURIFIED DRINKING WATER
   ========================================================== */

document.addEventListener("DOMContentLoaded", function () {
    const icons = { P1: "💧", P2: "🚰", P3: "🔵", P4: "🟦", P5: "📦", P6: "🔧", P7: "🔘" };
    const products = (window.LRN_PRODUCTS || []).map(p => ({
        id: p.item_code,
        name: p.item_name,
        price: parseFloat(p.price),
        stock: parseInt(p.stock_qty, 10) || 0,
        icon: icons[p.item_code] || "💧"
    }));

    let cart = [];

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
    const posContactNumber = document.getElementById("posContactNumber");
    const posDeliveryAddress = document.getElementById("posDeliveryAddress");
    const deliveryFields = document.getElementById("deliveryFields");
    const btnProcessPayment = document.getElementById("btnProcessPayment");
    const btnClearCart = document.getElementById("btnClearCart");
    const receiptModal = document.getElementById("receiptModal");
    const btnCloseReceipt = document.getElementById("btnCloseReceipt");

    function renderCatalog() {
        if (products.length === 0) {
            productGrid.innerHTML = "<p>No active products found. Add products in Products (CRUD).</p>";
            return;
        }
        productGrid.innerHTML = products.map(item => `
            <div class="product-card" style="${item.stock <= 0 ? 'opacity:0.5; cursor:not-allowed;' : ''}"
                 onclick="addToCart('${item.id}')">
                <div class="product-icon">${item.icon}</div>
                <div class="product-name">${item.name}</div>
                <div class="product-price">₱${item.price.toFixed(2)}</div>
                <div style="font-size:11.5px; color:${item.stock > 0 ? '#64748b' : '#dc2626'}; margin-top:4px;">
                    ${item.stock > 0 ? `Stock: ${item.stock}` : 'Out of Stock'}
                </div>
            </div>
        `).join("");
    }

    window.addToCart = function (productId) {
        const item = products.find(p => p.id === productId);
        if (!item || item.stock <= 0) {
            alert("This product is out of stock.");
            return;
        }
        const existing = cart.find(c => c.id === productId);
        if (existing) {
            if (existing.qty >= item.stock) {
                alert(`Only ${item.stock} left in stock for ${item.name}.`);
                return;
            }
            existing.qty += 1;
        } else {
            cart.push({ ...item, qty: 1 });
        }
        updateCartUI();
    };

    window.changeQty = function (productId, delta) {
        const item = cart.find(c => c.id === productId);
        const prod = products.find(p => p.id === productId);
        if (!item) return;

        if (delta > 0 && prod && item.qty >= prod.stock) {
            alert(`Only ${prod.stock} left in stock for ${item.name}.`);
            return;
        }

        item.qty += delta;
        if (item.qty <= 0) {
            cart = cart.filter(c => c.id !== productId);
        }
        updateCartUI();
    };

    window.removeItem = function (productId) {
        cart = cart.filter(c => c.id !== productId);
        updateCartUI();
    };

    function toggleDeliveryFields() {
        const isDelivery = posOrderType.value === "Home Delivery";
        deliveryFields.style.display = isDelivery ? "block" : "none";
    }

    function calculateTotals() {
        const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        const isDelivery = posOrderType.value === "Home Delivery";
        const deliveryFee = (isDelivery && cart.length > 0) ? 10.00 : 0.00;
        const total = subtotal + deliveryFee;

        if (posPaymentMethod.value === "GCash") {
            posCashTendered.value = total.toFixed(2);
            posCashTendered.readOnly = true;
        } else {
            posCashTendered.readOnly = false;
        }

        const cash = parseFloat(posCashTendered.value) || 0;
        const change = cash - total;

        dispSubtotal.textContent = `₱${subtotal.toFixed(2)}`;
        dispDelivery.textContent = `₱${deliveryFee.toFixed(2)}`;
        dispTotal.textContent = `₱${total.toFixed(2)}`;
        dispChange.textContent = (change >= 0 && total > 0) ? `₱${change.toFixed(2)}` : `₱0.00`;

        const isCashSufficient = posPaymentMethod.value === "GCash" || cash >= total;
        const hasCustomer = posCustomerName.value.trim().length > 0;
        const hasDeliveryDetails = !isDelivery || (
            posContactNumber.value.trim().length > 0 &&
            posDeliveryAddress.value.trim().length > 0
        );

        btnProcessPayment.disabled = !(cart.length > 0 && isCashSufficient && hasCustomer && hasDeliveryDetails);

        return { subtotal, deliveryFee, total, cash, change };
    }

    function updateCartUI() {
        if (cart.length === 0) {
            cartTableBody.innerHTML = `
                <tr class="empty-cart-row">
                    <td colspan="5">No items selected yet. Click products to add.</td>
                </tr>`;
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

    posCashTendered.addEventListener("input", calculateTotals);
    posCustomerName.addEventListener("input", calculateTotals);
    posContactNumber.addEventListener("input", calculateTotals);
    posDeliveryAddress.addEventListener("input", calculateTotals);
    posOrderType.addEventListener("change", function () {
        toggleDeliveryFields();
        calculateTotals();
    });
    posPaymentMethod.addEventListener("change", calculateTotals);

    btnClearCart.addEventListener("click", function () {
        if (confirm("Clear all items in the current transaction?")) {
            cart = [];
            posCashTendered.value = "";
            updateCartUI();
        }
    });

    btnProcessPayment.addEventListener("click", async function () {
        let { subtotal, deliveryFee, total, cash, change } = calculateTotals();

        btnProcessPayment.disabled = true;
        const originalLabel = btnProcessPayment.textContent;
        btnProcessPayment.textContent = "Saving order...";

        let receiptNo;
        try {
            const response = await fetch("save-order.php", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    customerName: posCustomerName.value.trim(),
                    orderType: posOrderType.value,
                    paymentMethod: posPaymentMethod.value,
                    contactNumber: posContactNumber.value.trim(),
                    deliveryAddress: posDeliveryAddress.value.trim(),
                    items: cart.map(i => ({ id: i.id, qty: i.qty }))
                })
            });
            const result = await response.json();

            if (response.status === 401) {
                alert("Your session expired. Please log in again.");
                window.location.href = "../login.php";
                return;
            }
            if (!result.success) {
                alert("Order was NOT saved: " + (result.message || "Unknown error."));
                btnProcessPayment.textContent = originalLabel;
                calculateTotals();
                return;
            }

            receiptNo = result.order_code;
            subtotal = result.subtotal;
            deliveryFee = result.delivery_fee;
            total = result.total;
            change = cash - total;

            // Update local stock display
            cart.forEach(c => {
                const prod = products.find(p => p.id === c.id);
                if (prod) prod.stock = Math.max(0, prod.stock - c.qty);
            });
            renderCatalog();
        } catch (err) {
            alert("Could not reach the server. The order was NOT saved.");
            btnProcessPayment.textContent = originalLabel;
            calculateTotals();
            return;
        }
        btnProcessPayment.textContent = originalLabel;

        document.getElementById("recNo").textContent = receiptNo;
        document.getElementById("recDate").textContent = new Date().toLocaleString();
        document.getElementById("recCustomer").textContent = posCustomerName.value.trim();
        document.getElementById("recOrderType").textContent = posOrderType.value;
        document.getElementById("recPayMode").textContent = posPaymentMethod.value;

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

        receiptModal.style.display = "flex";
    });

    btnCloseReceipt.addEventListener("click", function () {
        receiptModal.style.display = "none";
        cart = [];
        posCustomerName.value = "";
        posContactNumber.value = "";
        posDeliveryAddress.value = "";
        posCashTendered.value = "";
        updateCartUI();
    });

    toggleDeliveryFields();
    renderCatalog();
});