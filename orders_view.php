<?php
require 'conndb.php';

if (!isset($_GET['id'])) {
  die("Order ID missing");
}

$id = intval($_GET['id']);

// order info
$order = $conn->query("
  SELECT 
    o.*, 
    CONCAT(u.fname, ' ', u.lname) AS username,
    u.email
  FROM orders o
  JOIN users u ON o.user_id = u.user_id
  WHERE o.order_id = $id
")->fetch_assoc();

if (!$order) {
  die("Order not found");
}

// order items
$items = $conn->query("
  SELECT 
    oi.quantity,
    oi.unit_price,
    p.name
  FROM order_item oi
  JOIN product p ON oi.product_id = p.product_id
  WHERE oi.order_id = $id
");
?>

<!DOCTYPE html>
<html>
<head>
<div class="order-page">
<link rel="stylesheet" href="order_view.css">

<div class="order-card">
  <h2>Order #<?= $id ?></h2>

  <div class="order-info">
    <p><span>Customer:</span> <?= $order['username'] ?> (<?= $order['email'] ?>)</p>
    <p><span>Status:</span> <span class="badge <?= $order['status'] ?>"><?= $order['status'] ?></span></p>
    <p><span>Total:</span> <?= $order['total_amount'] ?> EGP</p>
    <p><span>Date:</span> <?= $order['order_date'] ?></p>
  </div>
</div>

<div class="order-card">
  <h3>Products</h3>

  <table class="order-table">
    <tr>
      <th>Product</th>
      <th>Qty</th>
      <th>Unit Price</th>
    </tr>

    <?php while($row = $items->fetch_assoc()): ?>
    <tr>
      <td><?= $row['name'] ?></td>
      <td><?= $row['quantity'] ?></td>
      <td><?= $row['unit_price'] ?> EGP</td>
    </tr>
    <?php endwhile; ?>
  </table>
</div>

<a href="orders.php" class="back-btn">⬅ Back to Orders</a>

</div>


</body>
</html>
