<?php
$pageTitle = 'Customers';
$active = 'customers';
require_once 'header.php';

$q = trim($_GET['q'] ?? '');

$where = "u.role='customer'";
$params = [];
if ($q !== '') {
  $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR EXISTS (
      SELECT 1 FROM orders oq WHERE oq.user_id=u.id AND oq.order_number LIKE ?))";
  $params = ['%' . $q . '%', '%' . $q . '%', '%' . $q . '%'];
}

$stmt = $pdo->prepare("SELECT u.*,
    COUNT(DISTINCT o.id) AS order_count,
    MAX(o.created_at) AS last_order,
    COALESCE(SUM(o.total), 0) AS total_spent,
    (SELECT b.name FROM orders o2
      JOIN order_items oi ON oi.order_id=o2.id
      JOIN bouquets b ON b.id=oi.bouquet_id
      WHERE o2.user_id=u.id
      ORDER BY o2.created_at DESC, o2.id DESC LIMIT 1) AS last_bouquet
  FROM users u
  LEFT JOIN orders o ON o.user_id=u.id AND o.status <> 'cancelled'
  WHERE $where
  GROUP BY u.id
  ORDER BY MAX(o.created_at) DESC, u.created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

$initials = function (string $name): string {
  $parts = preg_split('/\s+/', trim($name));
  $first = mb_substr($parts[0] ?? '', 0, 1);
  $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';
  return mb_strtoupper($first . $last);
};
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">The people you bloom for</span>
    <h1>Customers</h1>
    <p class="desc">See who has ordered, what they loved, and message them directly.</p>
  </div>
</div>

<div class="admin-container">
  <form class="admin-customer-search" method="get" action="customers.php">
    <div class="admin-search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" placeholder="Search name or order ID" value="<?php echo htmlspecialchars($q); ?>">
    </div>
  </form>

  <div class="customers-panel">
    <div class="table-wrap">
      <table class="data-table customers-table">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Last bouquet</th>
            <th>Orders</th>
            <th>Last order</th>
            <th>Total spent</th>
            <th class="message-col">Message</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($customers as $c): ?>
          <tr>
            <td>
              <div class="customer-cell">
                <span class="avatar-circle"><?php echo htmlspecialchars($initials($c['full_name'])); ?></span>
                <div>
                  <div class="customer-name"><?php echo htmlspecialchars($c['full_name']); ?></div>
                </div>
              </div>
            </td>
            <td class="cell-strong"><?php echo htmlspecialchars($c['last_bouquet'] ?? '—'); ?></td>
            <td class="cell-strong"><?php echo (int)$c['order_count']; ?></td>
            <td class="cell-strong"><?php echo $c['last_order'] ? date('j M Y', strtotime($c['last_order'])) : '—'; ?></td>
            <td class="cell-strong">₱<?php echo number_format((float)$c['total_spent']); ?></td>
            <td class="message-col">
              <a class="msg-link" href="inbox.php?thread=<?php echo (int)$c['id']; ?>" aria-label="Message <?php echo htmlspecialchars($c['full_name']); ?>">
                <i class="fa-regular fa-message"></i>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($customers)): ?>
          <tr><td colspan="6" class="table-empty">No customers match your search.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once 'footer.php'; ?>
