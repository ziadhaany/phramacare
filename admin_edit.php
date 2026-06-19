<?php
require 'conndb.php';

$id = intval($_GET['id']);

$result = $conn->query("SELECT * FROM product WHERE product_id=$id");
$product = $result->fetch_assoc();

if (!$product) {
  die("Product not found");
}
?>

<!DOCTYPE html>
<html>
<head>
  <title>Edit Product</title>
  <link rel="stylesheet" href="admin-edit.css">
</head>
<body>
<div class="form-card">
  <h2>Edit Product</h2>

  <form method="POST" action="update_product.php">

  <input type="hidden" name="id" value="<?= $product['product_id'] ?>">

  <div class="form-group">
    <label>Name</label>
    <input type="text" name="name" value="<?= $product['name'] ?>" required>
  </div>

  <div class="form-group">
    <label>Description</label>
    <textarea name="description" required><?= $product['description'] ?></textarea>
  </div>

  <div class="form-group">
    <label>Price</label>
    <input type="number" step="0.01" name="price" value="<?= $product['price'] ?>" required>
  </div>

  <div class="form-group">
    <label>Status</label>
    <select name="is_active">
      <option value="1" <?= $product['is_active'] ? 'selected' : '' ?>>Active</option>
      <option value="0" <?= !$product['is_active'] ? 'selected' : '' ?>>Inactive</option>
    </select>
  </div>

  <button type="submit" class="btn btn-primary">Save Changes</button>
  <a href="admin.php" class="btn btn-secondary">Cancel</a>

</form>
</div>

<a href="admin.php" class="btn btn-secondary">⬅ Back</a>

</body>
</html>
