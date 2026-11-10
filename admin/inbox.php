<?php
$pageTitle = 'Inbox';
$active = 'inbox';
require_once 'header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf_token'] ?? '')) {
  $action = $_POST['action'] ?? '';
  $threadKey = $_POST['thread_key'] ?? '';

  if ($action === 'mark_all_read') {
    $pdo->exec("UPDATE messages SET is_read=1 WHERE sender='customer'");
    setFlash('success', 'All conversations marked as read.');
    redirect('inbox.php');
  }

  $userId = (int)($_POST['user_id'] ?? 0);
  $orderId = (int)($_POST['order_id'] ?? 0);
  $name = trim($_POST['name'] ?? 'Customer');
  $email = trim($_POST['email'] ?? '');
  $message = trim($_POST['message'] ?? '');

  if ($action === 'quote') {
    $amount = max(0, (float)($_POST['quote_amount'] ?? 0));
    if ($amount > 0) {
      $message = 'Price quote: ₱' . number_format($amount, 2) . '. Let us know if you would like us to proceed.';
      if ($orderId) {
        $stmt = $pdo->prepare("SELECT subtotal FROM orders WHERE id=?");
        $stmt->execute([$orderId]);
        $subtotal = (float)($stmt->fetchColumn() ?: 0);
        $pdo->prepare("UPDATE orders SET customization=?, total=? WHERE id=?")
          ->execute([$amount, $subtotal + $amount, $orderId]);
      }
      $action = 'reply';
    }
  }

  if ($action === 'reply' && $message !== '') {
    $pdo->prepare("INSERT INTO messages (user_id, order_id, name, email, sender, message, is_read) VALUES (?,?,?,?, 'owner', ?, 1)")
      ->execute([$userId ?: null, $orderId ?: null, $name, $email, $message]);
    setFlash('success', 'Reply sent.');
  }

  $redirect = 'inbox.php';
  if ($threadKey !== '') {
    $redirect .= '?thread=' . urlencode($threadKey);
  }
  redirect($redirect);
}

// Build conversation threads from individual messages.
$stmt = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC, id DESC");
$threads = [];
foreach ($stmt->fetchAll() as $m) {
  $key = $m['user_id'] ? 'u' . $m['user_id'] : 'e' . strtolower($m['email']);
  if (!isset($threads[$key])) {
    $threads[$key] = [
      'key' => $key,
      'user_id' => (int)($m['user_id'] ?? 0),
      'order_id' => 0,
      'name' => $m['name'],
      'email' => $m['email'],
      'messages' => [],
      'unread' => false,
      'last_at' => $m['created_at'],
      'preview' => '',
    ];
  }
  $threads[$key]['messages'][] = $m;

  // Messages arrive newest-first, so keep the first order reference we see.
  if (!$threads[$key]['order_id'] && !empty($m['order_id'])) {
    $threads[$key]['order_id'] = (int)$m['order_id'];
  }

  // The list preview should show the latest customer question, not our reply.
  if ($threads[$key]['preview'] === '' && $m['sender'] === 'customer') {
    $threads[$key]['preview'] = $m['message'];
  }

  if ($m['sender'] === 'customer' && !(int)$m['is_read']) {
    $threads[$key]['unread'] = true;
  }
}
foreach ($threads as &$thread) {
  if ($thread['preview'] === '' && $thread['messages']) {
    $thread['preview'] = $thread['messages'][0]['message'];
  }
}
unset($thread);
uasort($threads, fn($a, $b) => strtotime($b['last_at']) <=> strtotime($a['last_at']));

$requested = $_GET['thread'] ?? '';
$selectedKey = '';
if ($requested !== '') {
  $candidate = ctype_digit((string)$requested) ? 'u' . $requested : (string)$requested;
  if (isset($threads[$candidate])) {
    $selectedKey = $candidate;
  }
}
if ($selectedKey === '' && $threads) {
  $selectedKey = array_key_first($threads);
}
$selectedThread = $selectedKey !== '' ? $threads[$selectedKey] : null;
$threadMessages = $selectedThread ? array_reverse($selectedThread['messages']) : [];

