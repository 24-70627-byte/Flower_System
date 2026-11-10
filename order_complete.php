<?php
$pageTitle = 'Order Complete';
require_once 'includes/header.php';
requireLogin();

$order = null;
$items = [];
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
  $stmt->execute([$_GET['id'], $_SESSION['user_id']]);
  $order = $stmt->fetch();

  if ($order) {
    $stmt = $pdo->prepare("SELECT oi.*, b.name, b.description, b.image FROM order_items oi JOIN bouquets b ON oi.bouquet_id=b.id WHERE oi.order_id=?");
    $stmt->execute([$order['id']]);
    $items = $stmt->fetchAll();
  }
}

$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
  $settings[$row['key']] = $row['value'];
}
$shopAddress = $order['shipping_address'] ?? ($settings['shop_address'] ?? "Nicole's Flowers & Greens shop");
$downpayment = (float)($order['downpayment'] ?? 0);
$paymentStatus = $order['payment_status'] ?? 'pending';
$orderTotal = (float)($order['total'] ?? 0);
$balance = max(0, $orderTotal - ($paymentStatus === 'paid' ? $downpayment : 0));
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="products.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>
    <div class="progress-steps" aria-label="Checkout progress">
      <span class="progress-step">01 Cart</span>
      <span class="progress-line"></span>
      <span class="progress-step">02 Payment</span>
      <span class="progress-line"></span>
      <span class="progress-step active">03 Pickup</span>
    </div>
  </div>

  <?php if (!$order): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-box-open"></i>
      <h2>Order not found</h2>
      <a href="products.php" class="btn btn-primary">Shop fresh flowers</a>
    </div>
  <?php else: ?>
    <div class="page-intro">
      <span class="eyebrow">A little beauty, on its way</span>
      <h1>Order complete</h1>
      <p>We'll be arranging your bouquet just as you want. As you pick up, please settle the remaining balance at the shop.</p>
    </div>

    <div class="receipt-page">
      <div class="receipt-card">
        <h2>Your order is in</h2>
        <p class="receipt-sub">Order #<?php echo htmlspecialchars($order['order_number']); ?> · We'll message you when your bouquet is ready.</p>

        <div class="receipt-section">
          <div class="receipt-section-title">Pickup details</div>
          <div class="receipt-row">
            <span class="label">Date and time:</span>
            <span class="value"><?php echo date('j M Y', strtotime($order['pickup_date'])); ?> · <?php echo htmlspecialchars($order['pickup_time']); ?></span>
          </div>
          <div class="receipt-row">
            <span class="label">Where:</span>
            <span class="value"><?php echo htmlspecialchars($shopAddress); ?></span>
          </div>
        </div>

        <?php if (!empty($order['florist_note'])): ?>
        <div class="receipt-section">
          <div class="receipt-section-title">Your note to the florist:</div>
          <p class="receipt-note-text"><?php echo nl2br(htmlspecialchars($order['florist_note'])); ?></p>
        </div>
        <?php endif; ?>

        <div class="receipt-section">
          <div class="receipt-section-title">Order summary</div>
          <?php foreach ($items as $item): ?>
          <div class="receipt-row">
            <span class="label"><?php echo htmlspecialchars($item['name']); ?> × <?php echo (int)$item['quantity']; ?></span>
            <span class="value">from ₱<?php echo number_format((float)$item['price']); ?></span>
          </div>
          <?php endforeach; ?>
          <?php if ((float)$order['customization'] > 0): ?>
          <div class="receipt-row">
            <span class="label">Customization Price</span>
            <span class="value">₱<?php echo number_format((float)$order['customization']); ?></span>
          </div>
          <?php endif; ?>
          <div class="receipt-row total">
            <span class="label">Total</span>
            <span class="value">₱<?php echo number_format((float)$order['total']); ?></span>
          </div>
          <div class="receipt-row down">
            <span class="label"><?php echo $order['payment_status'] === 'paid' ? 'Downpayment paid' : 'Downpayment'; ?></span>
            <span class="value"><?php echo $order['payment_status'] === 'paid' ? '−₱' . number_format($downpayment) : 'Pending'; ?></span>
          </div>
          <div class="receipt-row balance">
            <span class="label">Balance at pickup</span>
            <span class="value">₱<?php echo number_format($balance); ?></span>
          </div>
        </div>

        <div class="receipt-actions">
          <a href="contact.php" class="btn btn-outline-green btn-block">Message seller for concerns</a>
        </div>
        <p class="receipt-footnote">Screenshot this as the receipt/proof</p>
      </div>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
