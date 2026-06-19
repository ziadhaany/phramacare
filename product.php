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
    if ($logged_uid > 0)
        $params['uid'] = $logged_uid;
    elseif (!empty($guest_id))
        $params['guest'] = $guest_id;
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
    if ($row)
        $currentUserId = $row['user_id'];
    else {
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
    $cartId = $cartRow ? $cartRow['cart_id'] : null;

    if (!$cartId)
    {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, total_price) VALUES (?, 0)");
        $stmt->execute([$currentUserId]);
        $cartId = $conn->lastInsertId();
    }

    if ($_GET['action'] == "add" && isset($_GET['id'])) {
        $prodId = intval($_GET['id']);
        $qty = 1;

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
            $newQty = $item['quantity'] + 1;
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

$minPrice = isset($_GET['min_price']) ? (int) $_GET['min_price'] : 0;
$maxPrice = isset($_GET['max_price']) ? (int) $_GET['max_price'] : 3000;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'default';
$categoryId = isset($_GET['category']) ? (int) $_GET['category'] : 0;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$productsPerPage = 15;

switch ($sort) {
    case 'price_asc':
        $orderBy = "price ASC";
        break;
    case 'price_desc':
        $orderBy = "price DESC";
        break;
    case 'name_asc':
        $orderBy = "name ASC";
        break;
    default:
        $orderBy = "product_id ASC";
}

$params = [':min' => $minPrice, ':max' => $maxPrice];
$whereSQL = " WHERE is_active=1 AND price BETWEEN :min AND :max";

if (!empty($search)) {
    $whereSQL .= " AND (name LIKE :search OR description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($categoryId > 0) {
    $whereSQL .= " AND category_id = :cat";
    $params[':cat'] = $categoryId;
}

$stmt = $conn->prepare(query: "SELECT COUNT(*) FROM product $whereSQL");

foreach ($params as $key => $val)
    $stmt->bindValue
    (
        $key,
        $val,
        is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR
    );
$stmt->execute();
$totalProducts = $stmt->fetchColumn();

$totalPages = ceil($totalProducts / $productsPerPage);
$startIndex = max(0, ($page - 1) * $productsPerPage);

$sql = "SELECT * FROM product $whereSQL ORDER BY $orderBy LIMIT $startIndex, $productsPerPage";
$stmt = $conn->prepare($sql);

foreach ($params as $key => $val)
    $stmt->bindValue($key, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
$stmt->execute();
$displayProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);


$allCategories = $conn->query("SELECT * FROM category ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$endDisplay = min($startIndex + $productsPerPage, $totalProducts);
$clearPriceUrl = url("product.php", ['page' => 1]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PharmaCare • Products</title>
    <link rel="stylesheet" href="product.css">
    <link rel="icon" href="images/icon-pharmacare.png" type="image/x-icon">
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
                <li><a href="product.php" style="color:#ffcccc;">Logout</a></li>
            <?php else: ?>
                <li><a href="<?php echo url('login.php'); ?>">Login</a></li><?php endif; ?>
        </ul>
        <div class="cart-icon">
            <a href="<?php echo url('cart.php'); ?>">🛒 <span id="cart-count">0</span></a>
        </div>
    </nav>

    <div class="breadcrumb-container" style="justify-content:space-between; padding:10px 5%;">
        <div class="breadcrumb"><a href="<?php echo url('home.php'); ?>">Home</a> › <span>Products</span></div>
        <form class="breadcrumb-search" method="GET" action="product.php">
            <?php if ($logged_uid > 0): ?><input type="hidden" name="uid"
                    value="<?php echo $logged_uid; ?>"><?php else: ?><input type="hidden" name="guest"
                    value="<?php echo htmlspecialchars($guest_id); ?>"><?php endif; ?>
            <input type="hidden" name="min_price" value="<?php echo $minPrice; ?>">
            <input type="hidden" name="max_price" value="<?php echo $maxPrice; ?>">
            <input type="hidden" name="category" value="<?php echo $categoryId; ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">
            <input type="text" name="search" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
        </form>
    </div>

    <div class="store-container">
        <aside class="sidebar">
            <h3>Shopping Options</h3>
            <form method="GET" action="product.php" id="filterForm">
                <?php if ($logged_uid > 0): ?><input type="hidden" name="uid"
                        value="<?php echo $logged_uid; ?>"><?php else: ?><input type="hidden" name="guest"
                        value="<?php echo htmlspecialchars($guest_id); ?>"><?php endif; ?>

                <div class="filter-header">
                    <h4>Filters</h4>
                    <a href="<?php echo $clearPriceUrl; ?>" class="clear-filter-link">Clear ×</a>
                </div>

                <div class="filter-group">
                    <h4>Categories</h4>
                    <div class="category-list">
                        <label class="custom-radio"><input type="radio" name="category" value="0" class="category-radio"
                                <?php if ($categoryId == 0)
                                    echo 'checked'; ?>><span class="radio-text">All
                                Categories</span></label>
                        <?php foreach ($allCategories as $cat): ?>
                            <label class="custom-radio"><input type="radio" name="category"
                                    value="<?php echo $cat['category_id']; ?>" class="category-radio" <?php if ($categoryId == $cat['category_id'])
                                           echo 'checked'; ?>><span
                                    class="radio-text"><?php echo htmlspecialchars($cat['name']); ?></span></label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Price Range</h4>
                    <div class="price-values">
                        <span>Min: <span id="minVal"><?php echo $minPrice; ?></span></span>
                        <span>Max: <span id="maxVal"><?php echo $maxPrice; ?></span></span>
                    </div>
                    <div class="slider-container">
                        <div class="slider-track"></div>
                        <input type="range" name="min_price" min="20" max="50000" step="10"
                            value="<?php echo $minPrice; ?>" class="slider" id="minPriceRange">
                        <input type="range" name="max_price" min="20" max="50000" step="10"
                            value="<?php echo $maxPrice; ?>" class="slider" id="maxPriceRange">
                    </div>
                </div>

                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="sort" id="hiddenSort" value="<?php echo htmlspecialchars($sort); ?>">
                <button type="submit" class="filter-btn">Apply Filters</button>
            </form>
        </aside>

        <section class="products-section">
            <div class="top-bar" style="display:flex; justify-content:space-between; align-items:center;">
                <p class="items-count">Showing <?php echo $startIndex + 1; ?>–<?php echo $endDisplay; ?> of
                    <?php echo $totalProducts; ?> items
                </p>
                <div class="sort-container">
                    <label>Sort by: </label>
                    <select id="sortSelect" class="sort-select">
                        <option value="default" <?php if ($sort == 'default')
                            echo 'selected'; ?>>Default</option>
                        <option value="price_asc" <?php if ($sort == 'price_asc')
                            echo 'selected'; ?>>Price: Low to High
                        </option>
                        <option value="price_desc" <?php if ($sort == 'price_desc')
                            echo 'selected'; ?>>Price: High to Low
                        </option>
                        <option value="name_asc" <?php if ($sort == 'name_asc')
                            echo 'selected'; ?>>Name: A-Z</option>
                    </select>
                </div>
            </div>

            <div class="product-grid">
                <?php if (count($displayProducts) > 0): ?>
                    <?php foreach ($displayProducts as $product): ?>
                        <div class="product-card">
                            <a href="<?php echo url('product-details.php', ['id' => $product['product_id']]); ?>"><img
                                    src="<?php echo htmlspecialchars($product['image_url']); ?>"
                                    alt="<?php echo htmlspecialchars($product['name']); ?>"></a>
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="price"><?php echo htmlspecialchars($product['price']); ?> EGP</p>
                            <button class="add-to-cart" data-id="<?php echo $product['product_id']; ?>"
                                style="padding:8px 16px; background-color:#0066d9;color:white;border:none;cursor:pointer;border-radius:20px;">Add
                                to Cart</button>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="grid-column:1/-1;text-align:center;padding:40px;color:#666;">No products found.</p>
                <?php endif; ?>
            </div>

            <div class="pagination">
                <?php
                $baseQ = ['search' => $search, 'min_price' => $minPrice, 'max_price' => $maxPrice, 'sort' => $sort, 'category' => $categoryId];
                if ($page > 1) {
                    $baseQ['page'] = $page - 1;
                    echo '<a href="' . url('product.php', $baseQ) . '" class="back-btn">« Back</a>';
                }
                for ($i = 1; $i <= $totalPages; $i++) {
                    $baseQ['page'] = $i;
                    $active = ($i == $page) ? 'active' : '';
                    echo '<a href="' . url('product.php', $baseQ) . '" class="' . $active . '">' . $i . '</a>';
                }
                if ($page < $totalPages) {
                    $baseQ['page'] = $page + 1;
                    echo '<a href="' . url('product.php', $baseQ) . '" class="next-btn">Next »</a>';
                }
                ?>
            </div>
        </section>
    </div>
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
        $(document).ready(function () {
            const qs = '<?php echo $logged_uid > 0 ? "uid=$logged_uid" : "guest=$guest_id"; ?>';

            fetch(`product.php?action=count&${qs}`).then(res => res.json()).then(data => $("#cart-count").text(data.count));

            $(".add-to-cart").click(function () {
                let btn = $(this), id = btn.data("id"), txt = btn.text(), color = btn.css("background-color");
                btn.prop("disabled", true);
                fetch(`product.php?action=add&id=${id}&${qs}`).then(res => res.json()).then(data => {
                    if (data.status == "success") {
                        $("#cart-count").text(data.count);
                        btn.text("Added ✓").css("background-color", "#28a745").hide().fadeIn(400);
                        setTimeout(() => { btn.text(txt).css("background-color", color).prop("disabled", false); }, 1500);
                    }
                });
            });

            const minSlider = document.getElementById("minPriceRange"), maxSlider = document.getElementById("maxPriceRange"),
                minVal = document.getElementById("minVal"), maxVal = document.getElementById("maxVal"), sliderTrack = document.querySelector(".slider-track"), sliderMax = 50000;

            function slideMin() {
                if (parseInt(maxSlider.value) - parseInt(minSlider.value) < 0)
                    minSlider.value = maxSlider.value; minVal.innerText = minSlider.value; fillTrack();
            }
            function slideMax() {
                if (parseInt(maxSlider.value) - parseInt(minSlider.value) < 0)
                    maxSlider.value = minSlider.value; maxVal.innerText = maxSlider.value; fillTrack();
            }
            function fillTrack() {
                let p1 = (minSlider.value / sliderMax) * 100, p2 = (maxSlider.value / sliderMax) * 100;
                sliderTrack.style.background = `linear-gradient(to right,#ddd ${p1}%,#0066d9 ${p1}%,#0066d9 ${p2}%,#ddd ${p2}%)`;
            }
            minSlider.addEventListener("input", slideMin); maxSlider.addEventListener("input", slideMax); fillTrack();

            $("#sortSelect").change(function () { $("#hiddenSort").val($(this).val()); $("#filterForm").submit(); });

            $(".category-radio").change(function () {
                let cat = $(this).val();
                let params = { search: $("input[name='search']").val(), min_price: $("input[name='min_price']").val(), max_price: $("input[name='max_price']").val(), sort: $("input[name='sort']").val(), category: cat };
        <?php if ($logged_uid > 0): ?> params.uid = "<?php echo $logged_uid; ?>"; <?php else: ?> params.guest = "<?php echo htmlspecialchars($guest_id); ?>"; <?php endif; ?>
                window.location.href = "product.php?" + $.param(params);
            });
        });
    </script>
</body>

</html>