$threadOrder = null;
$threadBouquet = null;
if ($selectedThread && $selectedThread['order_id']) {
  $stmt = $pdo->prepare("SELECT o.*, u.full_name,
      (SELECT b.name FROM order_items oi JOIN bouquets b ON b.id=oi.bouquet_id WHERE oi.order_id=o.id ORDER BY oi.id LIMIT 1) AS bouquet_name
    FROM orders o JOIN users u ON u.id=o.user_id WHERE o.id=?");
  $stmt->execute([$selectedThread['order_id']]);
  $threadOrder = $stmt->fetch();
  $threadBouquet = $threadOrder['bouquet_name'] ?? null;
}

if ($selectedThread) {
  if ($selectedThread['user_id']) {
    $pdo->prepare("UPDATE messages SET is_read=1 WHERE user_id=? AND sender='customer'")->execute([$selectedThread['user_id']]);
  } else {
    $pdo->prepare("UPDATE messages SET is_read=1 WHERE email=? AND sender='customer'")->execute([$selectedThread['email']]);
  }
}

$relativeTime = function (string $timestamp): string {
  $diff = max(1, time() - strtotime($timestamp));
  if ($diff < 60) return 'Just now';
  if ($diff < 3600) return floor($diff / 60) . ' min';
  if ($diff < 86400) return floor($diff / 3600) . ' hr';
  if ($diff < 172800) return 'Yesterday';
  return date('j M', strtotime($timestamp));
};
?>

<div class="admin-page-head">
  <div class="admin-title-block">
    <span class="eyebrow">Customer messages</span>
    <h1>Inbox</h1>
    <p class="desc">Answer customization questions and send a price quote.</p>
  </div>
  <form method="post">
    <?php echo csrfField(); ?>
    <button type="submit" name="action" value="mark_all_read" class="btn btn-outline-green btn-sm">Mark all as read</button>
  </form>
</div>

<div class="admin-container">
  <?php if (!$threads): ?>
    <div class="inbox-empty">
      <i class="fa-regular fa-envelope" style="font-size:28px;margin-bottom:14px;"></i>
      <p>No messages yet. Customer questions will appear here.</p>
    </div>
  <?php else: ?>
  <div class="inbox-layout">
    <aside class="thread-list">
      <?php foreach ($threads as $key => $thread): ?>
      <a class="thread-item <?php echo $key === $selectedKey ? 'active' : ''; ?>" href="inbox.php?thread=<?php echo urlencode($key); ?>">
        <div class="thread-top">
          <?php if ($thread['unread']): ?><span class="thread-dot"></span><?php endif; ?>
          <span class="thread-name"><?php echo htmlspecialchars($thread['name']); ?></span>
          <span class="thread-time"><?php echo htmlspecialchars($relativeTime($thread['last_at'])); ?></span>
        </div>
        <div class="thread-preview"><?php echo htmlspecialchars(mb_strimwidth($thread['preview'], 0, 58, '…')); ?></div>
      </a>
      <?php endforeach; ?>
    </aside>

    <?php if ($selectedThread): ?>
    <section class="thread-view">
      <div class="thread-head">
        <div>
          <h2><?php echo htmlspecialchars($selectedThread['name']); ?></h2>
          <?php if ($threadOrder): ?>
            <div class="thread-order">Order #<?php echo htmlspecialchars($threadOrder['order_number']); ?> · <?php echo htmlspecialchars($threadBouquet ?? 'Bouquet'); ?> · Estimated ₱<?php echo number_format((float)$threadOrder['total']); ?></div>
          <?php else: ?>
            <div class="thread-order">Customer conversation</div>
          <?php endif; ?>
        </div>
        <?php if ($threadOrder): ?>
          <a href="orders.php?selected=<?php echo (int)$threadOrder['id']; ?>" class="btn-edit">View order</a>
        <?php endif; ?>
      </div>

      <div class="thread-body">
        <?php foreach ($threadMessages as $m): ?>
          <div class="bubble-wrap <?php echo $m['sender'] === 'owner' ? 'out' : 'in'; ?>">
            <div class="bubble <?php echo $m['sender'] === 'owner' ? 'out' : 'in'; ?>"><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
            <div class="bubble-time"><?php echo date('j M, g:i a', strtotime($m['created_at'])); ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="post" class="quote-form">
        <?php echo csrfField(); ?>
        <input type="hidden" name="thread_key" value="<?php echo htmlspecialchars($selectedThread['key']); ?>">
        <input type="hidden" name="user_id" value="<?php echo (int)$selectedThread['user_id']; ?>">
        <input type="hidden" name="order_id" value="<?php echo (int)$selectedThread['order_id']; ?>">
        <input type="hidden" name="name" value="<?php echo htmlspecialchars($selectedThread['name']); ?>">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($selectedThread['email']); ?>">
        <div class="detail-section-title">Send a price quote</div>
        <div class="quote-row">
          <div class="quote-input-wrap">
            <span>₱</span>
            <input type="number" name="quote_amount" step="0.01" min="0" class="form-control" placeholder="128.00" aria-label="Quote amount">
          </div>
          <button type="submit" name="action" value="quote" class="btn-save">Send quote</button>
        </div>
        <div class="reply-row">
          <input type="text" name="message" class="form-control" placeholder="Write a reply..." aria-label="Reply message">
          <button type="submit" name="action" value="reply" class="btn-save" aria-label="Send reply"><i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
    </section>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php require_once 'footer.php'; ?>
