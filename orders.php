<?php
$pageTitle = 'My Orders';
require_once 'includes/header.php';
requireLogin();

$stmt = $pdo->prepare("SELECT o.*,
    (SELECT b.name FROM order_items oi JOIN bouquets b ON b.id=oi.bouquet_id WHERE oi.order_id=o.id ORDER BY oi.id LIMIT 1) AS bouquet_name,
    (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.order_id=o.id) AS bouquet_qty
  FROM orders o
  WHERE o.user_id=?
  ORDER BY o.created_at DESC, o.id DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$statusLabels = [
  'pending' => 'Pending',
  'arranging' => 'Arranging',
  'ready' => 'Ready for pickup',
  'complete' => 'Order complete',
];
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="products.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>
    <a href="contact.php" class="back-link"><i class="fa-regular fa-envelope"></i> Message the shop</a>
  </div>

  <div class="page-intro">
    <span class="eyebrow">Your flowers, remembered</span>
    <h1>My orders</h1>
    <p>Every thoughtful gesture in one place. Revisit a receipt, check your pickup schedule, or message us about a bouquet.</p>
  </div>

  <?php if (empty($orders)): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-box-open"></i>
      <h2>No orders yet</h2>
      <a href="products.php" class="btn btn-primary">Shop fresh flowers</a>
    </div>
  <?php else: ?>
    <div class="order-history">
      <?php foreach ($orders as $order): ?>
      <?php
        $fulfillment = $order['fulfillment_status'] ?? 'pending';
        $fulfillment = isset($statusLabels[$fulfillment]) ? $fulfillment : 'pending';
      ?>
      <article class="order-history-card">
        <div class="order-history-main">
          <div class="order-history-kicker">
            <span>Order #<?php echo htmlspecialchars($order['order_number']); ?></span>
            <span><?php echo date('j M Y', strtotime($order['created_at'])); ?></span>
          </div>
          <h2><?php echo htmlspecialchars($order['bouquet_name'] ?? 'Your bouquet'); ?></h2>
          <p class="order-history-meta">
            <?php echo (int)$order['bouquet_qty']; ?> bouquet<?php echo (int)$order['bouquet_qty'] === 1 ? '' : 's'; ?>
            <?php if (!empty($order['pickup_date'])): ?>
              · Pickup <?php echo date('j M', strtotime($order['pickup_date'])); ?> · <?php echo htmlspecialchars($order['pickup_time']); ?>
            <?php endif; ?>
          </p>
          <div class="order-history-badges">
            <span class="status-pill <?php echo $order['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>"><?php echo $order['payment_status'] === 'paid' ? 'Paid' : 'Payment pending'; ?></span>
            <span class="status-pill <?php echo $fulfillment; ?>"><?php echo $statusLabels[$fulfillment]; ?></span>
          </div>
        </div>

        <div class="order-history-side">
          <span class="order-history-label">Order total</span>
          <strong>₱<?php echo number_format((float)$order['total']); ?></strong>
          <a href="order_complete.php?id=<?php echo (int)$order['id']; ?>" class="btn btn-outline-green btn-sm">View receipt <i class="fa-solid fa-arrow-right"></i></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
