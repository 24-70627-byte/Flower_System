<?php
$pageTitle = 'Pick Your Bouquet';
require_once 'includes/header.php';

$product = null;
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("SELECT * FROM bouquets WHERE id=? AND status='active'");
  $stmt->execute([$_GET['id']]);
  $product = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
?>
<div class="page-shell">

<?php if ($product): ?>
  <div class="page-topline">
    <a href="products.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to bouquets</a>
  </div>

  <section class="product-detail">
    <div class="product-detail-media">
      <img src="assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
    </div>
    <div class="product-detail-info">
      <span class="eyebrow">The flower counter</span>
      <h1><?php echo htmlspecialchars($product['name']); ?></h1>
      <p class="detail-desc"><?php echo htmlspecialchars($product['description']); ?></p>
      <div class="detail-price">₱<?php echo number_format((float)$product['price']); ?></div>
      <?php if ($product['status'] === 'active' && (int)$product['stock'] > 0): ?>
      <form action="cart_action.php" method="post" class="detail-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="bouquet_id" value="<?php echo $product['id']; ?>">
        <input type="number" name="quantity" value="1" min="1" max="<?php echo max(1, (int)$product['stock']); ?>" class="form-control" aria-label="Quantity">
        <button type="submit" class="btn btn-primary">Add to cart</button>
      </form>
      <?php else: ?>
        <p class="sold-note">Currently sold out</p>
      <?php endif; ?>
      <a href="products.php" class="btn btn-secondary">Continue shopping</a>
    </div>
  </section>

<?php else: ?>
  <div class="catalog-head">
    <div class="catalog-copy">
      <span class="eyebrow">The flower counter</span>
      <h1>Pick your bouquet</h1>
      <p>Designed around the most beautiful stems at market this week. Each bouquet is unique, but the feeling remains.</p>
    </div>
    <a class="shop-all" href="products.php">Shop all flowers <i class="fa-solid fa-arrow-right"></i></a>
  </div>

  <?php if ($q !== ''): ?>
    <p class="catalog-search-note">Showing results for “<?php echo htmlspecialchars($q); ?>” · <a href="products.php">Clear search</a></p>
  <?php endif; ?>

  <?php
  if ($q !== '') {
    $stmt = $pdo->prepare("SELECT * FROM bouquets WHERE status='active' AND (name LIKE ? OR description LIKE ?) ORDER BY created_at ASC, id ASC");
    $stmt->execute(['%' . $q . '%', '%' . $q . '%']);
  } else {
    $stmt = $pdo->query("SELECT * FROM bouquets WHERE status='active' ORDER BY created_at ASC, id ASC");
  }
  $bouquets = $stmt->fetchAll();
  ?>

  <?php if (empty($bouquets)): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-seedling"></i>
      <h2>No bouquets found</h2>
      <a href="products.php" class="btn btn-primary">Shop all flowers</a>
    </div>
  <?php else: ?>
  <div class="bouquet-grid">
    <?php foreach ($bouquets as $row): ?>
    <article class="bouquet-card">
      <div class="bouquet-media">
        <a href="products.php?id=<?php echo $row['id']; ?>" aria-label="<?php echo htmlspecialchars($row['name']); ?>">
          <img src="assets/images/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>">
        </a>
        <?php if (!empty($row['featured'])): ?>
          <span class="badge-pill">Bestseller</span>
        <?php elseif (!empty($row['is_new'])): ?>
          <span class="badge-pill new">New</span>
        <?php endif; ?>
        <?php if ($row['status'] === 'active' && (int)$row['stock'] > 0): ?>
        <form action="cart_action.php" method="post">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="bouquet_id" value="<?php echo $row['id']; ?>">
          <input type="hidden" name="quantity" value="1">
          <button type="submit" class="cart-orb" aria-label="Add <?php echo htmlspecialchars($row['name']); ?> to cart">
            <i class="fa-solid fa-bag-shopping"></i>
          </button>
        </form>
        <?php else: ?>
          <span class="cart-orb" aria-hidden="true"><i class="fa-solid fa-ban"></i></span>
        <?php endif; ?>
      </div>
      <div class="bouquet-title-row">
        <h3><a href="products.php?id=<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['name']); ?></a></h3>
        <span class="bouquet-price">₱<?php echo number_format((float)$row['price']); ?></span>
      </div>
      <p class="bouquet-desc"><?php echo htmlspecialchars($row['description']); ?></p>
    </article>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>
