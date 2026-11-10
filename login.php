<?php
require_once 'config.php';

if (isLoggedIn()) {
  redirect('index.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token.';
  } else {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email=? AND status='active'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['user_email'] = $user['email'];
      $_SESSION['user_full_name'] = $user['full_name'];
      $_SESSION['user_role'] = $user['role'];
      session_regenerate_id(true);

      $redirect = $_SESSION['redirect_after_login'] ?? 'index.php';
      unset($_SESSION['redirect_after_login']);
      redirect($redirect);
    } else {
      $error = 'Invalid email or password.';
    }
  }
}

$pageTitle = 'Welcome';
require_once 'includes/header.php';
?>

<section class="auth-page">
  <div class="auth-media">
    <img src="assets/images/hero.jpg" alt="Blush garden roses wrapped in cream paper">
  </div>

  <div class="auth-panel">
    <span class="eyebrow">Account</span>
    <h1>Welcome</h1>
    <p class="auth-copy">Sign in for your favourite flowers, thoughtful gifts, and all the little moments in between.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrfField(); ?>
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="you@example.com">
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
    <p class="auth-alt">New to Nicole's shop? <a href="register.php">Create an account</a></p>
    <p class="auth-note">Save your favourites. Revisit past orders.<br>Make the next thoughtful gesture a little easier.</p>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
