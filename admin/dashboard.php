<?php
$pageTitle = 'Overview';
$active = 'dashboard';
require_once 'header.php';

$totalOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status <> 'cancelled'")->fetchColumn();
$totalCustomers = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='customer'")->fetchColumn();
$totalBouquets = (int)$pdo->query("SELECT COUNT(*) FROM bouquets WHERE status='active'")->fetchColumn();
$awaiting = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE fulfillment_status IN ('pending','arranging')")->fetchColumn();
$unread = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE sender='customer' AND is_read=0")->fetchColumn();
$pickupToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE pickup_date=CURDATE()")->fetchColumn();

$stmt = $pdo->query("SELECT o.*, u.full_name,
    (SELECT b.name FROM order_items oi JOIN bouquets b ON b.id=oi.bouquet_id WHERE oi.order_id=o.id ORDER BY oi.id LIMIT 1) AS bouquet_name
  FROM orders o JOIN users u ON o.user_id=u.id
  ORDER BY o.created_at DESC, o.id DESC LIMIT 6");
$recentOrders = $stmt->fetchAll();

$statusLabels = [
  'pending' => 'Pending',
  'arranging' => 'Arranging',
  'ready' => 'Ready for Pickup',
  'complete' => 'Order Complete',
];
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">A thoughtful shop, at a glance</span>
    <h1>Shop overview</h1>
    <p class="desc">Keep an eye on today's orders, your catalog, and the customers you bloom for.</p>
  </div>
  <div class="admin-date"><?php echo date('l, j F Y'); ?></div>
</div>

<div class="admin-container">
  <div class="stats-grid">
    <div class="stat-tile"><span>Total orders</span><strong><?php echo $totalOrders; ?></strong></div>
    <div class="stat-tile"><span>Total revenue</span><strong>₱<?php echo number_format($totalRevenue); ?></strong></div>
    <div class="stat-tile"><span>Awaiting fulfillment</span><strong><?php echo $awaiting; ?></strong></div>
    <div class="stat-tile"><span>Pickup today</span><strong><?php echo $pickupToday; ?></strong></div>
    <div class="stat-tile"><span>Available bouquets</span><strong><?php echo $totalBouquets; ?></strong></div>
    <div class="stat-tile"><span>Unread messages</span><strong><?php echo $unread; ?></strong></div>
  </div>

  <div class="dashboard-layout">
    <section class="orders-panel dashboard-orders">
      <div class="dashboard-panel-head">
        <h2>Recent orders</h2>
        <a href="orders.php" class="btn-edit">View all orders</a>
      </div>
      <div class="table-wrap">
        <table class="data-table orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Bouquet</th>
              <th>Amount</th>
              <th>Fulfillment</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($recentOrders as $o): ?>
            <tr onclick="window.location.href='orders.php?selected=<?php echo (int)$o['id']; ?>'">
              <td><a class="order-id" href="orders.php?selected=<?php echo (int)$o['id']; ?>">#<?php echo htmlspecialchars($o['order_number']); ?></a></td>
              <td>
                <span class="cell-strong"><?php echo htmlspecialchars($o['full_name']); ?></span>
                <div class="cell-muted"><?php echo date('j M, g:i a', strtotime($o['created_at'])); ?></div>
              </td>
              <td class="cell-strong"><?php echo htmlspecialchars($o['bouquet_name'] ?? '—'); ?></td>
              <td class="order-amount">₱<?php echo number_format((float)$o['total']); ?></td>
              <td><span class="status-badge <?php echo htmlspecialchars($o['fulfillment_status']); ?>"><?php echo $statusLabels[$o['fulfillment_status']] ?? 'Pending'; ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($recentOrders)): ?>
              <tr><td colspan="5" class="table-empty">No orders yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>

    <aside class="dashboard-aside">
      <div class="dashboard-card">
        <div class="card-kicker">Today's shop</div>
        <div class="dashboard-kv"><span>Orders awaiting fulfillment</span><strong><?php echo $awaiting; ?></strong></div>
        <div class="dashboard-kv"><span>Pickups today</span><strong><?php echo $pickupToday; ?></strong></div>
        <div class="dashboard-kv"><span>Unread messages</span><strong><?php echo $unread; ?></strong></div>
        <div class="dashboard-links">
          <a href="orders.php" class="btn btn-primary btn-block">Manage orders <i class="fa-solid fa-arrow-right"></i></a>
          <a href="inbox.php" class="btn btn-outline-green btn-block">Open inbox</a>
        </div>
      </div>

      <div class="dashboard-card">
        <div class="card-kicker">Your shop</div>
        <div class="dashboard-kv"><span>Customers</span><strong><?php echo $totalCustomers; ?></strong></div>
        <div class="dashboard-kv"><span>Available bouquets</span><strong><?php echo $totalBouquets; ?></strong></div>
        <div class="dashboard-links">
          <a href="bouquets.php" class="btn btn-outline-green btn-block">Manage bouquets</a>
          <a href="settings.php" class="btn btn-outline-green btn-block">Shop settings</a>
        </div>
      </div>
    </aside>
  </div>
</div>

<?php require_once 'footer.php'; ?>
