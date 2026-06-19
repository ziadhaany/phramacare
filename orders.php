<?php require "conndb.php"; 

$sql = "
SELECT 
  o.order_id,
  o.total_amount,
  o.status,
  o.order_date,
  CONCAT(u.fname, ' ', u.lname) AS username
FROM orders o
JOIN users u ON o.user_id = u.user_id
ORDER BY o.order_date DESC
";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
<title>Manage Orders</title>
<link rel="stylesheet" href="admin.css">
</head>
<body>

<nav class="navbar"><div class="container"><div class="logo">PharmaCare • Admin</div></div></nav>

<main class="container mt-3">
<div class="dashboard">

<aside class="sidebar">
<ul class="menu">
  <li><a href="admin.php">Manage Products</a></li>
  <li><a href="orders.php" class="active">Manage Orders</a></li>
  <li><a href="users.php">Manage Users</a></li>
  <li><a href="prescriptions.php">Manage Prescriptions</a></li>
</ul>
</aside>

<section class="dashboard-content">
<h1>Order Management</h1>
<table class="table">
<tr>
  <th>ID</th>
  <th>User</th>
  <th>Total</th>
  <th>Status</th>
  <th>Action</th>
</tr>

<?php while($row = $result->fetch_assoc()): ?>
<tr>
  <td><?= $row['order_id'] ?></td>
  <td><?= $row['username'] ?></td>
<td><?= $row['total_amount'] ?> EGP</td>
  <td><?= $row['status'] ?></td>
  <td>
  <a href="orders_view.php?id=<?= $row['order_id'] ?>" class="btn-action view">
  View
</a>
    <a href="order_update.php?id=<?= $row['order_id'] ?> &status=confirmed" class="btn-action edit">Confirm</a> |
    <a href="order_update.php?id=<?= $row['order_id'] ?>&status=cancelled" class="btn-action delete">Cancel</a>
  </td>
</tr>
<?php endwhile; ?>
</table>


</section>
</div></main>

</body>
</html>