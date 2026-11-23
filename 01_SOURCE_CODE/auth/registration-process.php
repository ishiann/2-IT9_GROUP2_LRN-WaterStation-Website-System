<?php

require_once '../config/database.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registration.php");
    exit();
}


function e($text) {
    return htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8');
}

// 1. Read the form values (raw text; we escape only when printing)
$firstName     = trim($_POST['firstName'] ?? '');
$middleInitial = trim($_POST['middleInitial'] ?? '');
$lastName      = trim($_POST['lastName'] ?? '');
$suffix        = trim($_POST['suffix'] ?? '');
$customerId    = trim($_POST['customerId'] ?? '');
$contactNumber = trim($_POST['contactNumber'] ?? '');
$birthDate     = trim($_POST['birthDate'] ?? '');
$email         = trim($_POST['email'] ?? '');
$password      = $_POST['password'] ?? '';
$confirmPass   = $_POST['confirmPassword'] ?? '';
$customerType  = trim($_POST['customerType'] ?? 'N/A');
$address       = trim($_POST['address'] ?? '');
$terms         = isset($_POST['terms']) ? 'Yes, Accepted' : 'No';

// Full name: First M. Last Suffix
$fullName = $firstName;
if ($middleInitial !== '') {
    $fullName .= ' ' . (substr($middleInitial, -1) === '.' ? $middleInitial : $middleInitial . '.');
}
$fullName .= ' ' . $lastName;
if ($suffix !== '') {
    $fullName .= ' ' . $suffix;
}

// 2. Validate 
$errors = [];

if ($firstName === '' || $lastName === '')      $errors[] = "First name and last name are required.";
if ($customerId === '')                         $errors[] = "Customer / Account ID is required.";
if ($contactNumber === '')                      $errors[] = "Contact number is required.";
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Please enter a valid email address.";
if ($address === '')                            $errors[] = "Delivery address is required.";
if ($terms !== 'Yes, Accepted')                 $errors[] = "You must accept the Terms & Conditions.";
if (
    strlen($password) < 8 ||
    !preg_match('/[a-z]/', $password) ||
    !preg_match('/[A-Z]/', $password) ||
    !preg_match('/[0-9]/', $password) ||
    !preg_match('/[^A-Za-z0-9]/', $password)
) {
    $errors[] = "Password must be 8+ characters with uppercase, lowercase, a number, and a special character.";
}
if ($password !== $confirmPass) {
    $errors[] = "Password and Confirm Password do not match.";
}

$age = 0;
$birthOk = false;
if ($birthDate !== '') {
    $dob = DateTime::createFromFormat('Y-m-d', $birthDate);
    if ($dob && $dob->format('Y-m-d') === $birthDate && $dob <= new DateTime()) {
        $age = (new DateTime())->diff($dob)->y;
        $birthOk = true;
    }
}
if (!$birthOk)    $errors[] = "Please enter a valid birth date.";
elseif ($age < 18) $errors[] = "You must be at least 18 years old to register.";

// 3. Generate username: First3 + Last3 + BirthYear + "_" + Email2 + Age
$generatedUsername = '';
if (empty($errors)) {
    $fPart       = substr($firstName, 0, 3);
    $lPart       = substr($lastName, 0, 3);
    $birthYear   = substr($birthDate, 0, 4);
    $emailPrefix = explode('@', $email)[0];
    $ePart       = substr($emailPrefix, 0, 2);
    $baseUsername = strtolower(preg_replace('/\s+/', '', $fPart . $lPart . $birthYear . '_' . $ePart . $age));
    $generatedUsername = $baseUsername;
}

