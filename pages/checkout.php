<?php
include '../includes/connection.php';
session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$today = date('Y-m-d');

// Fetch active global discount
$get_discount = $conn->prepare("SELECT * FROM discounts WHERE start_date <= ? AND end_date >= ? LIMIT 1");
$get_discount->execute([$today, $today]);
$active_discount = $get_discount->fetch(PDO::FETCH_ASSOC);

$discount_percent = $active_discount['discount_percentage'] ?? 0;

if (isset($_POST['place_order'])) {
    $grand_total = 0;
    $order_items = [];

    // Check if this is a Buy Now order (with quantity in URL)
    if (isset($_GET['buy_now']) && isset($_GET['pid']) && isset($_GET['qty'])) {
        $product_id = $_GET['pid'];
        $qty = $_GET['qty'];

        $select_product = $conn->prepare("SELECT * FROM `products` WHERE id=?");
        $select_product->execute([$product_id]);
        $product = $select_product->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $original_price = $product['price'];
            $final_price = $original_price;
            
            if ($discount_percent > 0) {
                $final_price = $original_price - ($original_price * ($discount_percent / 100));
            }

            $sub_total = ($qty * $final_price) + 100;
            $grand_total += $sub_total;

            $order_items[] = [
                'product_id' => $product_id,
                'qty' => $qty,
                'sub_total' => $sub_total
            ];
        }
    }
    // If there's a direct purchase (from 'Buy Now' without quantity - legacy support)
    elseif (isset($_GET['get_id'])) {
        $product_id = $_GET['get_id'];
        $qty = 1;  // Default to 1 if 'Buy Now' is clicked

        $select_product = $conn->prepare("SELECT * FROM `products` WHERE id=?");
        $select_product->execute([$product_id]);
        $product = $select_product->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $original_price = $product['price'];
            $final_price = $original_price;
            
            if ($discount_percent > 0) {
                $final_price = $original_price - ($original_price * ($discount_percent / 100));
            }

            $sub_total = ($qty * $final_price) + 100;
            $grand_total += $sub_total;

            $order_items[] = [
                'product_id' => $product_id,
                'qty' => $qty,
                'sub_total' => $sub_total
            ];
        }
    } else {
        // Fetch cart items
        $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id=?");
        $select_cart->execute([$user_id]);

        if ($select_cart->rowCount() > 0) {
            while ($cart_item = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                $select_product = $conn->prepare("SELECT * FROM `products` WHERE id=?");
                $select_product->execute([$cart_item['product_id']]);
                $product = $select_product->fetch(PDO::FETCH_ASSOC);

                if ($product) {
                    $original_price = $product['price'];
                    $final_price = $original_price;
                    
                    if ($discount_percent > 0) {
                        $final_price = $original_price - ($original_price * ($discount_percent / 100));
                    }

                    $sub_total = ($cart_item['qty'] * $final_price) + 100;
                    $grand_total += $sub_total;

                    $order_items[] = [
                        'product_id' => $cart_item['product_id'],
                        'qty' => $cart_item['qty'],
                        'sub_total' => $sub_total
                    ];
                }
            }
        } else {
            echo "<script>alert('Your cart is empty!'); window.location.href='products.php';</script>";
            exit();
        }
    }

    // Insert order into orders table
    $insert_order = $conn->prepare("INSERT INTO `orders` (user_id, total_price, order_date) VALUES (?, ?, NOW())");
    if ($insert_order->execute([$user_id, $grand_total])) {
        $order_id = $conn->lastInsertId();

        // Insert order details into order_items table
        foreach ($order_items as $item) {
            $insert_order_item = $conn->prepare("INSERT INTO `order_items` (order_id, product_id, qty, sub_total) VALUES (?, ?, ?, ?)");
            $insert_order_item->execute([$order_id, $item['product_id'], $item['qty'], $item['sub_total']]);

            // Deduct stock — floor at 0 so it never goes negative
            $deduct_stock = $conn->prepare("UPDATE `products` SET stock = GREATEST(0, stock - ?) WHERE id = ?");
            $deduct_stock->execute([$item['qty'], $item['product_id']]);
        }

        // Clear the cart ONLY if items were ordered from the cart (not Buy Now)
        if (!isset($_GET['get_id']) && !isset($_GET['buy_now'])) {
            $delete_cart = $conn->prepare("DELETE FROM `cart` WHERE user_id=?");
            $delete_cart->execute([$user_id]);
        }

        $success_msg = 'Product ordered successfully';
        // Redirect to confirmation page
        header("Location: products.php?success_msg=" . urlencode($success_msg));
        exit();
    } else {
        echo "<script>alert('Failed to place the order. Please try again later.'); window.location.href='products.php';</script>";
        exit();
    }
}

