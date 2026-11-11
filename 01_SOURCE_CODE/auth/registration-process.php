<?php
// auth/registration-process.php

// 1. Ensure the request came via POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../registration.html");
    exit();
}

// 2. Connect to MySQL Database (step out of auth/ into config/)
require_once '../config/database.php';

// 3. Extract and sanitize inputs
$firstName      = htmlspecialchars(trim($_POST['firstName'] ?? ''));
$middleInitial  = htmlspecialchars(trim($_POST['middleInitial'] ?? ''));
$lastName       = htmlspecialchars(trim($_POST['lastName'] ?? ''));
$suffix         = htmlspecialchars(trim($_POST['suffix'] ?? ''));
$customerId     = htmlspecialchars(trim($_POST['customerId'] ?? ''));
$contactNumber  = htmlspecialchars(trim($_POST['contactNumber'] ?? ''));
$birthDate      = htmlspecialchars(trim($_POST['birthDate'] ?? ''));
$email          = htmlspecialchars(trim($_POST['email'] ?? ''));
$password       = $_POST['password'] ?? '';
$confirmPassword= $_POST['confirmPassword'] ?? '';
$containerType  = htmlspecialchars(trim($_POST['containerType'] ?? 'N/A'));
$customerType   = htmlspecialchars(trim($_POST['customerType'] ?? 'N/A'));
$address        = htmlspecialchars(trim($_POST['address'] ?? ''));
$terms          = isset($_POST['terms']) ? 'Yes, Accepted' : 'No';

// Assemble Full Name
$fullName = $firstName;
if (!empty($middleInitial)) {
    $mi = str_ends_with($middleInitial, '.') ? $middleInitial : $middleInitial . '.';
    $fullName .= ' ' . $mi;
}
$fullName .= ' ' . $lastName;
if (!empty($suffix)) {
    $fullName .= ' ' . $suffix;
}

// Extract multiple checkboxes (Delivery Schedule)
if (!empty($_POST['deliverySchedule']) && is_array($_POST['deliverySchedule'])) {
    $deliverySchedule = htmlspecialchars(implode(', ', $_POST['deliverySchedule']));
} else {
    $deliverySchedule = 'None selected';
}

// Calculate Age in PHP
$age = 0;
if (!empty($birthDate)) {
    $dob = new DateTime($birthDate);
    $today = new DateTime();
    $age = $today->diff($dob)->y;
}

// Generate Username (Format: First3 + Last3 + BirthYear + "_" + Email2 + Age)
$fPart       = substr(preg_replace('/[^a-zA-Z]/', '', $firstName), 0, 3);
$lPart       = substr(preg_replace('/[^a-zA-Z]/', '', $lastName), 0, 3);
$birthYear   = date('Y', strtotime($birthDate));
$emailPrefix = explode('@', $email)[0];
$ePart       = substr(preg_replace('/[^a-zA-Z0-9]/', '', $emailPrefix), 0, 2);

$generatedUsername = strtolower($fPart . $lPart . $birthYear . '_' . $ePart . $age);

// =========================================================================
// 4. DATABASE VALIDATION & INSERTION (Saves record to MySQL)
// =========================================================================
$errors = [];
$dbSuccess = false;

// Basic Server-side checks
if (empty($firstName) || empty($lastName) || empty($email) || empty($password)) {
    $errors[] = "Please fill in all required fields.";
}
if ($age < 18) {
    $errors[] = "Applicant must be at least 18 years old to register.";
}
if ($password !== $confirmPassword) {
    $errors[] = "Passwords do not match.";
}

