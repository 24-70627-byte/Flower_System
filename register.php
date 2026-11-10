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
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!$fullName || !$email || !$password) {
      $error = 'All fields are required.';
    } elseif ($password !== $confirm) {
      $error = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
      $error = 'Password must be at least 6 characters.';
    } else {
      $check = $pdo->prepare("SELECT id FROM users WHERE email=?");
      $check->execute([$email]);
      if ($check->fetch()) {
        $error = 'Email already registered.';
      } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $ins = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?,?,?, 'customer')");
        if ($ins->execute([$fullName, $email, $hash])) {
          setFlash('success', 'Account created successfully! Please log in.');
          redirect('login.php');
        } else {
          $error = 'Registration failed. Please try again.';
        }
      }
    }
  }
}

$pageTitle = 'Create Account';
require_once 'includes/header.php';
?>

<section class="auth-page">
  <div class="auth-media">
    <img src="assets/images/hero.jpg" alt="Blush garden roses wrapped in cream paper">
  </div>

  <div class="auth-panel">
    <span class="eyebrow">Account</span>
    <h1>Create account</h1>
    <p class="auth-copy">Join Nicole's shop to save favourites, revisit past orders, and make every thoughtful gesture a little easier.</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post">
      <?php echo csrfField(); ?>
      <div class="form-group">
        <label class="form-label" for="full_name">Full name</label>
        <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required placeholder="Your full name">
      </div>
      <div class="form-group">
        <label class="form-label" for="email">Email address</label>
        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required placeholder="you@example.com">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <div class="input-wrap">
          <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters">
          <button type="button" class="toggle-password" data-toggle-password="password" aria-label="Show password">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="confirm_password">Confirm password</label>
        <div class="input-wrap">
          <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Repeat your password">
          <button type="button" class="toggle-password" data-toggle-password="confirm_password" aria-label="Show password">
            <i class="fa-regular fa-eye"></i>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary btn-block">Create account <i class="fa-solid fa-arrow-right"></i></button>
    </form>

    <hr class="auth-divider">
    <p class="auth-alt">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
