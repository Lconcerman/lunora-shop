<?php
require_once __DIR__ . '/auth.php';
lunora_require_login('login.php');
$lunora_user = lunora_current_user();

// Auto-fill user details (always logged in at this point)
$email = $lunora_user['email'];
$name = $lunora_user['full_name'];
$csrfToken = lunora_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner">
    <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="primary-nav">
      <a href="index.php">Shop</a>
      <a href="index.php">New In</a>
      <a href="index.php">On Campus</a>
    </nav>
    <a href="index.php" class="wordmark">LUNORA</a>
    <div class="header-actions">
      <button class="icon-btn" aria-label="Search" id="searchToggle">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.6" y2="16.6"/></svg>
      </button>
      <a href="checkout.php" class="icon-btn" aria-label="Bag" id="bagToggle">
        <svg viewBox="0 0 24 24"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        <span class="bag-count" id="bagCount">0</span>
      </a>
      <a class="icon-btn" aria-label="Account" href="login.php">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
      </a>
      <?php if ($lunora_user): ?>
        <span class="account-link">Hi, <?= htmlspecialchars(explode(' ', $lunora_user['full_name'])[0]) ?></span>
        <a class="account-link account-link--logout" href="logout.php">Log Out</a>
      <?php else: ?>
        <a class="account-link" href="login.php">Log In</a>
      <?php endif; ?>
    </div>
  </div>
</header>

<main class="checkout-page">
  <div class="checkout-container">
    
    <!-- Left Column: Form -->
    <div class="checkout-left">
      <h1>Checkout</h1>
      <form id="checkoutForm" class="checkout-form" data-csrf="<?= htmlspecialchars($csrfToken) ?>">
        
        <h2>Delivery Information</h2>
        <label>Full Name
          <input type="text" name="fullname" value="<?= htmlspecialchars($name) ?>" placeholder="Jane Doe" required>
        </label>
        <label>Email Address
          <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" placeholder="you@gmail.com" required>
        </label>
        <label>Phone Number
          <input type="tel" name="phone" placeholder="+1 (555) 000-0000" required>
        </label>
        <label>Shipping Address
          <input type="text" name="address" placeholder="123 Main St, Apartment 4B, City, Country" required>
        </label>

        <h2 style="margin-top: 34px;">Payment Method</h2>
        <div class="payment-options">
          <label class="pay-option">
            <input type="radio" name="payment" value="card" checked>
            <span>Credit / Debit Card</span>
          </label>
          <label class="pay-option">
            <input type="radio" name="payment" value="paypal">
            <span>PayPal</span>
          </label>
          <label class="pay-option">
            <input type="radio" name="payment" value="cod">
            <span>Cash on Delivery</span>
          </label>
        </div>

        <button type="submit" class="auth-submit checkout-submit">Place Order</button>
      </form>
    </div>

    <!-- Right Column: Cart Summary -->
    <div class="checkout-right">
      <h2>Order Summary</h2>
      <div id="cartItemsContainer" class="cart-items">
        <!-- JavaScript will populate items here -->
      </div>
      <div class="cart-totals">
        <div class="tot-row"><span>Subtotal</span><span id="cartSubtotal">US$0.00</span></div>
        <div class="tot-row"><span>Estimated Shipping</span><span>Free</span></div>
        <div class="tot-row tot-total"><span>Total</span><span id="cartTotal">US$0.00</span></div>
      </div>
    </div>

  </div>
</main>

<footer class="site-footer">
  <div class="footer-base" style="justify-content:center; border-top:none;">
    <span class="wordmark--footer">LUNORA</span>
    <span>&copy; <?= date('Y') ?> LUNORA. All rights reserved.</span>
  </div>
</footer>

<div class="toast" id="toast" role="status" aria-live="polite"></div>
<script src="script.js"></script>
</body>
</html>