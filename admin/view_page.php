<?php
include '../includes/connection.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';
$success_msg = $warning_msg = '';

if (isset($_POST['logout'])) {
    // Destroy the session
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect the user to the login page (or any other page after logout)
    header('Location: index.php');
    exit; // Make sure to call exit after header redirection
}

// CSRF Token Generation
if (!isset($_SESSION['token'])) {
    $_SESSION['token'] = bin2hex(random_bytes(32));
}

if (isset($_POST['add_to_cart'])) {
    if (empty($user_id)) {
        $warning_msg = 'Please log in to add products to your cart';
        header("Location: view_page.php?pid=$product_id&warning_msg=" . urlencode($warning_msg));
        exit();
    } elseif (!isset($_POST['token']) || $_POST['token'] !== $_SESSION['token']) {
        die("Invalid request");
    } else {
        $id = unique_id();
        $product_id = $_POST['product_id'];
        $qty = filter_var($_POST['qty'], FILTER_SANITIZE_NUMBER_INT);

        // Check if product exists
        $check_product = $conn->prepare("SELECT price FROM `products` WHERE id = ? LIMIT 1");
        $check_product->execute([$product_id]);
        $fetch_product = $check_product->fetch(PDO::FETCH_ASSOC);

        if (!$fetch_product) {
            $warning_msg = 'Product not found';
        } else {
            // Check if product is already in cart
            $varify_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND product_id = ?");
            $varify_cart->execute([$user_id, $product_id]);

            // Limit cart items
            $max_cart_items = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
            $max_cart_items->execute([$user_id]);
            $cart_count = $max_cart_items->fetchColumn();

            if ($varify_cart->rowCount() > 0) {
                $warning_msg = 'Product already exists in your cart';
            } elseif ($cart_count >= 20) {
                $warning_msg = 'Cart is already full';
            } else {
                // Insert into cart
                $insert_cart = $conn->prepare("INSERT INTO `cart`(id, user_id, product_id, price, qty) VALUES(?,?,?,?,?)");
                $insert_cart->execute([$id, $user_id, $product_id, $fetch_product['price'], $qty]);
                $success_msg = 'Product added to cart successfully';
            }
        }
    }

    // Redirect to prevent form resubmission
    header("Location: view_page.php?pid=$product_id&success_msg=$success_msg&warning_msg=$warning_msg");
    exit();
}
?>

<?php $page = 'products'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Product Detail</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Product Detail</h1>
        </div>

        <div class="title2">
            <a href="view_products.php" class="btn" style="color: #000;"><i class='bx bx-arrow-back'></i></a>
            </a><span>/ Product Detail</span>
        </div>

        <section class="view-page">
            <?php
            if (isset($_GET['pid'])) {
                $pid = $_GET['pid'];
                $select_products = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
                $select_products->execute([$pid]);

                if ($select_products->rowCount() > 0) {
                    while ($fetch_products = $select_products->fetch(PDO::FETCH_ASSOC)) {
                        // Fetch any active discount
                        $today = date('Y-m-d');
                        $get_discount = $conn->prepare("SELECT * FROM `discounts` WHERE start_date <= ? AND end_date >= ?");
                        $get_discount->execute([$today, $today]);
                        $discount = $get_discount->fetch(PDO::FETCH_ASSOC);

                        $original_price = $fetch_products['price'];
                        $discounted_price = $original_price;

                        if ($discount) {
                            $discounted_price = $original_price - ($original_price * ($discount['discount_percentage'] / 100));
                            $discounted_price = number_format($discounted_price, 2);
                        }

                        ?>
                        <form method="post">
                            <img src="../admin/<?php echo $fetch_products['images']; ?>">
                            <div class="detail">
                                <div class="name">
                                    <?= $fetch_products['name']; ?>
                                </div>
                                <div class="product-detail">
                                    <h1>Origin:</h1>
                                    <p><?= $fetch_products['description']; ?></p>
                                </div>
                                <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
                                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                                <div class="price">
                                    <?php if ($discount && $original_price != $discounted_price): ?>
                                        <span class="old-price">₱<?= number_format($original_price, 2); ?></span>
                                        <span class="discount-label">₱<?= $discounted_price; ?></span>
                                        <p style="color: green;">Promo: <?= htmlspecialchars($discount['title']) ?></p>
                                    <?php else: ?>
                                        <p>₱<?= number_format($original_price, 2); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php
                                $stock = $fetch_products['stock'];
                                if ($stock == 0): ?>
                                    <div style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:10px;padding:1rem 1.5rem;margin:1rem 0;font-size:1.4rem;font-weight:bold;">
                                        ❌ Out of Stock — <a href="stock.php" style="color:#721c24;">Update stock →</a>
                                    </div>
                                <?php elseif ($stock <= 10): ?>
                                    <div style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:10px;padding:1rem 1.5rem;margin:1rem 0;font-size:1.4rem;font-weight:bold;">
                                        ⚠️ Low Stock: <?= $stock ?> remaining — <a href="stock.php" style="color:#856404;">Update stock →</a>
                                    </div>
                                <?php else: ?>
                                    <div style="background:#d4edda;color:#155724;border:1px solid #c3e6cb;border-radius:10px;padding:1rem 1.5rem;margin:1rem 0;font-size:1.4rem;font-weight:bold;">
                                        ✅ In Stock: <?= $stock ?> available
                                    </div>
                                <?php endif; ?>
                                <div class="button">
                                    <a class="btn" href="view_products.php?delete_product=<?= $fetch_products['id'] ?>"
                                        onclick="return confirm('Are you sure?')">DELETE</a>
                                    <a href="edit_product.php?pid=<?= $fetch_products['id']; ?>" class="btn">EDIT</a>
                                </div>
                            </div>
                        </form>
                        <div class="benefits">
                            <h1>Product Benefits</h1>
                            <p><?= $fetch_products['benefits']; ?></p>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p>No product found.</p>";
                }
            }
            ?>
        </section>

        <?php include '../includes/footer.php'; ?>
    </div>

    <style>
        .btn {
            padding: 2px 20px;
            line-height: normal;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>

<?php
function unique_id()
{
    return bin2hex(random_bytes(10));
}
?>