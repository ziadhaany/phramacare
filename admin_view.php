<?php
require 'conndb.php';

if (!isset($_GET['id'])) {
  die("Product not found");
}

$id = intval($_GET['id']);

$sql = "SELECT p.*, c.name AS category
        FROM product p
        LEFT JOIN category c ON p.category_id = c.category_id
        WHERE p.product_id = $id";

$result = $conn->query($sql);
$product = $result->fetch_assoc();

if (!$product) {
  die("Product not found");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title><?= $product['name'] ?></title>
  <link rel="stylesheet" href="admin-view.css">
</head>
<body>
<div class="product-card">

  <div class="product-header">
    <img src="<?= $product['image_url'] ?>">
    <h1><?= $product['name'] ?></h1>
  </div>

  <div class="product-info">
    <p><b>Category:</b> <?= $product['category'] ?></p>
    <p><b>Description:</b> <?= $product['description'] ?></p>
    <p><b>Price:</b> <?= $product['price'] ?> EGP</p>
    <p>
      <b>Status:</b>
      <span class="status <?= $product['is_active'] ? 'active' : 'inactive' ?>">
        <?= $product['is_active'] ? 'Active' : 'Inactive' ?>
      </span>
    </p>
  </div>

  <a class="back-link" href="admin.php">⬅ Back to Dashboard</a>

</div>
</body>
</html>
