<?php
session_start();
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? '') === 'admin') {
        header("Location: pages/dashboard.php");
    } else {
        header("Location: pages/customer-portal.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Login Page | LRN Purified Drinking Water</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="login-body">

  <div class="login-wrapper">
      <!-- Return to Landing Page Link-->
      <div class="top-nav-bar">
          <a href="index.php" class="back-home-link">&#8592; Return to Home Page</a>
      </div>

      <div class="login-card-container">
          <div class="welcome">
              <div class="school-brand">
                  <img src="images/lrn_logo.png" alt="LRN Purified Drinking Water Logo">
                  <div class="school-name">LRN PURIFIED WATER</div>
                  <div class="department">Station & Delivery Services</div>
                  <div class="college">Block 1, Lot 24 Palm Hill Subd., Palo Alto</div>
              </div>

              <div class="welcome-content">
                  <h1>Welcome.</h1>
                  <p>Log in to access your customer portal, schedule container refills, track delivery dispatch, and manage your account.</p>
              </div>

              <div class="welcome-footer">
                  <p>&copy; 2026 LRN PURIFIED DRINKING WATER</p>
              </div>

          </div>

          <!-- FORM SECTION -->
          <div class="form-section">
              <div class="forms">

                 <form id="customerLoginForm" class="login-form" action="auth/login-process.php" method="POST">
                    
                      <h2>CUSTOMER LOG IN</h2>
                      <p id="loginError" style="display:none; color:#b91c1c; background:#fee2e2; border:1px solid #fca5a5; padding:8px 12px; border-radius:6px; margin-bottom:10px;"></p>
                      <p class="subtitle">Welcome back! Please enter your account credentials.</p>

                      <!-- 1. Email or Username -->
                      <label for="loginUser">Email or Generated Username</label>
                      <input
                          type="text"
                          id="loginUser"
                          name="loginUser"
                          placeholder="e.g. jasgar1999_ja27 or juan@email.com"
                          required
                      >

                      <!-- 2. Password -->
                      <label for="loginPass">Password</label>
                      <input
                          type="password"
                          id="loginPass"
                          name="loginPass"
                          placeholder="Enter your account password"
                          required
                      >

                      <!-- 3. Login Button -->
                      <button class="main-button" type="submit">
                          Log In
                      </button>

                      <!-- 4. Registration Link -->
                      <p class="switch-text">
                          Don't have an account yet?
                          <a href="registration.html" class="switch-link">Click here to Register / Order</a>
                      </p>

                  </form>

              </div>
          </div>

      </div>

  </div>


    <script>
      (function () {
        var msgs = {
          invalid:  "Incorrect username/email or password.",
          empty:    "Please enter your username/email and password.",
          required: "Please log in first to open the ordering page.",
          server:   "Something went wrong. Please try again."
        };
        var code = new URLSearchParams(window.location.search).get("error");
        if (code && msgs[code]) {
          var box = document.getElementById("loginError");
          box.textContent = msgs[code];
          box.style.display = "block";
        }
      })();
    </script>
</body>
</html>