// 4. Check duplicates, then save
if (empty($errors)) {
    try {
        // Email already used?
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = "That email address is already registered.";

        // Customer ID already used?
        $stmt = $pdo->prepare("SELECT id FROM users WHERE customer_id = ?");
        $stmt->execute([$customerId]);
        if ($stmt->fetch()) $errors[] = "That Customer / Account ID is already registered.";

        if (empty($errors)) {
            // Username already used? Add a number at the end until it is free.
            $suffixNum = 1;
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            while (true) {
                $check->execute([$generatedUsername]);
                if (!$check->fetch()) break;
                $suffixNum++;
                $generatedUsername = $baseUsername . $suffixNum;
            }

            // NEVER store the plain password: store a secure hash
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users
                      (full_name, username, customer_id, email, password, contact_number,
                       role, birth_date, customer_type, address)
                    VALUES (?, ?, ?, ?, ?, ?, 'customer', ?, ?, ?)";
            $pdo->prepare($sql)->execute([
                $fullName, $generatedUsername, $customerId, $email, $passwordHash, $contactNumber,
                $birthDate, $customerType, $address
            ]);
        }
    } catch (PDOException $ex) {
        // Shows a friendly message (detail is hidden from the user dapat)
        $errors[] = "Database error: " . $ex->getMessage();
       
    }
}

$success = empty($errors);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration | LRN Purified Water</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: linear-gradient(rgba(2, 48, 71, 0.85), rgba(2, 132, 199, 0.75)),
                        url('../images/water_background.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .modal-header-custom { background-color: #023047; color: #ffffff; border-bottom: 3px solid #0284c7; }
        .username-badge-custom {
            font-family: 'Courier New', Courier, monospace;
            background-color: #e0f2fe; color: #023047; border: 1px solid #7dd3fc;
            padding: 4px 10px; border-radius: 6px; font-weight: 700;
        }
    </style>
</head>
<body>

    <div class="modal fade" id="registrationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">

                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        💧 LRN Purified Drinking Water — <?= $success ? 'Registration Summary' : 'Registration Problem' ?>
                    </h5>
                </div>

<?php if ($success): ?>
                <div class="modal-body p-4">
                    <div class="alert alert-success" role="alert">
                        <strong>Registration Successful!</strong> Your account was saved. Use your generated username or email to log in.
                    </div>

                    <table class="table table-bordered table-striped align-middle mt-3">
                        <tbody>
                            <tr class="table-info">
                                <th style="width: 35%;">Generated Username</th>
                                <td><span class="username-badge-custom"><?= e($generatedUsername) ?></span></td>
                            </tr>
                            <tr><th>Customer Name</th><td><?= e($fullName) ?></td></tr>
                            <tr><th>Customer / Account ID</th><td><?= e($customerId) ?></td></tr>
                            <tr><th>Contact Number</th><td><?= e($contactNumber) ?></td></tr>
                            <tr><th>Birth Date &amp; Age</th><td><?= e($birthDate) ?> (Age: <strong><?= (int)$age ?></strong>, 18+ Verified)</td></tr>
                            <tr><th>Email Address</th><td><?= e($email) ?></td></tr>
                            <tr><th>Customer Classification</th><td><?= e($customerType) ?></td></tr>
                            <tr><th>Delivery Address &amp; Notes</th><td><?= nl2br(e($address)) ?></td></tr>
                            <tr><th>Terms &amp; Conditions</th><td><span class="badge bg-success"><?= e($terms) ?></span></td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer bg-light">
                    <a href="../registration.php" class="btn btn-outline-secondary">Register Another Account</a>
                    <a href="../login.php" class="btn btn-primary" style="background-color: #0284c7; border-color: #0284c7;">Go to Log In</a>
                </div>
<?php else: ?>
                <div class="modal-body p-4">
                    <div class="alert alert-danger" role="alert">
                        <strong>We could not complete your registration:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach ($errors as $err): ?>
                                <li><?= e($err) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <a href="../registration.php" class="btn btn-primary" style="background-color: #0284c7; border-color: #0284c7;">&larr; Go Back to the Form</a>
                </div>
<?php endif; ?>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            new bootstrap.Modal(document.getElementById("registrationModal")).show();
        });
    </script>
</body>
</html>
