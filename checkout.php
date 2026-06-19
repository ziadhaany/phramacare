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
function validateEmail($email)
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}
function validateName($name)
{
    return preg_match('/^[\p{L}\s]+$/u', trim($name)) && strlen(trim($name)) >= 2;
}
function validatePhone($phone)
{
    return preg_match('/^\+?[0-9]{10,15}$/', trim($phone));
}
function validateAddress($address)
{
    return strlen(trim($address)) >= 5;
}

$logged_uid = isset($_GET['uid']) ? intval($_GET['uid']) : 0;
$guest_token = $_GET['guest'] ?? null;
$currentUserId = 0;

if ($logged_uid) {
    $currentUserId = $logged_uid;
} elseif ($guest_token) {
    $guest_token = preg_replace('/[^a-zA-Z0-9_-]/', '', $guest_token);
    $fakeEmail = $guest_token . '@temp.com';
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$fakeEmail]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($u) {
        $currentUserId = $u['user_id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO users (fname,lname,email,role) VALUES ('Guest','User',?, 'guest')");
        $stmt->execute([$fakeEmail]);
        $currentUserId = $conn->lastInsertId();
    }
}

$deliveryFee = 20;
$cartItemsDisplay = [];
$subtotal = 0;
$stmt = $conn->prepare("SELECT cart_id FROM cart WHERE user_id=? LIMIT 1");
$stmt->execute([$currentUserId]);
$c = $stmt->fetch(PDO::FETCH_ASSOC);
$cartId = $c['cart_id'] ?? 0;

