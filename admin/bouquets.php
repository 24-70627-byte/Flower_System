<?php
$pageTitle = 'Bouquets';
$active = 'bouquets';
require_once 'header.php';

// Handle add/edit/delete/toggle via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $price = (float)($_POST['price'] ?? 0);
  $image = trim($_POST['image'] ?? 'default-bouquet.jpg');
  $category = trim($_POST['category'] ?? 'bouquet');
  $stock = (int)($_POST['stock'] ?? 0);
  $featured = isset($_POST['featured']) ? 1 : 0;
  $isNew = isset($_POST['is_new']) ? 1 : 0;
  $available = isset($_POST['available']) ? 1 : 0;

  if ($action === 'add') {
    if ($name && $price >= 0) {
      $pdo->prepare("INSERT INTO bouquets (name,description,price,image,category,stock,featured,is_new,status) VALUES (?,?,?,?,?,?,?,?,?)")
        ->execute([$name, $desc, $price, $image, $category, $stock, $featured, $isNew, $available ? 'active' : 'inactive']);
      setFlash('success', 'Bouquet added.');
    }
  } elseif ($action === 'update') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id && $name && $price >= 0) {
      $pdo->prepare("UPDATE bouquets SET name=?,description=?,price=?,image=?,category=?,stock=?,featured=?,is_new=?,status=? WHERE id=?")
        ->execute([$name, $desc, $price, $image, $category, $stock, $featured, $isNew, $available ? 'active' : 'inactive', $id]);
      setFlash('success', 'Bouquet updated.');
    }
  } elseif ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
      $pdo->prepare("DELETE FROM bouquets WHERE id=?")->execute([$id]);
      setFlash('success', 'Bouquet deleted.');
    }
  } elseif ($action === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    $available = (int)($_POST['available'] ?? 1);
    if ($id) {
      $pdo->prepare("UPDATE bouquets SET status=? WHERE id=?")->execute([$available ? 'active' : 'inactive', $id]);
    }
  }
  redirect('bouquets.php');
}

// Edit mode
$edit = null;
if (isset($_GET['edit'])) {
  $stmt = $pdo->prepare("SELECT * FROM bouquets WHERE id=?");
  $stmt->execute([$_GET['edit']]);
  $edit = $stmt->fetch();
}

$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? 'all';
$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($q !== '') {
  $where[] = "(name LIKE ? OR description LIKE ?)";
  $params[] = '%' . $q . '%';
  $params[] = '%' . $q . '%';
}
if (in_array($statusFilter, ['active', 'inactive'], true)) {
  $where[] = "status = ?";
  $params[] = $statusFilter;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM bouquets $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $perPage;
}

$stmt = $pdo->prepare("SELECT * FROM bouquets $whereSql ORDER BY featured DESC, created_at ASC, id ASC LIMIT ? OFFSET ?");
$bindIndex = 1;
foreach ($params as $param) {
  $stmt->bindValue($bindIndex++, $param, PDO::PARAM_STR);
}
$stmt->bindValue($bindIndex++, $perPage, PDO::PARAM_INT);
$stmt->bindValue($bindIndex, $offset, PDO::PARAM_INT);
$stmt->execute();
$bouquets = $stmt->fetchAll();

$queryBase = ['q' => $q, 'status' => $statusFilter];
$pageUrl = function (int $target) use ($queryBase): string {
  $query = array_filter($queryBase, fn($v) => $v !== '' && $v !== 'all');
  $query['page'] = $target;
  return 'bouquets.php?' . http_build_query($query);
};
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">Your catalog, your way</span>
    <h1>Bouquets</h1>
    <p class="desc">Set what you sell, what it starts at, and what is available today.</p>
  </div>
</div>

