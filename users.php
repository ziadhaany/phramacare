<?php require "conndb.php"; ?>
<!DOCTYPE html>
<html>

<head>
  <title>Manage Users</title>
  <link rel="stylesheet" href="admin.css">
</head>

<body>

  <nav class="navbar">
    <div class="container">
      <div class="logo">PharmaCare • Admin</div>
    </div>
  </nav>

  <main class="container mt-3">
    <div class="dashboard">

      <aside class="sidebar">
        <ul class="menu">
          <li><a href="admin.php">Manage Products</a></li>
          <li><a href="orders.php">Manage Orders</a></li>
          <li><a href="users.php" class="active">Manage Users</a></li>
          <li><a href="prescriptions.php">Manage Prescriptions</a></li>
        </ul>
      </aside>

      <section class="dashboard-content">
        <h1>User Management</h1>

        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Role</th>
            </tr>
          </thead>

          <tbody>
            <?php
            $sql = "SELECT * FROM users";
            $res = mysqli_query($conn, $sql);

            while ($u = mysqli_fetch_assoc($res)) { ?>
              <tr>
                <td><?= $u['user_id'] ?></td>
                <td><?= $u['fname'] . " " . $u['lname'] ?></td>
                <td><?= $u['email'] ?></td>
                <td><?= $u['phone'] ?></td>
                <td><?= $u['role'] ?></td>
              </tr>
            <?php } ?>
          </tbody>
        </table>

      </section>
    </div>
  </main>
</body>
</html>