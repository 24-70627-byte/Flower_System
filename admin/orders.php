<?php
$pageTitle = 'Customer Orders';
$active = 'orders';
require_once 'header.php';

$tab = $_GET['tab'] ?? 'all';
$q = trim($_GET['q'] ?? '');
$paymentFilter = $_GET['payment'] ?? 'all';
$dateFilter = trim($_GET['date'] ?? '');
$validTabs = ['all', 'pending', 'arranging', 'ready', 'complete'];
if (!in_array($tab, $validTabs, true)) {
  $tab = 'all';
}

$preserveQuery = function (array $overrides = []) use ($tab, $q, $paymentFilter, $dateFilter): string {
  $query = array_filter([
    'tab' => $tab,
    'q' => $q,
    'payment' => $paymentFilter,
    'date' => $dateFilter,
  ], fn($v) => $v !== '' && $v !== 'all');
  foreach ($overrides as $key => $value) {
    if ($value === null || $value === '' || $value === 'all') {
      unset($query[$key]);
    } else {
      $query[$key] = $value;
    }
  }
  return 'orders.php' . ($query ? '?' . http_build_query($query) : '');
};

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
  $id = (int)($_POST['id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if ($id && $action === 'customization') {
    $customization = max(0, (float)($_POST['customization'] ?? 0));
    $stmt = $pdo->prepare("SELECT subtotal FROM orders WHERE id=?");
    $stmt->execute([$id]);
    $orderSubtotal = (float)($stmt->fetchColumn() ?: 0);
    $pdo->prepare("UPDATE orders SET customization=?, total=? WHERE id=?")
      ->execute([$customization, $orderSubtotal + $customization, $id]);
    setFlash('success', 'Customization price saved.');
  } elseif ($id && $action === 'status') {
    $newStatus = $_POST['fulfillment_status'] ?? 'pending';
    if (!in_array($newStatus, ['pending', 'arranging', 'ready', 'complete'], true)) {
      $newStatus = 'pending';
    }
    if (!empty($_POST['make_ready'])) {
      $newStatus = 'ready';
    }
    $legacy = $newStatus === 'complete' ? 'completed' : ($newStatus === 'pending' ? 'pending' : 'processing');
    $pdo->prepare("UPDATE orders SET fulfillment_status=?, status=? WHERE id=?")
      ->execute([$newStatus, $legacy, $id]);
    setFlash('success', 'Order status updated.');
  }
  redirect($preserveQuery(['selected' => $id ?: null]));
}

$counts = ['all' => 0, 'pending' => 0, 'arranging' => 0, 'ready' => 0, 'complete' => 0];
foreach ($pdo->query("SELECT fulfillment_status, COUNT(*) AS n FROM orders GROUP BY fulfillment_status") as $row) {
  $counts[$row['fulfillment_status']] = (int)$row['n'];
  $counts['all'] += (int)$row['n'];
}

