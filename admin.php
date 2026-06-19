<?php require "conndb.php"; ?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>PharmaCare • Admin Dashboard</title>
  <link rel="stylesheet" href="admin.css">
</head>

<body>

  <nav class="navbar">
    <div class="container">
      <div class="logo">PharmaCare • Admin</div>
      <ul>
        <li><a href="admin.php" class="active">Admin</a></li>
        <li><a href="login.php">Logout</a></li>
      </ul>
    </div>
  </nav>

  <main class="container mt-3">
    <div class="dashboard">

      <aside class="sidebar">
        <h3 class="mt-1">Admin Menu</h3>
        <ul class="menu">
          <li><a href="admin.php" class="active">Manage Products</a></li>
          <li><a href="orders.php">Manage Orders</a></li>
          <li><a href="users.php">Manage Users</a></li>
          <li><a href="prescriptions.php">Manage Prescriptions</a></li>
          <li><a href="create_product.php">Add New Product</a></li>
        </ul>
      </aside>

      <section class="dashboard-content">
        <h1 class="mb-2">Product Management Dashboard</h1>

        <div class="card mb-2">
          <p class="subtitle">Manage all products stored in the database.</p>
        </div>

        <a href="create_product.php" class="btn mb-2">➕ Add New Product</a>
        <div class="table-actions mb-2">
          <input type="text" id="product-search" placeholder="🔍 Search product name..." class="search-input">
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Image</th>
              <th>Name</th>
              <th>Description</th>
              <th>Category</th>
              <th>Price</th>
              <th style="text-align:center;">Actions</th>
            </tr>
          </thead>

          <tbody>
            <?php
            $sql = "SELECT product.*, category.name AS cat_name FROM product 
        LEFT JOIN category ON product.category_id = category.category_id";
            $result = mysqli_query($conn, $sql);

            while ($row = mysqli_fetch_assoc($result)) { ?>
              <tr>
                <td><?= $row['product_id'] ?></td>
                <td><img src="<?= $row['image_url'] ?>" class="thumb"></td>
                <td><?= $row['name'] ?></td>
                <td><?= substr($row['description'], 0, 50) ?>...</td>
                <td><?= $row['cat_name'] ?></td>
                <td><?= $row['price'] ?> EGP</td>
                <td>
                  <div class="action-buttons">
                    <a href="admin_view.php?id=<?= $row['product_id'] ?>" class="btn-action view">View</a>
                    <a href="admin_edit.php?id=<?= $row['product_id'] ?>" class="btn-action edit">Edit</a>
                    <a href="admin_delete.php?id=<?= $row['product_id'] ?>" class="btn-action delete">Delete</a>
                  </div>
                </td>

              </tr>
            <?php } ?>
          </tbody>
        </table>

      </section>
    </div>
  </main>
  <script src="admin.js"></script>
</body>

</html>