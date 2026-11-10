<?php
require_once 'config.php';

if (!empty($_SESSION['user_role']) && $_SESSION['user_role'] === 'owner') {
  redirect('admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token.';
  } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND role='owner' AND status='active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_full_name'] = $user['full_name'];
      $_SESSION['user_role'] = $user['role'];
      session_regenerate_id(true);
      redirect('admin/dashboard.php');
    } else {
      $error = 'Invalid email or password.';
    }
  }
}

$pageTitle = 'Owner Login';
$headerAuthOnly = true;
require_once 'includes/header.php';
?>

<section class="auth-page">
  <div class="auth-media">
    <img src="assets/images/hero.jpg" alt="Blush garden roses wrapped in cream paper">
  </div>

  <div class="auth-panel">
    <span class="eyebrow">Owner access</span>
    <h1>Owner login</h1>
    <p class="auth-copy">Sign in to manage customer orders, track fulfillment, and keep your shop running smoothly.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrfField(); ?>
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="nicole@flowershop.com">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" class="form-control" required placeholder="Enter your password">
          <button type="button" class="toggle-password" data-toggle-password="password" aria-label="Show password">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Sign in <i class="fa-solid fa-arrow-right"></i></button>
    </form>

    <hr class="auth-divider">
    <p class="auth-alt">Forgot your password? <a href="contact.php">Reset password</a></p>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
