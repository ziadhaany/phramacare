<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharmacare";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$error = "";
$email = "";
$guest_str = $_GET['guest'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

  $email = trim($_POST["email"] ?? "");
  $pass = trim($_POST["password"] ?? "");

  if ($email === "" || $pass === "") {
    $error = "Please enter email and password.";
  } else {

    $stmt = $conn->prepare("SELECT user_id, role, password FROM users WHERE email=? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();

    if (!$user) {
      $error = "Invalid credentials.";
    } else {
      $storedPassword = $user["password"];
      $isValid = password_verify($pass, $storedPassword) || $pass === $storedPassword;

      if (!$isValid) {
        $error = "Invalid credentials.";
      } else {
        $realUserId = $user['user_id'];

        if ($pass === $storedPassword && !password_get_info($storedPassword)['algo']) {
          $newHash = password_hash($pass, PASSWORD_DEFAULT);
          $up = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
          $up->bind_param("si", $newHash, $realUserId);
          $up->execute();
        }

        if (!empty($guest_str)) {
          $fakeEmail = $conn->real_escape_string($guest_str . '@temp.com');
          $gStmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
          $gStmt->bind_param("s", $fakeEmail);
          $gStmt->execute();
          $gRes = $gStmt->get_result();
          $gRow = $gRes->fetch_assoc();

          if ($gRow) {
            $guestUserId = $gRow['user_id'];

            $gcRes = $conn->query("SELECT cart_id FROM cart WHERE user_id=$guestUserId");
            $guestCart = $gcRes->fetch_assoc();

            if ($guestCart) {
              $ucRes = $conn->query("SELECT cart_id FROM cart WHERE user_id=$realUserId");
              $userCart = $ucRes->fetch_assoc();

              if ($userCart) {
                $conn->query("UPDATE cart_item SET cart_id={$userCart['cart_id']} WHERE cart_id={$guestCart['cart_id']}");
                $conn->query("DELETE FROM cart WHERE cart_id={$guestCart['cart_id']}");

                $sumRes = $conn->query("SELECT SUM(quantity*unit_price) FROM cart_item WHERE cart_id={$userCart['cart_id']}");
                $rowSum = $sumRes->fetch_row();
                $conn->query("UPDATE cart SET total_price=" . ($rowSum[0] ?? 0) . " WHERE cart_id={$userCart['cart_id']}");
              } else {
                $conn->query("UPDATE cart SET user_id=$realUserId WHERE user_id=$guestUserId");
              }
            }

            $conn->query("DELETE FROM users WHERE user_id=$guestUserId");
          }
          $gStmt->close();
        }

        if ($user["role"] === "admin") {
          header("Location: admin.php");
        } else {
          header("Location: home.php?uid=$realUserId");
        }
        exit;
      }
    }

    $stmt->close();
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>PharmaCare • Login</title>
  <link rel="stylesheet" href="login.css">
  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <style>
    .error {
      color: #991b1b;
      font-size: 0.85rem;
    }

    input.invalid {
      border: 1px solid red;
    }

    input.valid {
      border: 1px solid green;
    }
  </style>
</head>

<body>
  <div class="navbar">
    <div class="container clearfix">
      <div class="logo">💊 Pharma Care</div>
    </div>
  </div>

  <div class="container mt-3 text-center">
    <h1>Login Page</h1>
    <p>Please enter your credentials</p>

    <?php if ($error): ?>
      <div style="margin-top:12px; background:#fee2e2; color:#991b1b; padding:10px; border-radius:10px;">
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <form class="form mt-3" action="login.php?guest=<?= htmlspecialchars($guest_str) ?>" method="post" id="loginForm">
      <div class="form-group">
        <label>Email</label>
        <input type="text" id="email" name="email" placeholder="Enter your email"
          value="<?= htmlspecialchars($email) ?>">
        <span class="error"></span>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password">
        <span class="error"></span>
      </div>

      <input type="submit" class="btn" value="Login">

      <p style="margin-top: 15px;">
        Don't have an account?
        <a href="register.php?guest=<?= htmlspecialchars($guest_str) ?>"
          style="color: #1d4ed8; text-decoration: underline;">Register here</a>
      </p>

      <p style="margin-top: 10px;">
        <a href="product.php" style="color: #1d4ed8; text-decoration: underline;">Back to Home</a>
      </p>

    </form>
  </div>

  <div class="footer text-center mt-3">&copy; 2025 Pharma Care. All rights reserved.</div>

  <script>
    $(document).ready(function () {
      $("#loginForm").on("submit", function (e) {
        if ($("#email").val() == "" || $("#password").val() == "") {
          e.preventDefault();
          alert("Please fill all fields");
        }
      });
    });
  </script>
</body>

</html>