$where = [];
$params = [];
if ($tab !== 'all') {
  $where[] = "o.fulfillment_status = ?";
  $params[] = $tab;
}
if ($q !== '') {
  $where[] = "(o.order_number LIKE ? OR u.full_name LIKE ? OR EXISTS (
      SELECT 1 FROM order_items oi2 JOIN bouquets b2 ON b2.id=oi2.bouquet_id
      WHERE oi2.order_id=o.id AND b2.name LIKE ?))";
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
}
if (in_array($paymentFilter, ['paid', 'pending'], true)) {
  $where[] = "o.payment_status = ?";
  $params[] = $paymentFilter;
}
if ($dateFilter !== '' && strtotime($dateFilter) !== false) {
  $where[] = "o.pickup_date = ?";
  $params[] = $dateFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email, u.phone,
    (SELECT b.name FROM order_items oi JOIN bouquets b ON b.id=oi.bouquet_id WHERE oi.order_id=o.id ORDER BY oi.id LIMIT 1) AS bouquet_name,
    (SELECT COALESCE(SUM(oi.quantity),0) FROM order_items oi WHERE oi.order_id=o.id) AS bouquet_qty
  FROM orders o JOIN users u ON o.user_id=u.id
  $whereSql
  ORDER BY o.created_at DESC, o.id DESC");
$stmt->execute($params);
$orders = $stmt->fetchAll();
$filteredTotal = array_sum(array_map(fn($o) => (float)$o['total'], $orders));

$pickupToday = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE pickup_date=CURDATE()")->fetchColumn();
$awaiting = $counts['pending'] + $counts['arranging'];

$selectedId = (int)($_GET['selected'] ?? ($orders[0]['id'] ?? 0));
$selected = null;
$selectedItems = [];
if ($selectedId) {
  $stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email, u.phone FROM orders o JOIN users u ON o.user_id=u.id WHERE o.id=?");
  $stmt->execute([$selectedId]);
  $selected = $stmt->fetch();
  if ($selected) {
    $stmt = $pdo->prepare("SELECT oi.*, b.name, b.description, b.image FROM order_items oi JOIN bouquets b ON b.id=oi.bouquet_id WHERE oi.order_id=? ORDER BY oi.id");
    $stmt->execute([$selectedId]);
    $selectedItems = $stmt->fetchAll();
  }
}
$selectedFirstItem = $selectedItems[0] ?? null;
$selectedExtraItems = max(0, count($selectedItems) - 1);
$statusLabels = [
  'pending' => 'Pending',
  'arranging' => 'Arranging',
  'ready' => 'Ready for Pickup',
  'complete' => 'Order Complete',
];
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">A thoughtful delivery, every time</span>
    <h1>Customer orders</h1>
    <p class="desc">From the first stem to their doorstep. Keep every order beautifully on track.</p>
  </div>
  <div class="admin-date"><?php echo date('l, j F Y'); ?></div>
</div>

<div class="admin-container">
  <div class="stats-grid">
    <div class="stat-tile"><span>Total orders</span><strong><?php echo $counts['all']; ?></strong></div>
    <div class="stat-tile"><span>Pickup today</span><strong><?php echo $pickupToday; ?></strong></div>
    <div class="stat-tile"><span>Awaiting fulfillment</span><strong><?php echo $awaiting; ?></strong></div>
  </div>

  <div class="orders-layout">
    <section class="orders-panel">
      <div class="tabs-row">
        <?php foreach (['all' => 'All orders', 'pending' => 'Pending', 'arranging' => 'Arranging', 'ready' => 'Ready', 'complete' => 'Complete'] as $key => $label): ?>
          <a class="tab-link <?php echo $tab === $key ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($preserveQuery(['tab' => $key === 'all' ? null : $key, 'selected' => null])); ?>">
            <?php echo $label; ?> <span class="count"><?php echo $counts[$key]; ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <form class="orders-filters" method="get" action="orders.php">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        <div class="admin-search">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="search" name="q" placeholder="Search order ID, customer or bouquet" value="<?php echo htmlspecialchars($q); ?>">
        </div>
        <input type="date" name="date" class="form-control orders-date" value="<?php echo htmlspecialchars($dateFilter); ?>" aria-label="Filter by pickup date">
        <div class="select-wrap">
          <select name="payment" class="form-control" onchange="this.form.submit()" aria-label="Filter payment">
            <option value="all" <?php echo $paymentFilter === 'all' ? 'selected' : ''; ?>>All payments</option>
            <option value="paid" <?php echo $paymentFilter === 'paid' ? 'selected' : ''; ?>>Paid</option>
            <option value="pending" <?php echo $paymentFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
          </select>
        </div>
      </form>

      <div class="table-wrap">
        <table class="data-table orders-table">
          <thead>
            <tr>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Bouquet</th>
              <th>Delivery date</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Fulfillment</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $o): ?>
            <?php
              $rowUrl = $preserveQuery(['selected' => $o['id']]);
              $pillClass = $o['payment_status'] === 'paid' ? 'paid' : 'pending';
              $fulfillClass = $o['fulfillment_status'];
            ?>
            <tr class="<?php echo $selected && (int)$selected['id'] === (int)$o['id'] ? 'selected' : ''; ?>" onclick="window.location.href='<?php echo htmlspecialchars($rowUrl, ENT_QUOTES); ?>'">
              <td>
                <a class="order-id" href="<?php echo htmlspecialchars($rowUrl); ?>">#<?php echo htmlspecialchars($o['order_number']); ?></a>
                <div class="cell-muted"><?php echo $selected && (int)$selected['id'] === (int)$o['id'] ? 'Selected' : 'Online order'; ?></div>
              </td>
              <td>
                <span class="cell-strong"><?php echo htmlspecialchars($o['full_name']); ?></span>
                <div class="cell-muted"><?php echo date('j M, g:i a', strtotime($o['created_at'])); ?></div>
              </td>
              <td>
                <span class="cell-strong"><?php echo htmlspecialchars($o['bouquet_name'] ?? '—'); ?></span>
                <div class="cell-muted"><?php echo (int)$o['bouquet_qty']; ?> bouquet<?php echo (int)$o['bouquet_qty'] === 1 ? '' : 's'; ?></div>
              </td>
              <td>
                <span class="cell-strong"><?php echo $o['pickup_date'] ? date('j M Y', strtotime($o['pickup_date'])) : '—'; ?></span>
                <div class="cell-muted"><?php echo htmlspecialchars($o['pickup_time'] ?? ''); ?></div>
              </td>
              <td><span class="order-amount">₱<?php echo number_format((float)$o['total']); ?></span></td>
              <td><span class="status-pill <?php echo $pillClass; ?>"><?php echo $o['payment_status'] === 'paid' ? 'Paid' : 'Pending'; ?></span></td>
              <td><span class="status-badge <?php echo $fulfillClass; ?>"><?php echo $statusLabels[$o['fulfillment_status']] ?? 'Pending'; ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($orders)): ?>
            <tr><td colspan="7" class="table-empty">No orders match these filters.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="sample-note">
        <span>Showing <?php echo count($orders); ?> order<?php echo count($orders) === 1 ? '' : 's'; ?></span>
        <span>Total order value <strong>₱<?php echo number_format($filteredTotal); ?></strong> · PHP</span>
      </div>
    </section>

    <?php if ($selected): ?>
    <aside class="order-detail">
      <div class="order-detail-head">
        <div>
          <span class="order-detail-label">Selected order</span>
          <h2>Order #<?php echo htmlspecialchars($selected['order_number']); ?></h2>
          <p class="order-detail-meta"><?php echo htmlspecialchars($selected['full_name']); ?> · Placed <?php echo date('j M Y, g:i a', strtotime($selected['created_at'])); ?></p>
        </div>
        <div class="detail-head-actions">
          <span class="status-pill <?php echo htmlspecialchars($selected['fulfillment_status']); ?>"><?php echo $statusLabels[$selected['fulfillment_status']] ?? 'Pending'; ?></span>
          <a class="order-detail-close" href="<?php echo htmlspecialchars($preserveQuery(['selected' => null])); ?>" aria-label="Close order details">&times;</a>
        </div>
      </div>

      <?php if ($selectedFirstItem): ?>
      <div class="detail-product">
        <img src="../assets/images/<?php echo htmlspecialchars($selectedFirstItem['image']); ?>" alt="<?php echo htmlspecialchars($selectedFirstItem['name']); ?>">
        <div>
          <h3><?php echo htmlspecialchars($selectedFirstItem['name']); ?></h3>
          <p><?php echo (int)$selectedFirstItem['quantity']; ?> bouquet<?php echo (int)$selectedFirstItem['quantity'] === 1 ? '' : 's'; ?> × ₱<?php echo number_format((float)$selectedFirstItem['price']); ?><?php echo $selectedExtraItems ? ' · +' . $selectedExtraItems . ' more' : ''; ?></p>
          <p><?php echo htmlspecialchars($selectedFirstItem['description']); ?></p>
        </div>
      </div>
      <?php endif; ?>

      <div class="detail-payment">
        <span><?php echo htmlspecialchars($selected['payment_method']); ?> · <?php echo $selected['payment_status'] === 'paid' ? 'Paid' : 'Payment pending'; ?> <?php echo date('j M', strtotime($selected['created_at'])); ?></span>
        <span class="status-pill <?php echo $selected['payment_status'] === 'paid' ? 'paid' : 'pending'; ?>"><?php echo $selected['payment_status'] === 'paid' ? 'Paid' : 'Pending'; ?></span>
      </div>

      <div class="detail-lines">
        <div class="detail-line"><span>Bouquet subtotal</span><strong>₱<?php echo number_format((float)$selected['subtotal']); ?></strong></div>
        <div class="detail-line"><span>Customization</span><strong>₱<?php echo number_format((float)$selected['customization']); ?></strong></div>
      </div>
      <div class="detail-total"><span>Order total · PHP</span><strong>₱<?php echo number_format((float)$selected['total']); ?></strong></div>

      <form method="post" class="customization-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="customization">
        <input type="hidden" name="id" value="<?php echo $selected['id']; ?>">
        <div class="customization-label">Set customization price</div>
        <div class="customization-box">
          <div class="currency-box">
            <span>₱</span>
            <input type="number" name="customization" step="0.01" min="0" class="form-control" value="<?php echo number_format((float)$selected['customization'], 2, '.', ''); ?>" aria-label="Customization price">
          </div>
          <button type="submit" class="btn-save">Save price</button>
        </div>
        <p class="customization-help">Sets the bouquet price for this order. The order total updates once saved.</p>
      </form>

      <hr class="detail-divider">

      <div class="detail-section-title">Recipient &amp; pickup</div>
      <div class="pickup-box">
        <i class="fa-regular fa-calendar"></i>
        <span><?php echo $selected['pickup_date'] ? date('l, j M', strtotime($selected['pickup_date'])) : 'Pickup date to confirm'; ?> · <?php echo htmlspecialchars($selected['pickup_time']); ?></span>
      </div>
      <div class="detail-kv">
        <div>Flower: <strong><?php echo htmlspecialchars($selected['recipient_name'] ?: $selected['full_name']); ?></strong></div>
        <div>Name: <?php echo htmlspecialchars($selected['full_name']); ?></div>
        <div>Contact No: <?php echo htmlspecialchars($selected['phone'] ?: '—'); ?></div>
      </div>

      <hr class="detail-divider">

      <div class="detail-section-title">The finishing touches</div>
      <?php if (!empty($selected['florist_note'])): ?>
      <div class="detail-kv" style="margin-top:0;">
        <strong>For the florist</strong><br>
        <?php echo nl2br(htmlspecialchars($selected['florist_note'])); ?>
      </div>
      <?php endif; ?>
      <?php if (!empty($selected['gift_note'])): ?>
      <div class="gift-label">Handwritten gift note · Included</div>
      <div class="gift-note-box">“<?php echo nl2br(htmlspecialchars($selected['gift_note'])); ?>”</div>
      <?php endif; ?>

      <hr class="detail-divider">

      <div class="detail-section-title">Manage fulfillment</div>
      <form method="post" class="manage-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="status">
        <input type="hidden" name="id" value="<?php echo $selected['id']; ?>">
        <div class="select-wrap">
          <select name="fulfillment_status" class="form-control" onchange="this.form.submit()" aria-label="Fulfillment status">
            <option value="pending" <?php echo $selected['fulfillment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="arranging" <?php echo $selected['fulfillment_status'] === 'arranging' ? 'selected' : ''; ?>>Arranging</option>
            <option value="ready" <?php echo $selected['fulfillment_status'] === 'ready' ? 'selected' : ''; ?>>Ready for pickup</option>
            <option value="complete" <?php echo $selected['fulfillment_status'] === 'complete' ? 'selected' : ''; ?>>Order complete</option>
          </select>
        </div>
        <button type="submit" name="make_ready" value="1" class="btn btn-primary mark-ready"><i class="fa-solid fa-check"></i> Mark as ready</button>
      </form>
      <a href="inbox.php?thread=<?php echo (int)$selected['user_id']; ?>" class="btn btn-outline-green contact-buyer"><i class="fa-regular fa-envelope"></i> Contact buyer</a>
    </aside>
    <?php endif; ?>
  </div>
</div>

<?php require_once 'footer.php'; ?>
