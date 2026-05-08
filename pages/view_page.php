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
        $warning_msg = 'Please log in to add cookies to your cart';
        $product_id = $_POST['product_id'] ?? '';
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
            $warning_msg = 'Cookie not found';
        } else {
            // Check if product is already in cart
            $varify_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND product_id = ?");
            $varify_cart->execute([$user_id, $product_id]);

            // Limit cart items
            $max_cart_items = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
            $max_cart_items->execute([$user_id]);
            $cart_count = $max_cart_items->fetchColumn();

            if ($varify_cart->rowCount() > 0) {
                $warning_msg = 'Cookie already exists in your cart';
            } elseif ($cart_count >= 20) {
                $warning_msg = 'Your cookie jar is full (max 20 items)';
            } else {
                // Insert into cart
                $insert_cart = $conn->prepare("INSERT INTO `cart`(user_id, product_id, price, qty) VALUES(?,?,?,?)");
                $insert_cart->execute([$user_id, $product_id, $fetch_product['price'], $qty]);
                $success_msg = 'Cookie added to your jar successfully! 🍪';
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
    <title>Tender Loving Cookies - Cookie Details</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Cookie-themed view page styles */
        .banner {
            background-image: url('../images/cookie-banner-detail.jpg');
        }
        
        .view-page {
            background-image: url('../images/bg.jpg');
            padding: 5% 8%;
        }
        
        .view-page form {
            background: #fff;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: var(--shadow);
            border: 3px solid var(--cookie-tan);
            transition: all 0.3s ease;
        }
        
        .view-page form:hover {
            border-color: var(--cookie-brown);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.15);
        }
        
        .view-page form img {
            max-width: 400px;
            width: 100%;
            border-radius: 20px;
            box-shadow: var(--shadow);
            border: 3px solid var(--cookie-tan);
            transition: transform 0.3s ease;
        }
        
        .view-page form img:hover {
            transform: scale(1.02);
        }
        
        .view-page .detail {
            padding: 2rem;
        }
        
        .view-page .name {
            font-size: 3.5rem !important;
            color: var(--cookie-chocolate);
            margin-bottom: 1.5rem;
            border-bottom: 3px solid var(--cookie-tan);
            padding-bottom: 1rem;
            position: relative;
        }
        
        .view-page .name::after {
            content: '🍪';
            position: absolute;
            right: 0;
            top: 0;
            font-size: 3rem;
            opacity: 0.3;
        }
        
        .product-detail {
            background: var(--cookie-cream);
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
        }
        
        .product-detail h1 {
            color: var(--cookie-brown);
            font-size: 2.2rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .product-detail p {
            color: #555;
            font-size: 1.6rem;
            line-height: 1.8;
        }
        
        .view-page .price {
            background: linear-gradient(135deg, var(--cookie-warm), var(--cookie-cream));
            padding: 2rem;
            border-radius: 15px;
            margin: 2rem 0;
            text-align: center;
        }
        
        .view-page .price p {
            font-size: 2.5rem;
            color: var(--cookie-chocolate);
            font-weight: bold;
        }
        
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 2rem;
            margin-right: 1rem;
        }
        
        .discount-label {
            background: #ff6b6b;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-size: 2rem;
            font-weight: bold;
            display: inline-block;
        }
        
        .price p:last-child {
            color: #28a745;
            font-size: 1.6rem;
            margin-top: 1rem;
        }
        
        .view-page .button {
            display: flex;
            gap: 1rem;
            align-items: center;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .view-page .button .quantity {
            width: 80px;
            height: 50px;
            text-align: center;
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            font-size: 1.8rem;
            color: var(--cookie-chocolate);
        }
        
        .view-page .button .quantity:focus {
            border-color: var(--cookie-brown);
            outline: none;
        }
        
        .view-page .button .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1.2rem 3rem !important;
            font-size: 1.6rem;
            border-radius: 40px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            line-height: normal;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .view-page .button .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }
        
        .view-page .button .btn:last-child {
            background-color: var(--cookie-chocolate);
        }
        
        .view-page .button .btn:last-child:hover {
            background-color: var(--cookie-brown);
        }
        
        .ingredients,
        .benefits,
        .steps {
            background: #fff;
            border-radius: 20px;
            padding: 2.5rem;
            margin: 3rem 0;
            box-shadow: var(--shadow);
            border-left: 5px solid var(--cookie-brown);
            border-right: 5px solid var(--cookie-tan);
        }
        
        .ingredients h1,
        .benefits h1,
        .steps h1 {
            color: var(--cookie-chocolate);
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            padding-left: 1rem;
        }
        
        .ingredients h1::before,
        .benefits h1::before,
        .steps h1::before {
            content: '🍪';
            margin-right: 1rem;
            font-size: 2rem;
        }
        
        .ingredients p,
        .benefits p,
        .steps p {
            color: #555;
            font-size: 1.6rem;
            line-height: 1.8;
            padding: 1rem;
            background: var(--cookie-cream);
            border-radius: 10px;
        }
        
        .btn i {
            margin-right: 0.5rem;
            font-size: 1.8rem;
            vertical-align: middle;
        }
        
        .title2 .btn {
            background-color: var(--cookie-brown);
            color: #fff !important;
            padding: 1rem 2rem !important;
            font-size: 1.4rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .title2 .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateX(-5px);
        }
        
        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .view-page form {
                grid-template-columns: 1fr;
                padding: 2rem;
            }
            
            .view-page .name {
                font-size: 2.5rem !important;
            }
            
            .view-page .button {
                flex-direction: column;
            }
            
            .view-page .button .btn {
                width: 100%;
                text-align: center;
            }
            
            .product-detail h1 {
                font-size: 1.8rem;
            }
            
            .product-detail p {
                font-size: 1.4rem;
            }
        }
        
        @media (max-width: 480px) {
            .view-page .name {
                font-size: 2rem !important;
            }
            
            .ingredients h1,
            .benefits h1,
            .steps h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Cookie Details</h1>
        </div>

        <div class="title2">
            <a href="products.php" class="btn"><i class='bx bx-arrow-back'></i> Back to Cookies</a>
            <span>/ Cookie Details</span>
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
                            <img src="../admin/<?php echo $fetch_products['images']; ?>" alt="<?= $fetch_products['name']; ?> Cookie">
                            <div class="detail">
                                <div class="name">
                                    <?= $fetch_products['name']; ?>
                                </div>
                                <div class="product-detail">
                                    <h1>About This Cookie:</h1>
                                    <p><?= $fetch_products['description']; ?></p>
                                </div>
                                <input type="hidden" name="product_id" value="<?php echo $fetch_products['id']; ?>">
                                <input type="hidden" name="token" value="<?= $_SESSION['token']; ?>">
                                <div class="price">
                                    <?php if ($discount && $original_price != $discounted_price): ?>
                                        <span class="old-price">₱<?= number_format($original_price, 2); ?></span>
                                        <span class="discount-label">₱<?= $discounted_price; ?></span>
                                        <p style="color: #28a745;">🎉 Special Offer: <?= htmlspecialchars($discount['title']) ?></p>
                                    <?php else: ?>
                                        <p>₱<?= number_format($original_price, 2); ?></p>
                                    <?php endif; ?>
                                </div>

                                <?php
                                $stock = $fetch_products['stock'];
                                if ($stock == 0): ?>
                                    <div style="background:#f8d7da;color:#721c24;border:1px solid #f5c6cb;border-radius:10px;padding:1rem 1.5rem;margin:1rem 0;font-size:1.4rem;font-weight:bold;">
                                        ❌ Out of Stock — Check back soon!
                                    </div>
                                <?php elseif ($stock <= 10): ?>
                                    <div style="background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:10px;padding:1rem 1.5rem;margin:1rem 0;font-size:1.4rem;font-weight:bold;">
                                        ⚠️ Almost gone — only <?= $stock ?> left!
                                    </div>
                                <?php endif; ?>
                                <div class="button">
                                    <input type="number" name="qty" required min="1" class="quantity" value="1" max="99">
                                    <button type="submit" name="add_to_cart" class="btn"><i class="bx bx-cart-add"></i> Add to Jar</button>
                                    <a href="checkout.php?get_id=<?= $fetch_products['id']; ?>" class="btn"><i class="bx bx-basket"></i> Buy Now</a>
                                </div>
                            </div>
                        </form>
                        <div class="benefits">
                            <h1>Why You'll Love It</h1>
                            <p><?= $fetch_products['benefits']; ?></p>
                        </div>
                        <?php
                    }
                } else {
                    echo "<p style='text-align: center; font-size: 2rem; color: #666; padding: 5rem;'>🍪 Cookie not found. Please check our other delicious cookies!</p>";
                }
            }
            ?>
        </section>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
    
    <script>
        // Show success/warning messages if they exist in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');
        const warningMsg = urlParams.get('warning_msg');
        
        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: 'Yum! 🍪',
                text: decodeURIComponent(successMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
        
        if (warningMsg) {
            Swal.fire({
                icon: 'warning',
                title: 'Oops!',
                text: decodeURIComponent(warningMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
        
        // Quantity input validation
        document.querySelectorAll('.quantity').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) this.value = 1;
                if (this.value > 99) this.value = 99;
            });
        });
    </script>
</body>

</html>

<?php
function unique_id()
{
    return bin2hex(random_bytes(10));
}
?>