<?php
require_once __DIR__ . '/../config.php';
requireOwner();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) {
  redirect('orders.php');
}

// Order details now live in the split-panel view on the orders screen.
redirect('orders.php?selected=' . $orderId);
