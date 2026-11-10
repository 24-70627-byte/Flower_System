<?php
require_once __DIR__ . '/../config.php';
requireOwner();
$pageTitle = isset($pageTitle) ? $pageTitle : 'Admin';
$active = isset($active) ? $active : 'bouquets';

$orderCount = (int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE sender='customer' AND is_read=0")->fetchColumn();
$ownerName = $_SESSION['user_full_name'] ?? 'Owner';
$ownerFirst = preg_split('/\s+/', trim($ownerName))[0] ?: 'Owner';
$ownerInitial = strtoupper(mb_substr($ownerName, 0, 1));

$navItems = [
  'orders' => ['orders.php', 'fa-regular fa-clipboard', 'Orders', $orderCount],
  'bouquets' => ['bouquets.php', 'fa-solid fa-seedling', 'Bouquets', null],
  'customers' => ['customers.php', 'fa-regular fa-user', 'Customers', null],
  'settings' => ['settings.php', 'fa-solid fa-gear', 'Shop settings', null],
  'inbox' => ['inbox.php', 'fa-regular fa-envelope', 'Inbox', $unreadCount ?: null],
  'dashboard' => ['dashboard.php', 'fa-solid fa-chart-simple', 'Overview', null],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> | <?php echo SITE_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&display=swap" rel="stylesheet">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="admin-body">
<div class="admin-shell">
  <div class="admin-top-strip"></div>

  <header class="admin-topbar">
    <div class="admin-topbar-inner">
      <a href="../index.php" class="admin-view-store"><i class="fa-solid fa-arrow-up-right-from-square"></i> View storefront</a>

      <a href="orders.php" class="admin-logo" aria-label="Nicole's Flowers and Greens admin">
        <span class="line-1">Nicole's</span>
        <span class="line-2">Flowers &amp; Greens</span>
      </a>

      <div class="admin-owner">
        <span class="avatar"><?php echo htmlspecialchars($ownerInitial); ?></span>
        <span class="owner-name"><?php echo htmlspecialchars($ownerFirst); ?></span>
        <span class="owner-role">· Owner</span>
        <i class="fa-solid fa-chevron-down"></i>
      </div>
    </div>
  </header>

  <nav class="admin-navbar" aria-label="Admin navigation">
    <div class="admin-navbar-inner">
      <div class="admin-tabs">
        <?php foreach ($navItems as $key => [$href, $icon, $label, $count]): ?>
          <?php if ($key === 'dashboard') continue; ?>
          <a href="<?php echo $href; ?>" class="admin-tab <?php echo $active === $key ? 'active' : ''; ?>">
            <i class="<?php echo $icon; ?>"></i>
            <?php echo htmlspecialchars($label); ?>
            <?php if ($count !== null): ?><span class="tab-count"><?php echo (int)$count; ?></span><?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
      <a href="../logout.php" class="admin-signout"><i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out</a>
    </div>
  </nav>

  <main class="admin-main">
    <?php
    $flashError = getFlash('error');
    $flashSuccess = getFlash('success');
    if ($flashError): ?>
      <div class="admin-container" style="padding-top:18px;">
        <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
      </div>
    <?php endif; ?>
    <?php if ($flashSuccess): ?>
      <div class="admin-container" style="padding-top:18px;">
        <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
      </div>
    <?php endif; ?>
