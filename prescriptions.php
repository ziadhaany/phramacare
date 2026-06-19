<?php
require "conndb.php";

$sql = "
SELECT 
  p.prescription_id,
  p.image_url,
  p.status,
  CONCAT(u.fname, ' ', u.lname) AS username
FROM prescription p
JOIN users u ON p.user_id = u.user_id
";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>

<head>
  <title>Manage Prescriptions</title>
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
          <li><a href="users.php">Manage Users</a></li>
          <li><a href="prescriptions.php" class="active">Manage Prescriptions</a></li>
        </ul>
      </aside>

      <section class="dashboard-content">
        <h1>Prescription Management</h1>


        <table class="table">
          <tr>
            <th>ID</th>
            <th>User</th>
            <th>Image</th>
            <th>Status</th>
            <th>Action</th>
          </tr>

          <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= $row['prescription_id'] ?></td>
              <td><?= $row['username'] ?></td>

              <td>
                <a href="<?= $row['image_url'] ?>" target="_blank">View Image</a>
              </td>
              <td><?= $row['status'] ?></td>
              <td>
                <a href="prescription_update.php?id=<?= $row['prescription_id'] ?>&status=approved">Approve</a> |
                <a href="prescription_update.php?id=<?= $row['prescription_id'] ?>&status=rejected">Reject</a>
              </td>
            </tr>
          <?php endwhile; ?>
        </table>
      </section>
    </div>
  </main>

</body>

</html>