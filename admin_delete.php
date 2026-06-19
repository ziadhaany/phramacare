<?php
require_once "conndb.php";

$id = $_GET['id'];

mysqli_query($conn, "DELETE FROM product WHERE product_id = $id");

header("Location: admin.php?deleted=1");