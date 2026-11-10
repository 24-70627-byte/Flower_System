<?php
require_once 'config.php';
requireLogin();

$cart = !empty($_SESSION['cart']) ? $_SESSION['cart'] : [];
if (empty($cart)) {
  setFlash('error', 'Your cart is empty.');
  redirect('cart.php');
}

$subtotal = 0;
$itemDetails = [];
foreach ($cart as $key => $item) {
  $subtotal += $item['price'] * $item['quantity'];
  $stmt = $pdo->prepare("SELECT description FROM bouquets WHERE id=?");
  $stmt->execute([$key]);
  $itemDetails[$key] = $stmt->fetch() ?: ['description' => 'Made to order with the freshest seasonal stems.'];
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
  $settings[$row['key']] = $row['value'];
}
$downpaymentPercent = max(0, min(100, (int)($settings['downpayment_percent'] ?? 50)));
$downpaymentAmount = round($subtotal * $downpaymentPercent / 100, 2);
$shopAddress = $settings['shop_address'] ?? "Nicole's Flowers & Greens shop";

$notes = $_SESSION['cart_notes'] ?? ['gift_note' => '', 'florist_note' => ''];
$pickupSlots = [
  '10 am – 12 pm',
  '1 pm – 3 pm',
  '3 pm – 5 pm',
];

