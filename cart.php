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
$guest_token = $_GET['guest'] ?? null;

$currentUserId = 0;
if ($logged_uid) {
    $currentUserId = $logged_uid;
} elseif ($guest_token) {
    $fakeEmail = $guest_token . '@temp.com';
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$fakeEmail]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($u) {
        $currentUserId = $u['user_id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fname,lname,email,role) VALUES ('Guest','User',?,NULL)");
        $stmt->execute([$fakeEmail]);
        $currentUserId = $conn->lastInsertId();
    }
}
$stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id=? LIMIT 1");
$stmt->execute([$currentUserId]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);

if ($c) {
    $cartId = $c['cart_id'];
} else {
    $stmt = $conn->prepare("INSERT INTO cart (user_id,total_price) VALUES (?,0)");
    $stmt->execute([$currentUserId]);
    $cartId = $conn->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $pid = $_POST['id'] ?? null;
    $delivery_fee = 20;

    if ($action === 'clear') {
        $conn->prepare("DELETE FROM cart_item WHERE cart_id=?")->execute([$cartId]);
    } elseif ($pid) {
        if ($action === 'increase') {
            $conn->prepare("UPDATE cart_item SET quantity = quantity + 1 WHERE cart_id=? AND product_id=?")
                ->execute([$cartId, $pid]);
        } elseif ($action === 'decrease') {
            $q = $conn->prepare("SELECT quantity FROM cart_item WHERE cart_id=? AND product_id=?");
            $q->execute([$cartId, $pid]);
            $qty = $q->fetchColumn();
            if ($qty > 1) {
                $conn->prepare("UPDATE cart_item SET quantity = quantity - 1 WHERE cart_id=? AND product_id=?")
                    ->execute([$cartId, $pid]);
            } else {
                $conn->prepare("DELETE FROM cart_item WHERE cart_id=? AND product_id=?")
                    ->execute([$cartId, $pid]);
            }
        }
    }
    $items = $conn->prepare("SELECT product_id, quantity, unit_price FROM cart_item WHERE cart_id=?");
    $items->execute([$cartId]);
    $cart_items = $items->fetchAll(PDO::FETCH_ASSOC);

    $subtotal = 0;
    $quantity = 0;
    $subtotal_item_formatted = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['quantity'] * $item['unit_price'];
        if ($pid && $item['product_id'] == $pid) {
            $quantity = $item['quantity'];
            $subtotal_item_formatted = number_format($item['quantity'] * $item['unit_price'], 2);
        }
    }

    $total = $subtotal > 0 ? $subtotal + $delivery_fee : 0;
    $subtotal_formatted = number_format($subtotal, 2);
    $total_formatted = number_format($total, 2);

    $conn->prepare("UPDATE cart SET total_price = ? WHERE cart_id = ?")
        ->execute([$subtotal, $cartId]);

    echo json_encode([
        'quantity' => $quantity,
        'subtotal_formatted' => $subtotal_formatted,
        'subtotal_item_formatted' => $subtotal_item_formatted,
        'total_formatted' => $total_formatted,
        'delivery_fee' => $subtotal > 0 ? $delivery_fee : 0
    ]);
    exit;
}

$products_in_cart = [];
$total_price = 0;
$delivery_fee = 20;