<div class="admin-container">
  <form class="admin-toolbar" method="get" action="bouquets.php">
    <div class="admin-search">
      <i class="fa-solid fa-magnifying-glass"></i>
      <input type="search" name="q" placeholder="Search bouquet name" value="<?php echo htmlspecialchars($q); ?>">
    </div>
    <div class="select-wrap">
      <select name="status" class="form-control" onchange="this.form.submit()" aria-label="Filter bouquets">
        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All bouquets</option>
        <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Available</option>
        <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Sold out</option>
      </select>
    </div>
    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#bouquetModal">+ Add bouquet</button>
  </form>

  <div class="admin-grid">
    <?php foreach ($bouquets as $b): ?>
    <article class="admin-card">
      <div class="admin-card-media">
        <img src="../assets/images/<?php echo htmlspecialchars($b['image']); ?>" alt="<?php echo htmlspecialchars($b['name']); ?>">
      </div>
      <div class="admin-card-body">
        <h3><?php echo htmlspecialchars($b['name']); ?></h3>
        <div class="admin-card-price">Starting at <span>₱<?php echo number_format((float)$b['price']); ?></span></div>
        <div class="admin-card-note">Made to order</div>
        <hr class="admin-card-divider">

        <div class="availability-row">
          <div class="availability-left">
            <label class="switch">
              <input type="checkbox" <?php echo $b['status'] === 'active' ? 'checked' : ''; ?>
                onchange="setAvailability(<?php echo $b['id']; ?>, this.checked)">
              <span class="slider"></span>
            </label>
            <span class="<?php echo $b['status'] === 'active' ? 'on' : ''; ?>">Available</span>
          </div>
          <a href="bouquets.php?edit=<?php echo $b['id']; ?><?php echo $q !== '' ? '&q=' . urlencode($q) : ''; ?>" class="btn-edit">Edit</a>
        </div>

        <div class="sold-row">
          <label class="switch">
            <input type="checkbox" <?php echo $b['status'] === 'inactive' ? 'checked' : ''; ?>
              onchange="setAvailability(<?php echo $b['id']; ?>, this.checked ? 0 : 1)">
            <span class="slider"></span>
          </label>
          <span class="<?php echo $b['status'] === 'inactive' ? 'on' : ''; ?>">Sold out</span>
        </div>
      </div>
    </article>
    <?php endforeach; ?>

    <?php if (empty($bouquets)): ?>
    <div class="admin-empty">
      <p>No bouquets found.</p>
      <button class="btn-add" data-bs-toggle="modal" data-bs-target="#bouquetModal">+ Add bouquet</button>
    </div>
    <?php endif; ?>
  </div>

  <?php if ($totalPages > 1): ?>
  <div class="admin-pagination">
    <a class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>" href="<?php echo $page <= 1 ? '#' : htmlspecialchars($pageUrl($page - 1)); ?>" aria-label="Previous page">‹</a>
    <div class="page-info"><?php echo $page; ?> / <?php echo $totalPages; ?></div>
    <a class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>" href="<?php echo $page >= $totalPages ? '#' : htmlspecialchars($pageUrl($page + 1)); ?>" aria-label="Next page">›</a>
  </div>
  <?php endif; ?>
</div>

<!-- Add/Edit Bouquet Modal -->
<div class="modal fade" id="bouquetModal" tabindex="-1" aria-labelledby="bouquetModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <form id="bouquetForm" method="post">
        <?php echo csrfField(); ?>
        <div class="modal-header">
          <h5 class="modal-title" id="bouquetModalTitle"><?php echo $edit ? 'Edit bouquet' : 'Add bouquet'; ?></h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="action" value="<?php echo $edit ? 'update' : 'add'; ?>">
          <?php if ($edit): ?><input type="hidden" name="id" value="<?php echo $edit['id']; ?>"><?php endif; ?>

          <div class="form-group">
            <label class="form-label">Name</label>
            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit['name'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit['description'] ?? ''); ?></textarea>
          </div>
          <div class="form-grid-2">
            <div class="form-group">
              <label class="form-label">Price (₱)</label>
              <input type="number" name="price" step="0.01" min="0" class="form-control" value="<?php echo htmlspecialchars($edit['price'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Image filename</label>
              <input type="text" name="image" class="form-control" value="<?php echo htmlspecialchars($edit['image'] ?? 'default-bouquet.jpg'); ?>" placeholder="lilac-rose-ribbon.jpg">
            </div>
            <div class="form-group">
              <label class="form-label">Category</label>
              <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit['category'] ?? 'bouquet'); ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Stock</label>
              <input type="number" name="stock" min="0" class="form-control" value="<?php echo htmlspecialchars($edit['stock'] ?? '0'); ?>">
            </div>
          </div>

          <div class="form-check-row">
            <label><input type="checkbox" name="featured" <?php echo !empty($edit['featured']) ? 'checked' : ''; ?>> Bestseller</label>
            <label><input type="checkbox" name="is_new" <?php echo !empty($edit['is_new']) ? 'checked' : ''; ?>> New arrival</label>
            <label><input type="checkbox" name="available" <?php echo (empty($edit) || ($edit['status'] ?? 'active') === 'active') ? 'checked' : ''; ?>> Available</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn-cancel" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn-save"><?php echo $edit ? 'Save changes' : 'Add bouquet'; ?></button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function setAvailability(id, available) {
  var form = document.createElement('form');
  form.method = 'post';
  form.innerHTML = '<?php echo csrfField(); ?>' +
    '<input type="hidden" name="action" value="toggle">' +
    '<input type="hidden" name="id" value="' + id + '">' +
    '<input type="hidden" name="available" value="' + (available ? 1 : 0) + '">';
  document.body.appendChild(form);
  form.submit();
}
<?php if ($edit): ?>
document.addEventListener('DOMContentLoaded', function () {
  new bootstrap.Modal(document.getElementById('bouquetModal')).show();
});
<?php endif; ?>
</script>

<?php require_once 'footer.php'; ?>
