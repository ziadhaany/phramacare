<?php
require 'conndb.php';

$id = intval($_GET['id']);
$status = $_GET['status'];

$allowed = ['pending','confirmed','cancelled','delivered'];
if (!in_array($status, $allowed)) {
  die("Invalid status");
}

$conn->query("UPDATE orders SET status='$status' WHERE order_id=$id");
header("Location: orders.php");