$stmt = $conn->prepare("
    SELECT ci.product_id, ci.quantity, ci.unit_price,
           (ci.quantity * ci.unit_price) AS subtotal,
           p.name, p.image_url
    FROM cart_item ci
    JOIN product p ON ci.product_id = p.product_id
    WHERE ci.cart_id=?
");
$stmt->execute([$cartId]);
$products_in_cart = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($products_in_cart as $p) {
    $total_price += $p['subtotal'];
}

$guest_id = $guest_token ?? '';

function url($path, $params = [])
{
    global $logged_uid, $guest_token;
    if ($logged_uid)
        $params['uid'] = $logged_uid;
    elseif ($guest_token)
        $params['guest'] = $guest_token;
    return $path . '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PharmaCare • Cart</title>
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
    <link rel="stylesheet" href="cart.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js"></script>

</head>

<body>
    <header class="main-navbar">
        <div class="logo">💊 <span>PharmaCare</span></div>
        <nav class="nav-links">
            <a href="<?php echo url('home.php'); ?>">Home</a>
            <a href="<?php echo url('product.php'); ?>">Products</a>
            <a href="<?php echo url('contact.html'); ?>">Contact</a>
            <a href="<?php echo url('about.html'); ?>">About Us</a>
        </nav>
    </header>

    <div class="cart-header">
        <h1><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h1>
    </div>

    <main class="cart-container">
        <section class="cart-items">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products_in_cart)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:30px;">Your cart is currently empty.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products_in_cart as $row): ?>
                            <tr data-product-id="<?php echo $row['product_id']; ?>">
                                <td>
                                    <div class="item-info">
                                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" class="item-img"
                                            alt="Product Image">
                                        <span><?php echo htmlspecialchars($row['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($row['unit_price']); ?> EGP</td>
                                <td>
                                    <div class="qty-controls">
                                        <button class="qty-btn decrease">-</button>
                                        <input type="text" class="qty-input" value="<?php echo $row['quantity']; ?>" readonly>
                                        <button class="qty-btn increase">+</button>
                                    </div>
                                </td>
                                <td class="subtotal"><?php echo number_format($row['subtotal'], 2); ?> EGP</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <div class="cart-actions">
                <div class="left-actions">
                    <a href="<?php echo url('product.php'); ?>" class="btn-link">
                        <button class="updateBtn">Continue Shopping</button>
                    </a>
                </div>
                <div class="right-actions">
                    <?php if (!empty($products_in_cart)): ?>
                        <button id="clear-cart" class="clearBtn">Clear Cart</button>
                    <?php endif; ?>
                </div>
            </div>
        </section>

        <div class="cart-summary">
            <p>Subtotal: <span class="subtotal-total"><?php echo number_format($total_price, 2); ?> EGP</span></p>
            <p>Delivery: <span class="delivery-fee"><?php echo ($total_price > 0) ? $delivery_fee : 0; ?> EGP</span></p>
            <hr class="summary-divider">
            <p class="total">Total: <span
                    class="total-price"><?php echo number_format(($total_price > 0 ? $total_price + $delivery_fee : 0), 2); ?>
                    EGP</span></p>

            <?php if ($total_price > 0): ?>
                <a href="<?php echo url('checkout.php'); ?>" class="btn-link">
                    <button class="checkout-btn">Proceed to Checkout</button>
                </a>
            <?php else: ?>
                <button class="checkout-btn" disabled style="opacity:0.6; cursor:not-allowed;">Proceed to Checkout</button>
            <?php endif; ?>
        </div>
    </main>

    <footer class="main-footer">
        <div class="footer-container">
            <div class="footer-column">
                <h3>PharmaCare</h3>
                <p>Your trusted source for quality medicines and healthcare products.</p>
            </div>
            <div class="footer-column">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="<?php echo url('home.php'); ?>">Home</a></li>
                    <li><a href="<?php echo url('product.php'); ?>">Products</a></li>
                    <li><a href="<?php echo url('contact.html'); ?>">Contact</a></li>
                    <li><a href="<?php echo url('about.html'); ?>">About Us</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h4>Contact</h4>
                <p>Email: support@Pharma.com</p>
                <p>Phone: +20 123 456 789</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>© 2025 Pharma. All Rights Reserved.</p>
        </div>
    </footer>

    <script>
        $(document).ready(function () {
            function updateCartUI(data, productId, isClearing = false) {
                if (isClearing) {
                    $('tr[data-product-id]').remove();
                } else if (productId) {
                    const row = $('tr[data-product-id="' + productId + '"]');
                    if (data.quantity == 0) { row.remove(); }
                    else {
                        row.find('.qty-input').val(data.quantity);
                        row.find('.subtotal').text(data.subtotal_item_formatted + ' EGP');
                    }
                }
                $('.subtotal-total').text(data.subtotal_formatted + ' EGP');
                $('.total-price').text(data.total_formatted + ' EGP');
                $('.delivery-fee').text(data.delivery_fee + ' EGP');

                if ($('tr[data-product-id]').length == 0) {
                    $('.cart-items tbody').append('<tr><td colspan="4" style="text-align:center;padding:30px;">Your cart is currently empty.</td></tr>');
                    $('#clear-cart').remove();
                    $('.checkout-btn').prop('disabled', true).css({ opacity: 0.6, cursor: 'not-allowed' });
                }
            }

            $('.qty-btn.increase, .qty-btn.decrease').click(function () {
                const btn = $(this);
                const row = btn.closest('tr');
                const productId = row.data('product-id');
                const action = btn.hasClass('increase') ? 'increase' : 'decrease';
                $.post('', { action: action, id: productId, guest: '<?php echo $guest_id; ?>', uid: '<?php echo $logged_uid; ?>' }, function (response) {
                    updateCartUI(response, productId);
                }, 'json');
            });

            $('#clear-cart').click(function () {
                if (!confirm('Are you sure you want to clear the cart?')) return;
                $.post('', { action: 'clear', guest: '<?php echo $guest_id; ?>', uid: '<?php echo $logged_uid; ?>' }, function (response) {
                    updateCartUI(response, null, true);
                }, 'json');
            });
        });
    </script>

</body>

</html>