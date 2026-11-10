<?php
require_once __DIR__ . '/../config.php';
$pageTitle = isset($pageTitle) ? $pageTitle : SITE_NAME;
$flashError = getFlash('error');
$flashSuccess = getFlash('success');
$cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
$isOwner = !empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner';
$headerAuthOnly = isset($headerAuthOnly) ? $headerAuthOnly : false;
$accountHref = isLoggedIn() ? ($isOwner ? 'admin/dashboard.php' : 'profile.php') : 'login.php';
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
  <link href="assets/css/style.css" rel="stylesheet">
</head>
<body class="site-body">

<header class="site-header">
  <div class="header-inner">
    <div class="header-left"></div>

    <a class="wordmark" href="index.php" aria-label="<?php echo htmlspecialchars(SITE_NAME); ?> home">
      <span class="line-1">Nicole's</span>
      <span class="line-2">Flowers &amp; Greens</span>
    </a>

    <div class="header-icons">
      <button type="button" class="header-icon header-search" id="searchToggle" aria-label="Search">
        <i class="fa-solid fa-magnifying-glass"></i>
      </button>
      <a class="header-icon" href="<?php echo $accountHref; ?>" aria-label="Account">
        <span class="account-orb"><i class="fa-regular fa-user"></i></span>
      </a>
      <?php if (!$headerAuthOnly): ?>
        <a class="header-cart" href="cart.php" aria-label="Cart">
          <i class="fa-solid fa-bag-shopping"></i>
          <span class="cart-count">(<?php echo (int)$cartCount; ?>)</span>
        </a>
        <a class="header-inbox" href="contact.php">
          <i class="fa-regular fa-envelope"></i>
          <span>Inbox</span>
        </a>
      <?php endif; ?>
    </div>
  </div>

  <div class="search-drawer" id="searchDrawer">
    <form action="products.php" method="get">
      <input type="search" name="q" class="form-control" placeholder="Search bouquets" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
      <button type="submit" class="btn btn-primary btn-sm">Search</button>
    </form>
  </div>
</header>

<?php if ($flashError): ?>
<div class="page-shell" style="padding-top:18px;">
  <div class="alert alert-error"><?php echo htmlspecialchars($flashError); ?></div>
</div>
<?php endif; ?>
<?php if ($flashSuccess): ?>
<div class="page-shell" style="padding-top:18px;">
  <div class="alert alert-success"><?php echo htmlspecialchars($flashSuccess); ?></div>
</div>
<?php endif; ?>

<main>
