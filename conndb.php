<?php
$host = "localhost";
$user = "root";       // XAMPP default
$pass = "";           // XAMPP default
$db   = "pharmacare";     // اسم الداتا بيز اللي عندك

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>