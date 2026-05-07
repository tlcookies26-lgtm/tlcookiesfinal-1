<?php
include '../includes/connection.php';
session_start();

$user_id = $_SESSION['user_id'] ?? '';

if (!$user_id) {
    header("Location: login.php");
    exit();
}

$today = date('Y-m-d');

// Fetch active global discount
$get_discount = $conn->prepare("SELECT * FROM discounts WHERE start_date <= ? AND end_date >= ? LIMIT 1");
$get_discount->execute([$today, $today]);
$active_discount = $get_discount->fetch(PDO::FETCH_ASSOC);

$discount_percent = $active_discount['discount_percentage'] ?? 0;

if (isset($_POST['logout'])) {
    // Destroy the session
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect the user to the login page (or any other page after logout)
    header('Location: index.php');
    exit; // Make sure to call exit after header redirection
}

// Fetch cart items ordered by time added (newest first)
$fetch_cart = $conn->prepare("SELECT 
    cart.id AS cart_id, 
    cart.price, 
    cart.qty, 
    cart.created_at AS cart_added_time, 
    products.id AS product_id, 
    products.name, 
    products.images 
FROM cart 
JOIN products ON cart.product_id = products.id 
WHERE cart.user_id = ? 
ORDER BY cart.created_at DESC");

$fetch_cart->execute([$user_id]);

// Calculate total amount
$total_amount = 0;
$cart_items = $fetch_cart->fetchAll(PDO::FETCH_ASSOC);
foreach ($cart_items as $cart_item) {
    $original_price = $cart_item['price'];
    $final_price = $original_price;
    
    if ($discount_percent > 0) {
        $final_price = $original_price - ($original_price * ($discount_percent / 100));
    }
    
    $subtotal = ($final_price * $cart_item['qty']) + 100; // Include shipping fee
    $total_amount += $subtotal;
}
?>
<?php $page = 'products'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Your Cart</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        .cart-time {
            font-size: 1.2rem;
            color: #888;
            margin: 0.5rem 0;
        }
        .remove-btn {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 2rem;
            color: #dc3545;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }
        .remove-btn:hover {
            color: #c82333;
            transform: scale(1.1);
        }
        .cart-details {
            padding: 1rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Your Cookie Jar</h1>
        </div>

        <div class="title2">
            <a href="index.php">Home</a><span>/ Your Cart</span>
        </div>

        <?php if ($discount_percent > 0): ?>
        <div style="background: linear-gradient(135deg, var(--cookie-brown), var(--cookie-chocolate)); color: #fff; text-align: center; padding: 1rem; border-radius: 50px; margin: 1rem auto; max-width: 600px; font-size: 1.6rem;">
            🎉 <?= $discount_percent ?>% OFF on all cookies! (Valid until: <?= date('M d, Y', strtotime($active_discount['end_date'])) ?>)
        </div>
        <?php endif; ?>

        <section class="products">
            <div class='category-title'>Your Cookie Collection</div>
            <div class="box-container">
                <div class="row">
                    <?php
                    if (!empty($cart_items)) {
                        foreach ($cart_items as $cart_item) {
                            $original_price = $cart_item['price'];
                            $final_price = $original_price;

                            if ($discount_percent > 0) {
                                $final_price = $original_price - ($original_price * ($discount_percent / 100));
                            }

                            $subtotal = ($final_price * $cart_item['qty']) + 100;
                            ?>
                            <form action="../includes/update_cart.php" method="post" class="box" id="delItemForm">
                                <img class="img" src="../admin/<?php echo $cart_item['images']; ?>" alt="<?php echo $cart_item['name']; ?>">

                                <input type="hidden" name="cart_id" value="<?php echo $cart_item['cart_id']; ?>">

                                <div class="button-container">
                                    <div class="button">
                                        <a href="view_page.php?pid=<?= $cart_item['product_id']; ?>" class="bx bxs-show"></a>
                                        <input type="hidden" name="remove_from_cart" value="1">
                                        <button type="submit" name="remove_from_cart" class="remove-btn">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="cart-details">
                                    <h3 class="name"><?php echo $cart_item['name']; ?></h3>
                                    <p class="price">
                                        Price:
                                        <?php if ($discount_percent > 0): ?>
                                            <span class="old-price">₱<?php echo number_format($original_price, 2); ?></span>
                                            <span class="discount-label">-<?= $discount_percent ?>%</span>
                                            ₱<?php echo number_format($final_price, 2); ?>
                                        <?php else: ?>
                                            ₱<?php echo number_format($original_price, 2); ?>
                                        <?php endif; ?>
                                    </p>
                                    <p class="cart-time">Added on:
                                        <?php echo date("F j, Y, g:i a", strtotime($cart_item['cart_added_time'])); ?>
                                    </p>
                                    <div class="flex" style="display: block;">
                                        <label>Quantity: </label>
                                        <input type="number" name="qty" required min="1"
                                            value="<?php echo $cart_item['qty']; ?>" max="99" class="qty">
                                        <div class="button" style="display: none;">
                                            <button type="submit" name="update_qty" class="fa-edit">
                                                <i>update</i>
                                            </button>
                                        </div>
                                    </div>
                                    <span style="color: #000;">Shipping Fee: </span><span class="price">₱100.00</span>
                                    <p class="sub-total">Subtotal:
                                        <strong>₱<?php echo number_format($subtotal, 2); ?></strong>
                                    </p>
                                </div>
                            </form>
                            <?php
                        }
                    } else {
                        echo "<p style='text-align: center; padding: 3rem; font-size: 1.8rem; color: #666; grid-column: 1/-1;'>Your cookie jar is empty. <a href='products.php' style='color: var(--cookie-brown);'>Start shopping!</a> 🍪</p>";
                    }
                    ?>
                </div>
            </div>

            <?php if ($total_amount > 0) { ?>
                <div class="cart-total">
                    <p>Total Amount Payable: <span>₱<?php echo number_format($total_amount, 2); ?></span></p>
                    <form id="emptyCartForm" action="../includes/update_cart.php" method="post">
                        <input type="hidden" name="empty_cart" value="1">
                        <button type="submit" name="empty_cart" class="btn">Empty Jar</button>
                        <a href="checkout.php" class="btn">Proceed to Checkout</a>
                    </form>
                </div>
            <?php } ?>

        </section>

        <?php include '../includes/footer.php'; ?>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <script>
        // Empty cart confirmation
        document.querySelector('#emptyCartForm button[name="empty_cart"]')?.addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default form submission

            Swal.fire({
                title: "Empty Your Cookie Jar?",
                text: "This will remove all cookies from your cart.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonColor: "#8B4513",
                cancelButtonColor: "#3E2723",
                confirmButtonText: "Yes, empty it",
                cancelButtonText: "No, keep them"
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log("Submitting form...");
                    document.getElementById("emptyCartForm").submit();
                }
            });
        });

        // Remove item confirmation
        document.querySelectorAll('form button[name="remove_from_cart"]').forEach((button) => {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const form = button.closest('form');

                Swal.fire({
                    title: "Remove from Cart?",
                    text: "This cookie will be removed from your jar.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonColor: "#8B4513",
                    cancelButtonColor: "#3E2723",
                    confirmButtonText: "Yes, remove it",
                    cancelButtonText: "No, keep it"
                }).then((result) => {
                    if (result.isConfirmed) {
                        console.log("Submitting form...");
                        form.submit();
                    }
                });
            });
        });

        // Quantity update button visibility
        document.querySelectorAll('.qty').forEach((qtyInput) => {
            const originalQty = qtyInput.value;
            const buttonContainer = qtyInput.closest('.flex').querySelector('.button');

            // Ensure the button container is hidden initially
            if (buttonContainer) {
                buttonContainer.style.display = 'none';
            }

            qtyInput.addEventListener('input', () => {
                if (buttonContainer) {
                    if (qtyInput.value !== originalQty) {
                        buttonContainer.style.display = 'inline-block';
                    } else {
                        buttonContainer.style.display = 'none';
                    }
                }
            });
        });
    </script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>