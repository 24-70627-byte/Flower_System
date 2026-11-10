<?php
$pageTitle = 'Contact';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
  $settings[$row['key']] = $row['value'];
}

$currentUser = null;
if (isLoggedIn()) {
  $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
  $stmt->execute([$_SESSION['user_id']]);
  $currentUser = $stmt->fetch();
}

$aboutOrder = null;
if (!empty($_GET['order'])) {
  $stmt = $pdo->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
  $stmt->execute([(int)$_GET['order'], (int)($_SESSION['user_id'] ?? 0)]);
  $aboutOrder = $stmt->fetch();
}

$form = [
  'name' => $currentUser['full_name'] ?? '',
  'email' => $currentUser['email'] ?? '',
  'subject' => $aboutOrder ? 'Order #' . $aboutOrder['order_number'] : '',
  'message' => '',
];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $form = [
    'name' => trim($_POST['name'] ?? ''),
    'email' => trim($_POST['email'] ?? ''),
    'subject' => trim($_POST['subject'] ?? ''),
    'message' => trim($_POST['message'] ?? ''),
  ];
  $postedOrderId = (int)($_POST['order_id'] ?? 0);
  $validOrderId = 0;
  if ($postedOrderId && isLoggedIn()) {
    $stmt = $pdo->prepare("SELECT id FROM orders WHERE id=? AND user_id=?");
    $stmt->execute([$postedOrderId, $_SESSION['user_id']]);
    $validOrderId = (int)($stmt->fetchColumn() ?: 0);
  }

  if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    $error = 'Invalid security token.';
  } elseif (!$form['name'] || !$form['email'] || !$form['message']) {
    $error = 'Please fill in your name, email, and message.';
  } elseif (!filter_var($form['email'], FILTER_VALIDATE_EMAIL)) {
    $error = 'Please enter a valid email address.';
  } else {
    $pdo->prepare("INSERT INTO messages (user_id, order_id, name, email, subject, message) VALUES (?,?,?,?,?,?)")
      ->execute([
        $currentUser['id'] ?? null,
        $validOrderId ?: null,
        $form['name'],
        $form['email'],
        $form['subject'] ?: null,
        $form['message'],
      ]);
    setFlash('success', 'Message sent. We will be in touch shortly.');
    redirect('contact.php');
  }
}
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="index.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to the shop</a>
    <?php if (isLoggedIn()): ?>
      <a href="orders.php" class="back-link"><i class="fa-solid fa-box-open"></i> My orders</a>
    <?php endif; ?>
  </div>

  <div class="page-intro">
    <span class="eyebrow">We're here to help</span>
    <h1>Talk to the florist</h1>
    <p>Questions about a bouquet, a customization, or your pickup? Send a note and we'll reply with care.</p>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
  <?php endif; ?>

  <div class="contact-layout">
    <form method="post" class="checkout-card contact-form">
      <?php echo csrfField(); ?>
      <?php if ($aboutOrder): ?>
        <input type="hidden" name="order_id" value="<?php echo (int)$aboutOrder['id']; ?>">
        <p class="contact-order-note"><i class="fa-solid fa-box-open"></i> Regarding order #<?php echo htmlspecialchars($aboutOrder['order_number']); ?></p>
      <?php endif; ?>

      <h2>Send a message</h2>
      <div class="form-grid-2">
        <div class="form-group">
          <label class="form-label" for="name">Name</label>
          <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($form['name']); ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="email">Email</label>
          <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($form['email']); ?>" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label" for="subject">Subject</label>
        <input type="text" id="subject" name="subject" class="form-control" value="<?php echo htmlspecialchars($form['subject']); ?>" placeholder="Customization, pickup, or a little something else">
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label" for="message">Message</label>
        <textarea id="message" name="message" class="form-control" rows="6" required placeholder="Tell us what you have in mind..."><?php echo htmlspecialchars($form['message']); ?></textarea>
      </div>
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:18px;">Send message <i class="fa-solid fa-arrow-right"></i></button>
      <p class="checkout-help">We reply during shop hours, usually within a few hours.</p>
    </form>

    <aside class="contact-aside">
      <div class="summary-card">
        <h2>Visit the shop</h2>
        <div class="contact-info">
          <div class="contact-info-row">
            <span>Address</span>
            <strong><?php echo htmlspecialchars($settings['shop_address'] ?? "Nicole's Flowers & Greens shop"); ?></strong>
          </div>
          <div class="contact-info-row">
            <span>Hours</span>
            <strong><?php echo htmlspecialchars($settings['shop_open'] ?? '10 am – 6 pm'); ?></strong>
          </div>
          <div class="contact-info-row">
            <span>Phone</span>
            <strong><?php echo htmlspecialchars($settings['shop_phone'] ?? '0917 123 4567'); ?></strong>
          </div>
          <div class="contact-info-row">
            <span>Email</span>
            <strong><?php echo htmlspecialchars($settings['shop_email'] ?? 'hello@nicolesflowersgreens.com'); ?></strong>
          </div>
        </div>
        <div class="summary-actions">
          <a href="products.php" class="btn btn-outline-green btn-block">Browse bouquets</a>
        </div>
      </div>

      <div class="info-card">
        <h3>Every stem, considered.</h3>
        <p>Each arrangement is made to order with the freshest stems at market. If something isn't available, we'll suggest the closest, loveliest alternative.</p>
      </div>
    </aside>
  </div>
</div>

<?php require_once 'includes/footer.php'; ?>
