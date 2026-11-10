<?php
$pageTitle = 'Shop Settings';
$active = 'settings';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
  foreach ($_POST as $key => $val) {
    if ($key === 'csrf_token') continue;
    $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)")
      ->execute([$key, trim((string)$val)]);
  }
  setFlash('success', 'Settings saved.');
  redirect('settings.php');
}

$stmt = $pdo->query("SELECT `key`, `value` FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
  $settings[$row['key']] = $row['value'];
}
$downpayment = (int)($settings['downpayment_percent'] ?? 50);
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">Your shop, your rules</span>
    <h1>Shop settings</h1>
    <p class="desc">Set your pickup details, downpayment, and how customers can pay.</p>
  </div>
  <button type="submit" form="settingsForm" class="btn btn-primary save-top">Save changes</button>
</div>

<div class="admin-container">
  <form id="settingsForm" method="post">
    <?php echo csrfField(); ?>

    <div class="settings-grid">
      <section class="settings-card">
        <div class="card-kicker">Shop details</div>
        <div class="setting-row">
          <span>Shop name</span>
          <input type="text" name="shop_name" class="setting-input" value="<?php echo htmlspecialchars($settings['shop_name'] ?? ''); ?>" aria-label="Shop name">
        </div>
        <div class="setting-row">
          <span>Contact email</span>
          <input type="email" name="shop_email" class="setting-input" value="<?php echo htmlspecialchars($settings['shop_email'] ?? ''); ?>" aria-label="Contact email">
        </div>
        <div class="setting-row">
          <span>Pickup address</span>
          <input type="text" name="shop_address" class="setting-input" value="<?php echo htmlspecialchars($settings['shop_address'] ?? ''); ?>" aria-label="Pickup address" placeholder="Add address">
        </div>
        <div class="setting-row">
          <span>Pickup hours</span>
          <input type="text" name="shop_open" class="setting-input" value="<?php echo htmlspecialchars($settings['shop_open'] ?? ''); ?>" aria-label="Pickup hours" placeholder="Add hours">
        </div>
        <div class="setting-row">
          <span>Contact number</span>
          <input type="text" name="shop_phone" class="setting-input" value="<?php echo htmlspecialchars($settings['shop_phone'] ?? ''); ?>" aria-label="Contact number">
        </div>
      </section>

      <section class="settings-card">
        <div class="card-kicker">Downpayment</div>
        <div class="downpayment-row">
          <span>Required to confirm an order</span>
          <div class="select-wrap">
            <select name="downpayment_percent" class="form-control" aria-label="Downpayment percentage">
              <?php foreach ([0,10,20,30,40,50,60,70,80,90,100] as $pct): ?>
                <option value="<?php echo $pct; ?>" <?php echo $downpayment === $pct ? 'selected' : ''; ?>><?php echo $pct; ?>%</option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <p class="downpayment-help">The balance is settled at pickup. Customization prices are confirmed through your inbox.</p>
      </section>
    </div>

    <section class="settings-card payment-card">
      <div class="card-kicker">Payment methods</div>

      <?php
      $paymentMethods = [
        'payment_gcash' => 'GCash',
        'payment_maya' => 'Maya',
        'payment_bank' => 'Bank transfer',
        'payment_cash' => 'Pay at the shop',
      ];
      foreach ($paymentMethods as $key => $label): ?>
      <div class="payment-row">
        <span class="payment-name"><?php echo htmlspecialchars($label); ?></span>
        <input type="text" name="<?php echo $key; ?>" class="payment-value" value="<?php echo htmlspecialchars($settings[$key] ?? 'Add account'); ?>" readonly aria-label="<?php echo htmlspecialchars($label); ?> account">
        <button type="button" class="payment-edit">Edit</button>
      </div>
      <?php endforeach; ?>
    </section>
  </form>
</div>

<script>
document.querySelectorAll('.payment-edit').forEach(function (button) {
  button.addEventListener('click', function () {
    var input = button.parentElement.querySelector('.payment-value');
    if (input.hasAttribute('readonly')) {
      input.removeAttribute('readonly');
      input.focus();
      input.select();
      button.textContent = 'Done';
    } else {
      input.setAttribute('readonly', 'readonly');
      button.textContent = 'Edit';
    }
  });
});
</script>

<?php require_once 'footer.php'; ?>