if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

?>

<?php $page = 'products'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Checkout</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        .info {
            margin: 0.5rem 0;
            font-size: 1.4rem;
        }
        .btn {
            padding: 2px 20px;
            line-height: normal;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Checkout Summary</h1>
        </div>

        <div class="title2">
            <a <?php 
            if (isset($_GET['get_id']) || isset($_GET['buy_now'])) {
                echo 'href="products.php"';
            } else {
                echo 'href="cart.php"';
            }
            ?>
            class="btn" style="color: #000;"><i class='bx bx-arrow-back'></i></a><span>/ Checkout Summary</span>
        </div>

        <?php if ($discount_percent > 0): ?>
        <div style="background: linear-gradient(135deg, var(--cookie-brown), var(--cookie-chocolate)); color: #fff; text-align: center; padding: 1rem; border-radius: 50px; margin: 1rem auto; max-width: 600px; font-size: 1.6rem;">
            🎉 <?= $discount_percent ?>% OFF applied to your order! (Valid until: <?= date('M d, Y', strtotime($active_discount['end_date'])) ?>)
        </div>
        <?php endif; ?>

        <section class="checkout">
            <div class="title">
                <img src="../assets/images/tlc-logo.png" class="logo2" onerror="this.src='../assets/images/default-cookie.png'">
                <h1>Checkout Summary</h1>
                <div class="row">
                    <form method="post">
                        <!-- Submit Button -->
                        <button type="submit" name="place_order" class="btn">place order</button>
                    </form>
                    <div class="summary">
                        <h3>my bag</h3>
                        <div class="box-container">
                            <?php
                            $grand_total = 0;
                            
                            // Check if this is a Buy Now order with quantity
                            if (isset($_GET['buy_now']) && isset($_GET['pid']) && isset($_GET['qty'])) {
                                $product_id = $_GET['pid'];
                                $qty = $_GET['qty'];
                                
                                $select_get = $conn->prepare("SELECT * FROM `products` WHERE id=?");
                                $select_get->execute([$product_id]);
                                while ($fetch_get = $select_get->fetch(PDO::FETCH_ASSOC)) {
                                    $original_price = $fetch_get['price'];
                                    $final_price = $original_price;
                                    
                                    if ($discount_percent > 0) {
                                        $final_price = $original_price - ($original_price * ($discount_percent / 100));
                                    }

                                    $sub_total = ($qty * $final_price) + 100;
                                    $grand_total += $sub_total;

                                    ?>
                                    <div class="flex">
                                        <img src="../admin/<?= $fetch_get['images']; ?>" class="img">
                                        <div>
                                            <h3 class="name"><?= $fetch_get['name']; ?></h3>
                                            <p class="price">
                                            <div class="info">
                                                <span style="color: #000;">Price: </span>
                                                <?php if ($discount_percent > 0): ?>
                                                    <span class="old-price">₱<?= number_format($original_price, 2); ?></span>
                                                    <span class="discount-label">(<?= $discount_percent ?>%): </span>
                                                    ₱<?= number_format($final_price, 2); ?>
                                                <?php else: ?>
                                                    ₱<?= number_format($original_price, 2); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="info">
                                                <span style="color: #000;">Quantity: </span><span><?= $qty ?></span>
                                            </div>
                                            <div class="info">
                                                <span style="color: #000;">Shipping Fee: </span><span>₱100.00</span>
                                            </div>
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            }
                            elseif (isset($_GET['get_id'])) {
                                $select_get = $conn->prepare("SELECT * FROM `products` WHERE id=?");
                                $select_get->execute([$_GET['get_id']]);
                                while ($fetch_get = $select_get->fetch(PDO::FETCH_ASSOC)) {
                                    $original_price = $fetch_get['price'];
                                    $final_price = $original_price;
                                    
                                    if ($discount_percent > 0) {
                                        $final_price = $original_price - ($original_price * ($discount_percent / 100));
                                    }

                                    $sub_total = $final_price + 100;
                                    $grand_total += $sub_total;

                                    ?>
                                    <div class="flex">
                                        <img src="../admin/<?= $fetch_get['images']; ?>" class="img">
                                        <div>
                                            <h3 class="name"><?= $fetch_get['name']; ?></h3>
                                            <p class="price">
                                            <div class="info">
                                                <span style="color: #000;">Price: </span>
                                                <?php if ($discount_percent > 0): ?>
                                                    <span class="old-price">₱<?= number_format($original_price, 2); ?></span>
                                                    <span class="discount-label">(<?= $discount_percent ?>%): </span>
                                                    ₱<?= number_format($final_price, 2); ?>
                                                <?php else: ?>
                                                    ₱<?= number_format($original_price, 2); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="info">
                                                <span style="color: #000;">Quantity: </span><span>1</span>
                                            </div>
                                            <div class="info">
                                                <span style="color: #000;">Shipping Fee: </span><span>₱100.00</span>
                                            </div>
                                            </p>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                $select_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id=?");
                                $select_cart->execute([$user_id]);
                                if ($select_cart->rowCount() > 0) {
                                    while ($fetch_cart = $select_cart->fetch(PDO::FETCH_ASSOC)) {
                                        $select_products = $conn->prepare("SELECT * FROM `products` WHERE id=?");
                                        $select_products->execute([$fetch_cart['product_id']]);
                                        $fetch_products = $select_products->fetch(PDO::FETCH_ASSOC);
                                        $original_price = $fetch_products['price'];
                                        $final_price = $original_price;
                                        
                                        if ($discount_percent > 0) {
                                            $final_price = $original_price - ($original_price * ($discount_percent / 100));
                                        }

                                        $sub_total = ($fetch_cart['qty'] * $final_price) + 100;
                                        $grand_total += $sub_total;
                                        ?>
                                        <div class="flex">
                                            <img src="../admin/<?= $fetch_products['images']; ?>" class="img">
                                            <div>
                                                <h3 class="name"><?= $fetch_products['name']; ?></h3>
                                                <p class="price">
                                                <div class="info">
                                                    <span style="color: #000;">Price: </span>
                                                    <?php if ($discount_percent > 0): ?>
                                                        <span class="old-price">₱<?= number_format($original_price, 2); ?></span>
                                                        <span class="discount-label">(<?= $discount_percent ?>%): </span>
                                                        ₱<?= number_format($final_price, 2); ?>
                                                    <?php else: ?>
                                                        ₱<?= number_format($original_price, 2); ?>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="info">
                                                    <span style="color: #000;">Quantity: </span><span><?= $fetch_cart['qty']; ?></span>
                                                </div>
                                                <div class="info">
                                                    <span style="color: #000;">Shipping Fee: </span><span>₱100.00</span>
                                                </div>
                                                </p>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo '<p class="empty">your cart is empty</p>';
                                }
                            }
                            ?>
                        </div>
                        <div class="grand-total"><span>total amount payable: </span>₱<?= number_format($grand_total, 2) ?></div>
                    </div>
                </div>
            </div>
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