if ($cartId) {
    $stmt = $conn->prepare("
        SELECT ci.product_id, ci.quantity AS qty, ci.unit_price,
               (ci.quantity*ci.unit_price) AS line_total,
               p.name, p.image_url AS image
        FROM cart_item ci
        JOIN product p ON ci.product_id=p.product_id
        WHERE ci.cart_id=?
    ");
    $stmt->execute([$cartId]);
    $cartItemsDisplay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($cartItemsDisplay as $item) {
        $subtotal += $item['line_total'];
    }
}

$isCartEmpty = empty($cartItemsDisplay);
$totalAmount = $subtotal + ($isCartEmpty ? 0 : $deliveryFee);
$orderIdDisplay = '';
$orderSuccess = false;
$popupMessage = '';
$errorMsg = '';
$redirectUrl = '';
$fieldErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $postal = trim($_POST['postal'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $paymentPost = $_POST['payment'] ?? 'cod';
    $errors = [];
    
    if (!validateEmail($email)) {
        $fieldErrors['email'] = "Invalid email address";
    }
    if (!validateName($fname)) {
        $fieldErrors['fname'] = "First name is invalid (letters only, at least 2 characters)";
    }
    if (!validateName($lname)) {
        $fieldErrors['lname'] = "Last name is invalid (letters only, at least 2 characters)";
    }
    if (!validateAddress($address)) {
        $fieldErrors['address'] = "Address is too short (at least 5 characters)";
    }
    if (!validatePhone($phone)) {
        $fieldErrors['phone'] = "Phone number is invalid (10-15 digits)";
    }
    if (empty($city)) {
        $fieldErrors['city'] = "City is required";
    }
    if (!empty($fieldErrors)) {
        $errorMsg = "Please fix the errors below";
    } else {
        $payment = 'cash';
        if ($paymentPost === 'cod')
            $payment = 'cash';
        elseif ($paymentPost === 'online')
            $payment = 'credit';

        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existingUser) {
            $currentUserId = $existingUser['user_id'];
            $stmt = $conn->prepare("UPDATE users SET fname=?, lname=?, street=?, city=?, zip_code=?, phone=? WHERE user_id=?");
            $stmt->execute([$fname, $lname, $address, $city, $postal, $phone, $currentUserId]);
        } else {
            $stmt = $conn->prepare("INSERT INTO users (fname,lname,email,street,city,zip_code,phone,role) VALUES (?,?,?,?,?,?,?,NULL)");
            $stmt->execute([$fname, $lname, $email, $address, $city, $postal, $phone]);
            $currentUserId = $conn->lastInsertId();
        }
        if (!$isCartEmpty) {
            $shipping_address = "$address, $city, $state, $postal";
            $stmt = $conn->prepare("INSERT INTO orders (user_id, cart_id, total_amount, shipping_address, payment_method) VALUES (?,?,?,?,?)");
            $stmt->execute([$currentUserId, $cartId, $totalAmount, $shipping_address, $payment]);
            $orderIdDisplay = $conn->lastInsertId();
            $orderSuccess = true;
            $popupMessage = "Your order has been created successfully!";
            $stmtItems = $conn->prepare("SELECT product_id, quantity, unit_price FROM cart_item WHERE cart_id=?");
            $stmtItems->execute([$cartId]);
            $cartItems = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cartItems as $item) {
                $stmtInsert = $conn->prepare("INSERT INTO order_item (order_id, product_id, unit_price, quantity) VALUES (?,?,?,?)");
                $stmtInsert->execute([$orderIdDisplay, $item['product_id'], $item['unit_price'], $item['quantity']]);
            }
            $stmt = $conn->prepare("UPDATE users SET role=NULL WHERE user_id=?");
            $stmt->execute([$currentUserId]);
            $conn->prepare("DELETE FROM cart_item WHERE cart_id=?")->execute([$cartId]);
            $conn->prepare("UPDATE cart SET total_price=0 WHERE cart_id=?")->execute([$cartId]);
            $cartItemsDisplay = [];
            $subtotal = 0;
            $totalAmount = 0;
            $isCartEmpty = true;
            if ($payment === 'cash') {
                $redirectUrl = "thankyou.php?order_id=$orderIdDisplay";
            } else {
                $redirectUrl = "visa.html?order_id=$orderIdDisplay";
            }
        } else {
            $errorMsg = "Cart is empty! Cannot complete order.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare • Checkout</title>
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
    <link rel="stylesheet" href="checkout.css" />
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="animations.css">
    <script src="animations.js"></script>
</head>

<body>

    <form class="checkout-container" method="POST" action="">
        <h2>💊<b>PharmaCare</b></h2>

        <?php if ($errorMsg): ?>
            <div class="error-banner"><?php echo nl2br(htmlspecialchars($errorMsg)); ?></div>
        <?php endif; ?>

        <div class="form-section contact-header">
            <div class="contact-top">
                <h3>Contact</h3>
                <a href="login.php" class="signin-link">Sign In</a>
            </div>
            <div class="field-group <?php echo isset($fieldErrors['email']) ? 'input-error' : ''; ?>">
                <input type="email" name="email" id="email" placeholder="Email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <div class="error-msg">
                    <?php echo isset($fieldErrors['email']) ? htmlspecialchars($fieldErrors['email']) : 'Enter a valid email address'; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Delivery</h3>
            <div class="field-group">
                <select name="country" required>
                    <option value="Egypt">Egypt</option>
                </select>
            </div>
            <div class="form-row">
                <div class="field-group form-col <?php echo isset($fieldErrors['fname']) ? 'input-error' : ''; ?>">
                    <input type="text" name="fname" id="fname" placeholder="First name" required maxlength="50"
                        value="<?php echo isset($_POST['fname']) ? htmlspecialchars($_POST['fname']) : ''; ?>">
                    <div class="error-msg">
                        <?php echo isset($fieldErrors['fname']) ? htmlspecialchars($fieldErrors['fname']) : 'Enter a valid first name'; ?>
                    </div>
                </div>
                <div class="field-group form-col <?php echo isset($fieldErrors['lname']) ? 'input-error' : ''; ?>">
                    <input type="text" name="lname" id="lname" placeholder="Last name" required maxlength="50"
                        value="<?php echo isset($_POST['lname']) ? htmlspecialchars($_POST['lname']) : ''; ?>">
                    <div class="error-msg">
                        <?php echo isset($fieldErrors['lname']) ? htmlspecialchars($fieldErrors['lname']) : 'Enter a valid last name'; ?>
                    </div>
                </div>
            </div>
            <div class="field-group <?php echo isset($fieldErrors['address']) ? 'input-error' : ''; ?>">
                <input type="text" name="address" id="address" placeholder="Address" required maxlength="200"
                    value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>">
                <div class="error-msg">
                    <?php echo isset($fieldErrors['address']) ? htmlspecialchars($fieldErrors['address']) : 'Enter a valid address'; ?>
                </div>
            </div>
            <div class="form-row">
                <div class="field-group form-col <?php echo isset($fieldErrors['city']) ? 'input-error' : ''; ?>">
                    <input type="text" name="city" id="city" placeholder="City" required maxlength="50"
                        value="<?php echo isset($_POST['city']) ? htmlspecialchars($_POST['city']) : ''; ?>">
                    <div class="error-msg">
                        <?php echo isset($fieldErrors['city']) ? htmlspecialchars($fieldErrors['city']) : 'Enter a valid city'; ?>
                    </div>
                </div>
                <div class="field-group form-col">
                    <select name="state">
                        <option value="Giza" <?php echo (isset($_POST['state']) && $_POST['state'] === 'Giza') ? 'selected' : ''; ?>>Giza</option>
                        <option value="Cairo" <?php echo (isset($_POST['state']) && $_POST['state'] === 'Cairo') ? 'selected' : ''; ?>>Cairo</option>
                    </select>
                </div>
                <div class="field-group form-col">
                    <input type="text" name="postal" placeholder="Postal code" maxlength="10"
                        value="<?php echo isset($_POST['postal']) ? htmlspecialchars($_POST['postal']) : ''; ?>">
                </div>
            </div>
            <div class="field-group <?php echo isset($fieldErrors['phone']) ? 'input-error' : ''; ?>">
                <input type="tel" name="phone" id="phone" placeholder="Phone" required maxlength="20"
                    value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                <div class="error-msg">
                    <?php echo isset($fieldErrors['phone']) ? htmlspecialchars($fieldErrors['phone']) : 'Enter a valid phone number'; ?>
                </div>
            </div>
        </div>

        <div class="form-section">
            <h3>Shipping method</h3>
            <div class="shipping-box">
                <div>
                    <div class="shipping-name">Standard</div>
                    <div class="shipping-time">1-2 days</div>
                </div>
                <div class="shipping-price">E£<?php echo number_format($deliveryFee, 2); ?></div>
            </div>
        </div>

        <div class="form-section">
            <h3>Payment</h3>
            <div class="payment-wrapper">
                <div class="payment-option active" id="option-online">
                    <label class="payment-header selected">
                        <input type="radio" name="payment" value="online" checked>
                        <span class="payment-label">Debit/Credit cards</span>
                    </label>
                </div>
                <div class="payment-option" id="option-cod">
                    <label class="payment-header">
                        <input type="radio" name="payment" value="cod">
                        <span class="payment-label">Cash on Delivery (COD)</span>
                    </label>
                </div>
            </div>
        </div>

        <div id="codSuccessPopup"
            class="popup-overlay <?php echo ($orderSuccess && !empty($redirectUrl) && strpos($redirectUrl, 'thankyou.php') !== false) ? 'show-popup' : ''; ?>">
            <div class="popup-box">
                <h2>✔️ Order Created!</h2>
                <p>Order #<?php echo htmlspecialchars($orderIdDisplay); ?></p>
                <p><?php echo htmlspecialchars($popupMessage); ?></p>
                <button type="button"
                    onclick="window.location.href='<?php echo htmlspecialchars($redirectUrl); ?>'">OK</button>
            </div>
        </div>

        <button class="pay-btn" type="submit" <?php if ($isCartEmpty)
            echo 'disabled style="opacity:0.5; cursor:not-allowed;"'; ?>>
            <?php echo $isCartEmpty ? 'Cart is Empty' : 'Pay E£' . number_format($totalAmount, 2); ?>
        </button>
    </form>

    <div class="right-side" id="orderSummary">
        <?php if (!$isCartEmpty): ?>
            <div class="order-items-container">
                <?php foreach ($cartItemsDisplay as $item): ?>
                    <div class="summary-item">
                        <div class="item-left">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" class="item-img"
                                    alt="<?php echo htmlspecialchars($item['name']); ?>">
                            <?php endif; ?>
                            <div class="item-info">
                                <div><?php echo htmlspecialchars($item['name']); ?></div>
                                <span>Qty: <?php echo intval($item['qty']); ?></span>
                            </div>
                        </div>
                        <div class="item-price">E£<?php echo number_format($item['line_total'], 2); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="empty-cart-msg">Your cart is empty</p>
        <?php endif; ?>
        <div class="summary-row">
            <span>Subtotal</span>
            <span id="subtotalVal">E£<?php echo number_format($subtotal, 2); ?></span>
        </div>
        <div class="summary-row">
            <span>Shipping</span>
            <span id="shippingVal">E£<?php echo number_format($deliveryFee, 2); ?></span>
        </div>
        <div class="divider"></div>
        <div class="total-row">
            <span>Total</span>
            <span id="totalVal">E£<?php echo number_format($totalAmount, 2); ?></span>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            const togglePayment = (method) => {
                const $onlineOpt = $('#option-online');
                const $codOpt = $('#option-cod');

                $onlineOpt.toggleClass('active', method === 'online');
                $onlineOpt.find('.payment-header').toggleClass('selected', method === 'online');

                $codOpt.toggleClass('active', method === 'cod');
                $codOpt.find('.payment-header').toggleClass('selected', method === 'cod');
            };

            $('input[name="payment"]').on('change', function () {
                togglePayment($(this).val());
            });
            const validators = {
                email: val => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val),
                nameOnly: val => /^[\p{L}\s]+$/u.test(val.trim()) && val.trim().length >= 2,
                phoneOnly: val => /^\+?[0-9]{10,15}$/.test(val.trim()),
                addressMin: val => val.trim().length >= 5,
                notEmpty: val => val.trim().length > 0
            };
            const formFields = [
                { id: 'email', validator: validators.email },
                { id: 'fname', validator: validators.nameOnly },
                { id: 'lname', validator: validators.nameOnly },
                { id: 'address', validator: validators.addressMin },
                { id: 'city', validator: validators.notEmpty },
                { id: 'phone', validator: validators.phoneOnly }
            ];
            const validateField = ({ id, validator }) => {
                const $input = $('#' + id);
                if (!$input.length) return true;

                const valid = validator($input.val());
                $input.parent().toggleClass('input-error', !valid);
                $input.siblings('.error-msg').css('display', valid ? 'none' : 'block');
                return valid;
            };
            formFields.forEach(field => {
                $('#' + field.id).on('input blur', () => validateField(field));
            });
            $('.checkout-container').on('submit', function (e) {
                const isFormValid = formFields.every(validateField);

                if (!isFormValid) {
                    e.preventDefault();
                    if ($('.input-error').length) {
                        $('.input-error').first()[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
            <?php if ($orderSuccess && !empty($redirectUrl)): ?>
                <?php if (strpos($redirectUrl, 'thankyou.php') !== false): ?>
                    setTimeout(() => {
                        window.location.href = '<?php echo htmlspecialchars($redirectUrl); ?>';
                    }, 3000);
                <?php else: ?>
                    window.location.href = '<?php echo htmlspecialchars($redirectUrl); ?>';
                <?php endif; ?>
            <?php endif; ?>
        });

    </script>
</body>

</html>