<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect('cart.php');
}

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
  setFlash('error', 'Invalid security token.');
  redirect('cart.php');
}

if (!isLoggedIn()) {
  redirect('login.php');
}

$action = $_POST['action'] ?? '';
$redirectTo = $_POST['redirect'] ?? 'cart.php';

if (!isset($_SESSION['cart'])) {
  $_SESSION['cart'] = [];
}

switch ($action) {
  case 'add':
    $bouquetId = (int)($_POST['bouquet_id'] ?? 0);
    $quantity = max(1, (int)($_POST['quantity'] ?? 1));
    if ($bouquetId > 0) {
      $stmt = $pdo->prepare("SELECT id, name, price, image, stock FROM bouquets WHERE id=? AND status='active'");
      $stmt->execute([$bouquetId]);
      $bouquet = $stmt->fetch();
      if ($bouquet) {
        $key = $bouquetId;
        if (isset($_SESSION['cart'][$key])) {
          $_SESSION['cart'][$key]['quantity'] += $quantity;
        } else {
          $_SESSION['cart'][$key] = [
            'name' => $bouquet['name'],
            'price' => (float)$bouquet['price'],
            'image' => $bouquet['image'],
            'quantity' => $quantity,
          ];
        }
        setFlash('success', 'Added to cart.');
      }
    }
    redirect('cart.php');
    break;

  case 'update':
    $key = (int)($_POST['key'] ?? 0);
    if (isset($_SESSION['cart'][$key])) {
      if (isset($_POST['delta'])) {
        $quantity = (int)$_SESSION['cart'][$key]['quantity'] + (int)$_POST['delta'];
      } else {
        $quantity = (int)($_POST['quantity'] ?? 1);
      }
      $_SESSION['cart'][$key]['quantity'] = max(1, min(99, $quantity));
      setFlash('success', 'Cart updated.');
    }
    redirect('cart.php');
    break;

  case 'remove':
    $key = (int)($_POST['key'] ?? 0);
    if (isset($_SESSION['cart'][$key])) {
      unset($_SESSION['cart'][$key]);
      setFlash('success', 'Item removed.');
    }
    redirect('cart.php');
    break;

  case 'notes':
    $_SESSION['cart_notes'] = [
      'gift_note' => trim($_POST['gift_note'] ?? ''),
      'florist_note' => trim($_POST['florist_note'] ?? ''),
    ];
    setFlash('success', 'Finishing touches saved.');
    redirect($redirectTo === 'checkout.php' ? 'checkout.php' : 'cart.php');
    break;

  case 'clear':
    $_SESSION['cart'] = [];
    unset($_SESSION['cart_notes']);
    setFlash('success', 'Cart cleared.');
    redirect('cart.php');
    break;

  default:
    redirect('cart.php');
}
