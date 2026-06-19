<?php
require 'conndb.php';

$id = intval($_GET['id']);
$status = $_GET['status'];

if (!in_array($status, ['approved','rejected'])) {
  die("Invalid status");
}

$conn->query("UPDATE prescriptions SET status='$status' WHERE prescription_id=$id");
header("Location: prescriptions.php");
