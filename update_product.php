<?php
require 'conndb.php';

$id = intval($_POST['id']);
$name = $_POST['name'];
$desc = $_POST['description'];
$price = $_POST['price'];
$is_active = $_POST['is_active'];

$sql = "UPDATE product 
        SET name='$name',
            description='$desc',
            price=$price,
            is_active=$is_active
        WHERE product_id=$id";

$conn->query($sql);

header("Location: admin.php");
