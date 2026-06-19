<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharmacare";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$logged_uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$guest_id = isset($_GET['guest']) ? $_GET['guest'] : '';

if ($logged_uid == 0 && empty($guest_id)) {
    $guest_id = 'guest_' . bin2hex(random_bytes(4));
}
function url($path, $params = [])
{
    global $logged_uid, $guest_id;
    if ($logged_uid > 0) {
        $params['uid'] = $logged_uid;
    } elseif (!empty($guest_id)) {
        $params['guest'] = $guest_id;
    }
    $qs = http_build_query($params);
    return $path . ($qs ? (strpos($path, '?') === false ? '?' : '&') . $qs : '');
}

$currentUserId = 0;
if ($logged_uid > 0) {
    $currentUserId = $logged_uid;
} else {
    $fakeEmail = $guest_id . '@temp.com';
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->execute([$fakeEmail]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $currentUserId = $row['user_id'];
    } else {
        $dummyPass = password_hash('temp', PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'guest')");
        $stmt->execute([$fakeEmail, $dummyPass]);
        $currentUserId = $conn->lastInsertId();
    }
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');

    $stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id = ?");
    $stmt->execute([$currentUserId]);
    $cartRow = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($cartRow) {
        $cartId = $cartRow['cart_id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, total_price) VALUES (?, 0)");
        $stmt->execute([$currentUserId]);
        $cartId = $conn->lastInsertId();
    }

    if ($_GET['action'] == "add" && isset($_GET['id'])) {
        $prodId = intval($_GET['id']);
        $qty = isset($_GET['qty']) ? intval($_GET['qty']) : 1;

        $stmt = $conn->prepare("SELECT cart_item_id, quantity FROM cart_item WHERE cart_id = ? AND product_id = ?");
        $stmt->execute([$cartId, $prodId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmtP = $conn->prepare("SELECT price FROM product WHERE product_id = ?");
        $stmtP->execute([$prodId]);
        $price = $stmtP->fetchColumn();

        if (!$item) {
            $conn->prepare("INSERT INTO cart_item (cart_id, product_id, unit_price, quantity) VALUES (?, ?, ?, ?)")
                ->execute([$cartId, $prodId, $price, $qty]);
        } else {
            $newQty = $item['quantity'] + $qty;
            $conn->prepare("UPDATE cart_item SET quantity = ? WHERE cart_item_id = ?")
                ->execute([$newQty, $item['cart_item_id']]);
        }

        $stmtSum = $conn->prepare("SELECT SUM(unit_price * quantity) FROM cart_item WHERE cart_id = ?");
        $stmtSum->execute([$cartId]);
        $totalCart = $stmtSum->fetchColumn() ?: 0;
        $conn->prepare("UPDATE cart SET total_price = ? WHERE cart_id = ?")->execute([$totalCart, $cartId]);

        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM cart_item WHERE cart_id = ?");
        $stmtCount->execute([$cartId]);
        echo json_encode(["status" => "success", "count" => $stmtCount->fetchColumn() ?: 0]);
        exit;
    }

    if ($_GET['action'] == "count") {
        $stmtCount = $conn->prepare("SELECT COUNT(*) FROM cart_item WHERE cart_id = ?");
        $stmtCount->execute([$cartId]);
        echo json_encode(["count" => $stmtCount->fetchColumn() ?: 0]);
        exit;
    }
}

if (empty($_GET['id'])) {
    header("Location: " . url('product.php'));
    exit;
}

$productId = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM product WHERE product_id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header("Location: " . url('product.php'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($product['name']); ?> - PharmaCare</title>
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
    <link rel="stylesheet" href="product-details.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js"></script>
</head>

<body>

    <nav class="main-navbar">
        <div class="logo">💊 PharmaCare</div>
        <ul class="nav-links">
            <li><a href="<?php echo url('home.php'); ?>">Home</a></li>
            <li><a href="<?php echo url('product.php'); ?>" class="active">Products</a></li>
            <li><a href="<?php echo url('contact.html'); ?>">Contact</a></li>
            <li><a href="<?php echo url('about.html'); ?>">About Us</a></li>
            <?php if ($logged_uid > 0): ?>
                <li><a href="product.php" style="color: #ffcccc;">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo url('login.php'); ?>">Login</a></li>
            <?php endif; ?>
        </ul>
        <div class="cart-icon">
            <a href="<?php echo url('cart.php'); ?>">🛒 <span id="cart-count">0</span></a>
        </div>
    </nav>

    <div class="breadcrumb-container">
        <div class="breadcrumb">
            <a href="<?php echo url('home.php'); ?>">Home</a> ›
            <a href="<?php echo url('product.php'); ?>">Products</a> ›
            <span><?php echo htmlspecialchars($product['name']); ?></span>
        </div>
    </div>

    <main class="details-container">
        <div class="product-image">
            <img src="<?php echo htmlspecialchars($product['image_url']); ?>"
                alt="<?php echo htmlspecialchars($product['name']); ?>">
        </div>
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['name']); ?></h1>
            <div class="price"><?php echo htmlspecialchars($product['price']); ?> EGP</div>
            <p><?php echo htmlspecialchars($product['description']); ?></p>

            <div class="quantity-container">
                <label for="product-qty">Qty:</label>
                <input type="number" id="product-qty" value="1" min="1">
            </div>

            <button class="add-to-cart" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                Add to Cart
            </button>
            <br>
            <a href="<?php echo url('product.php'); ?>" class="back-btn">← Back to Products</a>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>PharmaCare</h3>
                <p>Your trusted source for medicine & digital healthcare.</p>
            </div>
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo url('home.php'); ?>">Home</a></li>
                    <li><a href="<?php echo url('product.php'); ?>">Products</a></li>
                    <li><a href="<?php echo url('contact.html'); ?>">Contact</a></li>
                    <li><a href="<?php echo url('about.html'); ?>">About us</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Support</h4>
                <p>Email: support@pharma.com</p>
                <p>Phone: +20 100 000 0000</p>
            </div>
        </div>
        <div class="footer-bottom">© 2025 PharmaCare — All rights reserved.</div>
    </footer>

    <script>
        const qs = '<?php echo $logged_uid > 0 ? "uid=$logged_uid" : "guest=$guest_id"; ?>';

        fetch(`product-details.php?action=count&${qs}`)
            .then(res => res.json())
            .then(data => {
                document.getElementById("cart-count").textContent = data.count || 0;
            });

        function addToCart(productId) {
            const qty = parseInt(document.getElementById("product-qty").value) || 1;
            const btn = document.querySelector(".add-to-cart");
            const originalText = btn.textContent;
            const originalColor = btn.style.backgroundColor;

            btn.disabled = true;

            fetch(`product-details.php?id=${productId}&action=add&qty=${qty}&${qs}`)
                .then(res => res.json())
                .then(data => {
                    if (data.status === "success") {
                        document.getElementById("cart-count").textContent = data.count;
                        btn.textContent = "Added ✓";
                        btn.style.backgroundColor = "#28a745";

                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.style.backgroundColor = originalColor;
                            btn.disabled = false;
                        }, 1500);
                    }
                })
                .catch(err => {
                    console.error(err);
                    btn.disabled = false;
                });
        }
    </script>

</body>

</html>