<?php
$pageTitle = 'Your Cart';
require_once 'includes/header.php';
requireLogin();

$cart = !empty($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
$itemDetails = [];
foreach ($cart as $key => $item) {
  $subtotal += $item['price'] * $item['quantity'];
  $stmt = $pdo->prepare("SELECT description, stock, status FROM bouquets WHERE id=?");
  $stmt->execute([$key]);
  $itemDetails[$key] = $stmt->fetch() ?: ['description' => 'Made to order with the freshest seasonal stems.', 'stock' => 0, 'status' => 'inactive'];
}
$notes = $_SESSION['cart_notes'] ?? ['gift_note' => 'A little beauty for your day. With love, always.', 'florist_note' => ''];
?>

<div class="page-shell">
  <div class="page-topline">
    <a href="products.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Continue shopping</a>
    <div class="progress-steps" aria-label="Checkout progress">
      <span class="progress-step active">01 Cart</span>
      <span class="progress-line"></span>
      <span class="progress-step">02 Payment</span>
      <span class="progress-line"></span>
      <span class="progress-step">03 Pickup</span>
    </div>
  </div>

  <div class="page-intro">
    <span class="eyebrow">A little beauty, on its way</span>
    <h1>Your cart</h1>
    <p>Make it personal. We'll take care of every stem and thoughtful detail.</p>
  </div>

  <?php if (empty($cart)): ?>
    <div class="cart-empty">
      <i class="fa-solid fa-bag-shopping"></i>
      <h2>Your cart is empty</h2>
      <a href="products.php" class="btn btn-primary">Shop fresh flowers</a>
    </div>
  <?php else: ?>
    <div class="cart-layout">
      <div class="cart-main">
        <div class="cart-list-head">
          <span>Your bouquet</span>
          <span>Item total</span>
        </div>

        <?php foreach ($cart as $key => $item): ?>
        <article class="cart-item">
          <a class="cart-item-media" href="products.php?id=<?php echo (int)$key; ?>">
            <img src="assets/images/<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
          </a>

          <div class="cart-item-info">
            <a class="cart-item-title" href="products.php?id=<?php echo (int)$key; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
            <p class="cart-item-desc"><?php echo htmlspecialchars($itemDetails[$key]['description']); ?></p>

            <div class="cart-item-controls">
              <form action="cart_action.php" method="post" class="qty-stepper" aria-label="Quantity">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="key" value="<?php echo (int)$key; ?>">
                <button type="submit" name="delta" value="-1" aria-label="Decrease quantity">−</button>
                <span class="qty-value"><?php echo (int)$item['quantity']; ?></span>
                <button type="submit" name="delta" value="1" aria-label="Increase quantity">+</button>
              </form>

              <form action="cart_action.php" method="post">
                <?php echo csrfField(); ?>
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="key" value="<?php echo (int)$key; ?>">
                <button type="submit" class="remove-link"><i class="fa-regular fa-trash-can"></i> Remove</button>
              </form>
            </div>

            <a href="contact.php" class="customize-toggle"><i class="fa-solid fa-sliders"></i> Customize your bouquet <i class="fa-solid fa-chevron-up"></i></a>
          </div>

          <div class="cart-item-price">
            Starting at
            <strong>₱<?php echo number_format((float)$item['price']); ?></strong>
          </div>
        </article>
        <?php endforeach; ?>

        <form id="finishingForm" action="cart_action.php" method="post">
          <?php echo csrfField(); ?>
          <input type="hidden" name="action" value="notes">
          <input type="hidden" name="redirect" value="checkout.php">

          <section class="finishing-card">
            <div class="finishing-head">
              <h2>The finishing touches</h2>
              <span>Made just for them · Optional</span>
            </div>

            <div class="finishing-field">
              <div class="finishing-label-row">
                <label for="gift_note">Handwritten gift note</label>
                <span>Complimentary</span>
              </div>
              <textarea id="gift_note" name="gift_note" class="form-control" maxlength="200" rows="3" data-char-count="giftNoteCount" placeholder="A little beauty for your day. With love, always."><?php echo htmlspecialchars($notes['gift_note'] ?? ''); ?></textarea>
              <div class="char-count"><span id="giftNoteCount"><?php echo mb_strlen($notes['gift_note'] ?? ''); ?></span> / 200</div>
            </div>

            <div class="finishing-field" style="margin-bottom:0;">
              <div class="finishing-label-row">
                <label for="florist_note">A note for our florist</label>
              </div>
              <textarea id="florist_note" name="florist_note" class="form-control" rows="3" placeholder="Any favorite colors or stems to avoid?"><?php echo htmlspecialchars($notes['florist_note'] ?? ''); ?></textarea>
              <p class="finishing-help">We'll do our best to honor your wishes, with the freshest stems in season.</p>
            </div>
          </section>
        </form>
      </div>

      <aside class="cart-aside">
        <div class="summary-card">
          <h2>Order summary</h2>
          <div class="summary-line"><span>Bouquet starting price</span><strong>₱<?php echo number_format($subtotal); ?></strong></div>
          <div class="summary-line"><span>Customization</span><span>To be confirmed</span></div>
          <hr class="summary-divider">
          <div class="summary-total"><span>Estimated total</span><span><span class="from-label">From</span><strong>₱<?php echo number_format($subtotal); ?></strong></span></div>

          <p class="summary-note"><strong>Please Note:</strong>Price shown is an estimate.</p>
          <p class="summary-note"><strong>Action Required:</strong>Contact the seller to confirm your final price, then submit the required downpayment at checkout to process your order for pickup.</p>

          <div class="summary-actions">
            <button type="submit" form="finishingForm" class="btn btn-primary btn-block">Continue to checkout <i class="fa-solid fa-arrow-right"></i></button>
            <a href="contact.php" class="btn btn-outline-green btn-block"><i class="fa-regular fa-comment"></i> Contact seller</a>
          </div>
          <p class="secure-note"><i class="fa-solid fa-lock"></i> Secure checkout</p>
        </div>

        <div class="seasonal-card">
          <h3>Always seasonal. Always special.</h3>
          <p>Each arrangement is made to order with the freshest stems at market. Colours and blooms may shift gently with the season, never with the care.</p>
        </div>
      </aside>
    </div>
  <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
