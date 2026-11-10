<?php
$pageTitle = 'My Account';
require_once 'includes/header.php';
requireLogin();

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token.';
  } else {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';

    if (!$fullName || !$email) {
      $error = 'Name and email are required.';
    } elseif ($currentPassword || $newPassword) {
      if (!$currentPassword || !$newPassword) {
        $error = 'Enter both your current and new password to change it.';
      } elseif (!password_verify($currentPassword, $user['password'])) {
        $error = 'Current password is incorrect.';
      } elseif (strlen($newPassword) < 6) {
        $error = 'New password must be at least 6 characters.';
      } else {
        $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?");
        $upd->execute([$fullName, $email, $phone, $address, $_SESSION['user_id']]);
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $pwd = $pdo->prepare("UPDATE users SET password=? WHERE id=?");
        $pwd->execute([$hash, $_SESSION['user_id']]);
        $_SESSION['user_full_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $success = 'Profile and password updated.';
      }
    } else {
      $upd = $pdo->prepare("UPDATE users SET full_name=?, email=?, phone=?, address=? WHERE id=?");
      $upd->execute([$fullName, $email, $phone, $address, $_SESSION['user_id']]);
      $_SESSION['user_full_name'] = $fullName;
      $_SESSION['user_email'] = $email;
      $success = 'Profile updated.';
    }
  }
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
  $stmt->execute([$_SESSION['user_id']]);
  $user = $stmt->fetch();
}

$nameParts = preg_split('/\s+/', trim($user['full_name'] ?? 'Customer'));
$initials = mb_strtoupper(mb_substr($nameParts[0] ?? 'C', 0, 1) . (count($nameParts) > 1 ? mb_substr(end($nameParts), 0, 1) : ''));
$orderCount = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE user_id=" . (int)$_SESSION['user_id'])->fetchColumn();
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to the shop</a>
    <a href="orders.php" class="back-link"><i class="fa-solid fa-box-open"></i> My orders</a>
  </div>

  <div class="page-intro">
    <span class="eyebrow">Your account</span>
    <h1>Account details</h1>
    <p>Keep your details current so every pickup and thoughtful note finds its way to you.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
  <?php endif; ?>

  <div class="profile-layout">
    <form method="post" class="profile-main">
      <section class="checkout-card">
        <h2>Your details</h2>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="full_name">Full name</label>
            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="phone">Mobile number</label>
            <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" placeholder="09XX XXX XXXX">
          </div>
          <div class="form-group">
            <label class="form-label" for="address">Pickup address</label>
            <input type="text" id="address" name="address" class="form-control" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>" placeholder="Where should we meet you?">
          </div>
        </div>
      </section>

      <section class="checkout-card">
        <div class="checkout-card-head">
          <h2>Password</h2>
          <span>Only if you'd like to change it</span>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••" autocomplete="current-password">
          </div>
          <div class="form-group">
            <label class="form-label" for="new_password">New password</label>
            <input type="password" id="new_password" name="new_password" class="form-control" placeholder="At least 6 characters" autocomplete="new-password">
          </div>
        </div>
        <p class="checkout-help">Leave both blank to keep your current password.</p>
      </section>

      <div class="profile-actions">
        <button type="submit" class="btn btn-primary">Save changes <i class="fa-solid fa-arrow-right"></i></button>
        <a href="index.php" class="btn btn-secondary">Back to the shop</a>
      </div>
    </form>

    <aside class="profile-aside">
      <div class="profile-card">
        <span class="profile-avatar"><?php echo htmlspecialchars($initials); ?></span>
        <h2><?php echo htmlspecialchars($user['full_name'] ?? 'Customer'); ?></h2>
        <p><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
        <div class="profile-stat">
          <span>Orders placed</span>
          <strong><?php echo $orderCount; ?></strong>
        </div>
        <div class="profile-links">
          <a href="orders.php">My orders <i class="fa-solid fa-arrow-right"></i></a>
          <a href="contact.php">Message the shop <i class="fa-solid fa-arrow-right"></i></a>
          <a href="logout.php">Sign out <i class="fa-solid fa-arrow-right-from-bracket"></i></a>
        </div>
      </div>

      <div class="info-card">
        <h3>Made for you.</h3>
        <p>Your details travel with every order, so your bouquets and notes arrive exactly as planned.</p>
      </div>
    </aside>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
