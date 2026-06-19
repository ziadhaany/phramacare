<?php
$host = 'localhost';
$dbname = 'pharmacare';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

$pdo->prepare("DELETE FROM cart WHERE total_price = 0 OR total_price IS NULL")->execute();

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if ($order_id <= 0) {
    header("Location: home.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT o.*, u.fname, u.lname, u.phone, u.role
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.user_id
    WHERE o.order_id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: home.php");
    exit;
}

$stmtItems = $pdo->prepare("
    SELECT oi.*, p.name
    FROM order_item oi
    JOIN product p ON oi.product_id = p.product_id
    WHERE oi.order_id = ?
");
$stmtItems->execute([$order_id]);
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

$stmtUser = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
$stmtUser->execute([$order['user_id']]);
$userData = $stmtUser->fetch(PDO::FETCH_ASSOC);

if ($userData && strpos($userData['email'], '@temp.com') !== false) {
    $pdo->prepare("DELETE FROM users WHERE user_id=?")->execute([$order['user_id']]);
}

$pdo->prepare("DELETE FROM users WHERE role='guest'")->execute();

$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['unit_price'] * $item['quantity'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare • Order Received</title>
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
    <link rel="stylesheet" href="thankyou.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js"></script>
</head>

<body>
    <div class="container">
        <h1>Your Order Has Been Received</h1>

        <div class="order-info-box">
            <div class="info-item">
                <span class="label">Order Number:</span>
                <span class="value">#<?php echo htmlspecialchars($order['order_id']); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Date:</span>
                <span class="value"><?php echo date("F j, Y", strtotime($order['order_date'])); ?></span>
            </div>
            <div class="info-item">
                <span class="label">Total:</span>
                <span class="value"><?php echo number_format($order['total_amount'], 2); ?> EGP</span>
            </div>
            <div class="info-item">
                <span class="label">Payment Method:</span>
                <span class="value">
                    <?php
                    if ($order['payment_method'] == 'cash')
                        echo 'Cash on Delivery';
                    elseif ($order['payment_method'] == 'credit')
                        echo 'Credit/Debit Card';
                    else
                        echo ucfirst($order['payment_method']);
                    ?>
                </span>
            </div>
        </div>

        <p class="payment-instruction">
            <?php
            if ($order['payment_method'] == 'cash') {
                echo "Please prepare the exact amount of cash upon delivery.";
            } else {
                echo "Your online payment has been successfully processed.";
            }
            ?>
        </p>

        <h2>Order Details</h2>
        <table class="order-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td class="product-name">
                            <strong>× <?php echo intval($item['quantity']); ?>
                                <?php echo htmlspecialchars($item['name']); ?></strong>
                        </td>
                        <td>EGP <?php echo number_format($item['unit_price'] * $item['quantity'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>

                <tr>
                    <td>Subtotal:</td>
                    <td>EGP <?php echo number_format($subtotal, 2); ?></td>
                </tr>
                <tr>
                    <td>Shipping:</td>
                    <td>EGP <?php echo number_format($order['shipping_cost'] ?? 20, 2); ?> <span class="small-text">via
                            Flat rate</span></td>
                </tr>
                <tr>
                    <td>Payment Method:</td>
                    <td>
                        <?php
                        if ($order['payment_method'] == 'cash')
                            echo 'Cash on Delivery';
                        elseif ($order['payment_method'] == 'credit')
                            echo 'Credit/Debit Card';
                        else
                            echo ucfirst($order['payment_method']);
                        ?>
                    </td>
                </tr>
                <tr>
                    <td class="total-label">Total:</td>
                    <td class="total-amount">EGP <?php echo number_format($order['total_amount'], 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="address-container">
            <div class="address-column">
                <h3>Shipping Address</h3>
                <div class="address-details">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                    <br><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($order['phone']); ?>
                </div>
            </div>
        </div>

        <div style="text-align: center;">
            <button id="goHome">Back to Home</button>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('.container').hide().fadeIn(800);
            $('#goHome').on('click', function () {
                window.location.href = 'home.php';
            });

            $('.order-table tbody tr').hover(
                function () {
                    $(this).css('background-color', '#f5f5f5');
                },
                function () {
                    $(this).css('background-color', '');
                }
            );
        });
    </script>
</body>

</html>