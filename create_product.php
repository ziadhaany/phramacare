<?php
require "conndb.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $name        = trim($_POST["name"] ?? "");
  $description = trim($_POST["description"] ?? "");
  $price       = floatval($_POST["price"] ?? 0);
  $stock       = intval($_POST["stock"] ?? 0);
  $category_id = intval($_POST["category_id"] ?? 0);

  if (!$name || !$description || $price <= 0 || $stock <= 0 || $category_id <= 0) {
    $error = "Please fill all fields correctly.";
  } 
  else {

    // ===== IMAGE =====
    $imageName = "";

    if (!empty($_FILES["image"]["name"])) {
    
        if (!is_dir("images")) {
            mkdir("images", 0777, true);
        }
    
        $imageName = basename($_FILES["image"]["name"]);
    
        $targetPath = "images/" . $imageName;
    
        move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath);
    }
    
    
     }
  
     
    // ===== DEFAULT VALUES =====
    $dosage = "general";
    $requires = 0;
    $is_active = 1;

    // ===== INSERT =====
    $sql = "
      INSERT INTO product
      (name, description, dosage_form, requires_prescription, price, stock_quantity, image_url, category_id, is_active)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
      $error = $conn->error;
    } else {
      $stmt->bind_param(
        "sssidisii",
        $name,
        $description,
        $dosage,
        $requires,
        $price,
        $stock,
        $targetPath ,
        $category_id,
        $is_active
      );

      if ($stmt->execute()) {
        $success = "Product created successfully.";
      } else {
        $error = $stmt->error;
      }

      $stmt->close();
    }
  }

?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Product</title>
  <link rel="stylesheet" href="create.css">
</head>
<body>

<!-- ================== NAVBAR ================== -->
<nav class="navbar">
  <div class="container">
    <div class="logo">PharmaCare • Admin</div>
  </div>
</nav>

<!-- ================== PAGE ================== -->
<div class="container mt-3 text-center">

  <h1 class="mb-2">Create New Product</h1>
  <p class="subtitle mb-3">Add a new product to the store</p>

  <?php if ($error): ?>
    <div class="alert error"><?= $error ?></div>
  <?php endif; ?>

  <?php if ($success): ?>
    <div class="alert success"><?= $success ?></div>
  <?php endif; ?>

  <!-- ================== FORM ================== -->
  <form class="form" method="POST" enctype="multipart/form-data">

    <div class="form-group">
      <label>Product Name</label>
      <input type="text" name="name" required>
    </div>

    <div class="form-group">
      <label>Description</label>
      <textarea name="description" rows="4" required></textarea>
    </div>

    <div class="form-group">
      <label>Price (EGP)</label>
      <input type="number" name="price" step="0.01" required>
    </div>

    <div class="form-group">
      <label>Stock Quantity</label>
      <input type="number" name="stock" required>
    </div>

    <div class="form-group">
      <label>Category</label>
      <select name="category_id" required>
        <option value="">Select Category</option>
        <option value="1">Medicine</option>
        <option value="2">Supplements</option>
        <option value="3">Medical Supplies</option>
      </select>
    </div>

    <div class="form-group">
      <label>Product Image</label>
      <input type="file" name="image">
    </div>

    <button type="submit" class="btn">Create Product</button>

  </form>
</div>

<!-- ================== FOOTER ================== -->
<footer class="footer">
  © 2025 PharmaCare — All rights reserved.
</footer>

</body>
</html>