if (empty($errors)) {
    try {
        // A. Check for duplicate email or username
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
        $checkStmt->execute([$generatedUsername, $email]);

        if ($checkStmt->rowCount() > 0) {
            $errors[] = "An account with this email address or username already exists. Please log in.";
        } else {
            // B. Hash password securely
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // C. Insert into MySQL 'users' table
            $insertStmt = $pdo->prepare("
                INSERT INTO users (full_name, username, email, password, contact_number, role) 
                VALUES (?, ?, ?, ?, ?, 'customer')
            ");
            $insertStmt->execute([
                $fullName,
                $generatedUsername,
                $email,
                $hashedPassword,
                $contactNumber
            ]);

            $dbSuccess = true;
        }
    } catch (PDOException $e) {
        $errors[] = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Confirmation | LRN Purified Water</title>
    
    <!-- Bootstrap 5 CSS for the Modal -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Existing Project Styles (stepping out of auth/) -->
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
        .modal-header-custom {
            background-color: #023047;
            color: #ffffff;
            border-bottom: 3px solid #0284c7;
        }
        .username-badge-custom {
            font-family: 'Courier New', Courier, monospace;
            background-color: #e0f2fe;
            color: #023047;
            border: 1px solid #7dd3fc;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 700;
        }
    </style>
</head>
<body>

    <!-- BOOTSTRAP MODAL -->
    <div class="modal fade" id="registrationModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content shadow-lg border-0">
                
                <!-- Modal Header -->
                <div class="modal-header modal-header-custom">
                    <h5 class="modal-title fw-bold" id="modalTitle">
                        💧 LRN Purified Drinking Water — Registration Summary
                    </h5>
                </div>

                <!-- Modal Body (Output Table) -->
                <div class="modal-body p-4">

                    <?php if ($dbSuccess): ?>
                        <div class="alert alert-success d-flex align-items-center" role="alert">
                            <div>
                                <strong>Registration Successful!</strong> Your account has been saved to the database. You can now use your generated username or email to log in.
                            </div>
                        </div>

                        <table class="table table-bordered table-striped align-middle mt-3">
                            <tbody>
                                <tr class="table-info">
                                    <th style="width: 35%;">Generated Username (Use to Log In)</th>
                                    <td><span class="username-badge-custom"><?= $generatedUsername ?></span></td>
                                </tr>
                                <tr>
                                    <th>Customer Name</th>
                                    <td><?= $fullName ?></td>
                                </tr>
                                <tr>
                                    <th>Customer / Account ID</th>
                                    <td><?= $customerId ?></td>
                                </tr>
                                <tr>
                                    <th>Contact Number</th>
                                    <td><?= $contactNumber ?></td>
                                </tr>
                                <tr>
                                    <th>Birth Date & Age</th>
                                    <td><?= $birthDate ?> (Age: <strong><?= $age ?></strong>, 18+ Verified)</td>
                                </tr>
                                <tr>
                                    <th>Email Address</th>
                                    <td><?= $email ?></td>
                                </tr>
                                <tr>
                                    <th>Preferred Container</th>
                                    <td><?= $containerType ?></td>
                                </tr>
                                <tr>
                                    <th>Delivery Schedule</th>
                                    <td><?= $deliverySchedule ?></td>
                                </tr>
                                <tr>
                                    <th>Customer Classification</th>
                                    <td><?= $customerType ?></td>
                                </tr>
                                <tr>
                                    <th>Delivery Address & Notes</th>
                                    <td><?= nl2br($address) ?></td>
                                </tr>
                                <tr>
                                    <th>Terms & Conditions</th>
                                    <td><span class="badge bg-success"><?= $terms ?></span></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <!-- Error Alert if Validation or Database Fails -->
                        <div class="alert alert-danger" role="alert">
                            <h5 class="alert-heading fw-bold">Registration Failed</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= htmlspecialchars($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Modal Footer Actions (Fixed paths stepping out to root) -->
                <div class="modal-footer bg-light">
                    <?php if ($dbSuccess): ?>
                        <a href="../login.html" class="btn btn-success fw-bold">Proceed to Log In</a>
                        <a href="../index.html" class="btn btn-primary" style="background-color: #0284c7; border-color: #0284c7;">Back to Home Page</a>
                    <?php else: ?>
                        <a href="../registration.html" class="btn btn-secondary">Go Back &amp; Fix Details</a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Automatically show modal on page load -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const modalElement = document.getElementById("registrationModal");
            const regModal = new bootstrap.Modal(modalElement);
            regModal.show();
        });
    </script>
</body>
</html>