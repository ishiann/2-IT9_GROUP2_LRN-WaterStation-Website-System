<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond($ok, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $ok], $data));
    exit();
}

if (!isset($_SESSION['user_id'])) {
    respond(false, ['message' => 'You are not logged in.'], 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, ['message' => 'Invalid request.'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    respond(false, ['message' => 'Invalid data received.'], 400);
}

$role          = $_SESSION['role'] ?? 'customer';
$orderType     = trim($input['orderType'] ?? '');
$paymentMethod = trim($input['paymentMethod'] ?? '');
$items         = $input['items'] ?? [];

if (!in_array($orderType, ['Walk-in / Pick-up', 'Home Delivery'], true)) {
    respond(false, ['message' => 'Invalid order type.'], 400);
}
if (!in_array($paymentMethod, ['Cash', 'GCash'], true)) {
    respond(false, ['message' => 'Invalid payment method.'], 400);
}
if (!is_array($items) || count($items) === 0) {
    respond(false, ['message' => 'The cart is empty.'], 400);
}

try {
    $pdo->beginTransaction();

    $findProduct = $pdo->prepare(
        "SELECT id, item_name, price FROM products WHERE item_code = ? AND status = 'Active'"
    );
    $lines    = [];
    $subtotal = 0.0;

    foreach ($items as $it) {
        $code = trim($it['id'] ?? '');
        $qty  = (int)($it['qty'] ?? 0);
        if ($code === '' || $qty < 1 || $qty > 500) {
            throw new RuntimeException('Invalid item in the cart.');
        }
        $findProduct->execute([$code]);
        $p = $findProduct->fetch();
        if (!$p) {
            throw new RuntimeException("Product $code is not available.");
        }
        $unit = (float)$p['price'];
        $line = round($unit * $qty, 2);
        $subtotal += $line;
        $lines[] = ['product_id' => $p['id'], 'qty' => $qty, 'unit' => $unit, 'line' => $line];
    }

    $deliveryFee = ($orderType === 'Home Delivery') ? 10.00 : 0.00;
    $total       = round($subtotal + $deliveryFee, 2);

    // Fetch logged-in user's details for customer self-orders / fallback
    $u = $pdo->prepare("SELECT full_name, contact_number, address FROM users WHERE id = ?");
    $u->execute([$_SESSION['user_id']]);
    $userRow = $u->fetch() ?: [];

    if ($role === 'admin') {
        $customerName = trim($input['customerName'] ?? '');
        if ($customerName === '') {
            throw new RuntimeException('Customer name is required.');
        }
        $contact = trim($input['contactNumber'] ?? '');
        $address = trim($input['deliveryAddress'] ?? '');

        if ($orderType === 'Home Delivery') {
            if ($contact === '' || $address === '') {
                throw new RuntimeException('Contact number and delivery address are required for Home Delivery.');
            }
            $status = 'Pending';
        } else {
            if ($contact === '') $contact = 'Walk-in';
            if ($address === '') $address = 'Walk-in / Station Pick-up';
            $status = 'Completed';
        }
    } else {
        // Customer self-order via customer-portal.php
        $customerName = $userRow['full_name'] ?? ($_SESSION['full_name'] ?? 'Customer');
        $contact      = trim($input['contactNumber'] ?? '') ?: ($userRow['contact_number'] ?? '');
        $address      = ($orderType === 'Home Delivery')
            ? (trim($input['deliveryAddress'] ?? '') ?: ($userRow['address'] ?? ''))
            : 'Walk-in / Station Pick-up';

        if ($contact === '' || ($orderType === 'Home Delivery' && $address === '')) {
            throw new RuntimeException('Contact number and delivery address are required.');
        }
        // Customer online orders always start as Pending until staff confirms/completes
        $status = 'Pending';
    }

    $insertOrder = $pdo->prepare(
        "INSERT INTO orders
           (order_code, user_id, customer_name, contact_number, delivery_address,
            order_type, total_amount, payment_method, status)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    $orderCode = '';
    for ($try = 0; $try < 5; $try++) {
        $orderCode = 'LRN-' . date('ymd') . '-' . random_int(1000, 9999);
        try {
            $insertOrder->execute([
                $orderCode, $_SESSION['user_id'], $customerName, $contact, $address,
                $orderType, $total, $paymentMethod, $status
            ]);
            break;
        } catch (PDOException $dup) {
            if ($dup->getCode() !== '23000' || $try === 4) throw $dup;
        }
    }
    $orderId = (int)$pdo->lastInsertId();

    $insertItem = $pdo->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
         VALUES (?, ?, ?, ?, ?)"
    );
    $reduceStock = $pdo->prepare(
        "UPDATE products SET stock_qty = stock_qty - ? WHERE id = ? AND stock_qty >= ?"
    );
    foreach ($lines as $l) {
        $reduceStock->execute([$l['qty'], $l['product_id'], $l['qty']]);
        if ($reduceStock->rowCount() === 0) {
            throw new RuntimeException('Not enough stock for one of the items.');
        }
        $insertItem->execute([$orderId, $l['product_id'], $l['qty'], $l['unit'], $l['line']]);
    }

    $pdo->commit();

    respond(true, [
        'order_code'   => $orderCode,
        'subtotal'     => $subtotal,
        'delivery_fee' => $deliveryFee,
        'total'        => $total
    ]);

} catch (RuntimeException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(false, ['message' => $ex->getMessage()], 400);
} catch (PDOException $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(false, ['message' => 'Database error. The order was not saved.'], 500);
}