$error = '';
$form = [
  'full_name' => $user['full_name'] ?? '',
  'email' => $user['email'] ?? '',
  'phone' => $user['phone'] ?? '',
  'pickup_date' => '',
  'pickup_time' => $pickupSlots[0],
  'payment_method' => 'GCash',
  'reference_number' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form = [
    'full_name' => trim($_POST['full_name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'phone' => trim($_POST['phone'] ?? ''),
    'pickup_date' => trim($_POST['pickup_date'] ?? ''),
    'pickup_time' => trim($_POST['pickup_time'] ?? ''),
    'payment_method' => trim($_POST['payment_method'] ?? 'GCash'),
    'reference_number' => trim($_POST['reference_number'] ?? ''),
  ];
  $terms = isset($_POST['terms']);
  $allowedPayments = ['GCash', 'Bank transfer', 'Pay at shop'];

  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token.';
  } elseif (!$form['full_name'] || !$form['phone'] || !$form['pickup_date'] || !$form['pickup_time']) {
    $error = 'Please complete your details and pickup schedule.';
  } elseif (!in_array($form['payment_method'], $allowedPayments, true)) {
    $error = 'Please choose a valid payment method.';
  } elseif ($form['payment_method'] !== 'Pay at shop' && !$form['reference_number']) {
    $error = 'Please enter your payment reference number.';
  } elseif (!$terms) {
    $error = 'Please confirm that you understand the final price will be confirmed by the seller.';
  } elseif (strtotime($form['pickup_date']) === false) {
    $error = 'Please choose a valid pickup date.';
  } else {
    try {
      $pdo->beginTransaction();

      $upd = $pdo->prepare("UPDATE users SET full_name=?, phone=? WHERE id=?");
      $upd->execute([$form['full_name'], $form['phone'], $_SESSION['user_id']]);

      $nextNumber = (int)$pdo->query("SELECT COALESCE(MAX(CAST(order_number AS UNSIGNED)), 1040) + 1 FROM orders")->fetchColumn();
      $orderNumber = (string)$nextNumber;
      $paymentStatus = ($form['payment_method'] !== 'Pay at shop' && $form['reference_number'] !== '') ? 'paid' : 'pending';

      $ins = $pdo->prepare("INSERT INTO orders
        (user_id, order_number, subtotal, customization, total, downpayment, payment_status, fulfillment_status, status,
         payment_method, reference_number, pickup_date, pickup_time, recipient_name, gift_note, florist_note,
         shipping_address, phone, notes)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
      $ins->execute([
        $_SESSION['user_id'], $orderNumber, $subtotal, 0, $subtotal, $downpaymentAmount,
        $paymentStatus, 'pending', 'pending', $form['payment_method'], $form['reference_number'] ?: null,
        $form['pickup_date'], $form['pickup_time'], $form['full_name'],
        $notes['gift_note'] ?? '', $notes['florist_note'] ?? '', $shopAddress, $form['phone'], $notes['florist_note'] ?? ''
      ]);
      $orderId = (int)$pdo->lastInsertId();

      $itemIns = $pdo->prepare("INSERT INTO order_items (order_id, bouquet_id, quantity, price) VALUES (?,?,?,?)");
      foreach ($cart as $key => $item) {
        $itemIns->execute([$orderId, $key, $item['quantity'], $item['price']]);
      }

      $pdo->commit();
      $_SESSION['cart'] = [];
      unset($_SESSION['cart_notes']);
      redirect('order_complete.php?id=' . $orderId);
    } catch (Throwable $e) {
      if ($pdo->inTransaction()) {
        $pdo->rollBack();
      }
      $error = 'We could not place your order just now. Please try again.';
    }
  }
}

$pageTitle = 'Checkout';
require_once 'includes/header.php';
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="cart.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to cart</a>
    <div class="progress-steps" aria-label="Checkout progress">
      <span class="progress-step">01 Cart</span>
      <span class="progress-line"></span>
      <span class="progress-step active">02 Payment</span>
      <span class="progress-line"></span>
      <span class="progress-step">03 Pickup</span>
    </div>
  </div>

  <div class="page-intro">
    <h1>Checkout</h1>
    <p>Confirm your details and send your down payment so we can start your bouquet.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <form method="post" class="checkout-layout" id="checkoutForm">
    <?php echo csrfField(); ?>

    <div class="checkout-main">
      <section class="checkout-card">
        <h2>Your details</h2>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($form['full_name']); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="phone">Mobile Number</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($form['phone']); ?>" placeholder="09XX XXX XXXX" required>
          </div>
          <div class="form-group" style="grid-column:1/-1;">
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($form['email']); ?>" readonly>
          </div>
        </div>
      </section>

      <section class="checkout-card">
        <div class="checkout-card-head">
          <h2>Pickup Schedule</h2>
          <span>Pickup only</span>
        </div>
        <div class="pickup-field">
          <i class="fa-regular fa-calendar"></i>
          <input type="date" name="pickup_date" class="form-control" value="<?php echo htmlspecialchars($form['pickup_date']); ?>" min="<?php echo date('Y-m-d'); ?>" required aria-label="Pickup date">
          <span class="pickup-sep">at</span>
          <select name="pickup_time" class="form-control" aria-label="Pickup time">
            <?php foreach ($pickupSlots as $slot): ?>
              <option value="<?php echo htmlspecialchars($slot); ?>" <?php echo $form['pickup_time'] === $slot ? 'selected' : ''; ?>><?php echo htmlspecialchars($slot); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <p class="checkout-help">The seller will confirm your pickup date, time and address after checking your down payment.</p>
      </section>

      <section class="checkout-card">
        <div class="checkout-card-head">
          <h2>Down payment</h2>
          <span class="downpayment-required">Required · <?php echo $downpaymentPercent; ?>%</span>
        </div>
        <p class="checkout-help" style="margin-top:0;margin-bottom:16px;">Send the downpayment amount confirmed by the admin (seller), then enter your reference number.</p>

        <div class="radio-cards" style="margin-bottom:16px;">
          <?php foreach (['GCash', 'Bank transfer', 'Pay at shop'] as $method): ?>
          <label class="radio-card <?php echo $form['payment_method'] === $method ? 'selected' : ''; ?>">
            <input type="radio" name="payment_method" value="<?php echo htmlspecialchars($method); ?>" <?php echo $form['payment_method'] === $method ? 'checked' : ''; ?>>
            <span><?php echo htmlspecialchars($method); ?></span>
          </label>
          <?php endforeach; ?>
        </div>

        <div class="form-group" style="margin-bottom:0;max-width:330px;">
          <label class="form-label" for="reference_number">Reference number</label>
          <input type="text" id="reference_number" name="reference_number" class="form-control" value="<?php echo htmlspecialchars($form['reference_number']); ?>" placeholder="Enter reference no.">
        </div>
        <p class="checkout-help">Downpayment due today: <span class="text-green">₱<?php echo number_format($downpaymentAmount); ?></span> · Balance settled at pickup.</p>
      </section>
    </div>

    <aside class="checkout-aside">
      <div class="summary-card">
        <h2>Order summary</h2>

        <?php foreach ($cart as $key => $item): ?>
        <div class="summary-item">
          <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
          <div>
            <h3><?php echo htmlspecialchars($item['name']); ?></h3>
            <p><?php echo htmlspecialchars($itemDetails[$key]['description']); ?></p>
            <p class="qty">Qty <?php echo (int)$item['quantity']; ?></p>
          </div>
        </div>
        <?php endforeach; ?>

        <div class="summary-line"><span>Bouquet price</span><strong>₱<?php echo number_format($subtotal); ?></strong></div>
        <div class="summary-line"><span>Customization</span><span>To be confirmed</span></div>
        <div class="summary-line"><span>Gift note</span><span><?php echo !empty($notes['gift_note']) ? 'Gift note' : '—'; ?></span></div>
        <div class="summary-line"><span>Pickup schedule</span><span>Set by seller</span></div>
        <div class="summary-total-line"><span>Total Order</span><strong>₱<?php echo number_format($subtotal); ?></strong></div>

        <p class="summary-note"><strong>Please Note:</strong>Price shown is an estimate.</p>

        <label class="terms-line">
          <input type="checkbox" name="terms" value="1" <?php echo isset($_POST['terms']) ? 'checked' : ''; ?> required>
          <span>I understand the final price will be confirmed by the seller.</span>
        </label>

        <div class="summary-actions">
          <button type="submit" class="btn btn-primary btn-block">Place order <i class="fa-solid fa-arrow-right"></i></button>
          <a href="cart.php" class="btn btn-outline-green btn-block">Back to cart</a>
        </div>
        <p class="secure-note"><i class="fa-solid fa-lock"></i> Secure checkout</p>
      </div>

      <div class="info-card">
        <h3>Fresh, made for you.</h3>
        <p>The seller sets your pickup date and time after confirming your downpayment, then your bouquet is prepared with the freshest stems in season.</p>
      </div>
    </aside>
  </form>
</div>

<?php require_once 'includes/footer.php'; ?>
