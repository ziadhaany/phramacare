<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharmacare";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

$message = "";
$msgType = "";
$guest_str = $_GET['guest'] ?? '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fname = htmlspecialchars(trim($_POST['fname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $lname = htmlspecialchars(trim($_POST['lname'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $street = htmlspecialchars(trim($_POST['street'] ?? ''), ENT_QUOTES, 'UTF-8');
    $city = htmlspecialchars(trim($_POST['city'] ?? ''), ENT_QUOTES, 'UTF-8');
    $zip = trim($_POST['zipcode'] ?? '');
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (empty($email) || empty($pass) || empty($fname) || empty($lname) || empty($phone)) {
        $message = "❌ Please fill all required fields.";
        $msgType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "❌ Invalid email format.";
        $msgType = "error";
    } elseif (!preg_match('/^\+?[0-9]{11,15}$/', $phone)) {
        $message = "❌ Invalid phone number format (11-15 digits).";
        $msgType = "error";
    } elseif (strlen($pass) < 6) {
        $message = "❌ Password must be at least 6 characters.";
        $msgType = "error";
    } elseif ($pass !== $confirm) {
        $message = "❌ Passwords do not match.";
        $msgType = "error";
    } else {
        try {
            $stmt = $conn->prepare("SELECT user_id, role FROM users WHERE email=? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            $hashedPassword = password_hash($pass, PASSWORD_DEFAULT);

            if ($user) {
                if ($user['role'] === 'user') {
                    $message = "❌ This email is already registered. Please login.";
                    $msgType = "error";
                } else {
                    $uid = $user['user_id'];
                    $upd = $conn->prepare("
                        UPDATE users 
                        SET role='user', password=?, fname=?, lname=?, phone=?, street=?, city=?, zip_code=?
                        WHERE user_id=?
                    ");
                    $upd->execute([$hashedPassword, $fname, $lname, $phone, $street, $city, $zip, $uid]);

                    header("Location: product.php?uid=" . $uid);
                    exit;
                }
            } else {
                $ins = $conn->prepare("
                    INSERT INTO users (role, fname, lname, phone, email, password, street, city, zip_code) 
                    VALUES ('user', ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$fname, $lname, $phone, $email, $hashedPassword, $street, $city, $zip]);
                $newUid = $conn->lastInsertId();

                header("Location: product.php?uid=" . $newUid);
                exit;
            }
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            $message = "❌ Registration failed. Please try again later.";
            $msgType = "error";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare • Registration</title>
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
    <link rel="stylesheet" href="register.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js"></script>
</head>

<body>
    <div class="navbar">
        <div class="logo">💊 PharmaCare</div>
    </div>

    <div class="page-container">
        <h1 class="page-title">User Registration</h1>
    </div>

    <div class="form-container">
        <?php if ($message): ?>
            <div class="alert <?php echo $msgType; ?>"><?php echo $message; ?></div>
        <?php endif; ?>

        <h2>Create Account</h2>

        <form action="register.php?guest=<?php echo htmlspecialchars($guest_str); ?>" method="POST" id="regForm">
            <div class="user-box">
                <div class="user-title">User Information</div>

                <label>First Name *</label>
                <input type="text" name="fname" id="fname" required>
                <div id="err-fname" class="error"></div>

                <label>Last Name *</label>
                <input type="text" name="lname" id="lname" required>
                <div id="err-lname" class="error"></div>

                <label>Email *</label>
                <input type="email" name="email" id="email" required>
                <div id="err-email" class="error"></div>

                <label>Phone *</label>
                <input type="text" name="phone" id="phone" required>
                <div id="err-phone" class="error"></div>
            </div>

            <div class="address-box">
                <div class="address-title">Address</div>

                <label>Street</label>
                <input type="text" name="street" id="street">
                <div id="err-street" class="error"></div>

                <label>City</label>
                <input type="text" name="city" id="city">
                <div id="err-city" class="error"></div>

                <label>Zip Code (optional)</label>
                <input type="text" name="zipcode" id="zipcode">
                <div id="err-zipcode" class="error"></div>
            </div>

            <div class="password-box">
                <div class="password-title">Password</div>

                <label>Password *</label>
                <input type="password" name="password" id="password" required>
                <div id="err-password" class="error"></div>

                <label>Confirm Password *</label>
                <input type="password" name="confirm" id="confirm" required>
                <div id="err-confirm" class="error"></div>
            </div>

            <button type="submit" id="submitBtn">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="register.js"></script>
</body>

</html>