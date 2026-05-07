<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

if (isset($_POST['logout'])) {
    // Destroy the session
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect the user to the login page (or any other page after logout)
    header('Location: index.php');
    exit; // Make sure to call exit after header redirection
}

// Ensure user_id is set
$user_id = $_SESSION['user_id'] ?? '';
$success_msg = $warning_msg = '';

// Fetch active discounts (global discounts, not category-specific)
$today = date('Y-m-d');
$get_discounts = $conn->prepare("SELECT * FROM `discounts` WHERE start_date <= ? AND end_date >= ?");
$get_discounts->execute([$today, $today]);
$active_discount = $get_discounts->fetch(PDO::FETCH_ASSOC);

if (isset($_POST['add_to_cart'])) {
    if (empty($user_id)) {
        $warning_msg = 'Please log in to add items to your cart';
    } else {
        $id = unique_id();
        $product_id = $_POST['product_id'];
        $qty = $_POST['qty'];
        $qty = filter_var($qty, FILTER_SANITIZE_STRING);

        // Check if product exists
        $check_product = $conn->prepare("SELECT * FROM `products` WHERE id = ? LIMIT 1");
        $check_product->execute([$product_id]);

        if ($check_product->rowCount() == 0) {
            $warning_msg = 'Product not found';
        } else {
            // Check if the product is already in the cart
            $varify_cart = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ? AND product_id = ?");
            $varify_cart->execute([$user_id, $product_id]);

            // Limit cart items
            $max_cart_items = $conn->prepare("SELECT COUNT(*) FROM `cart` WHERE user_id = ?");
            $max_cart_items->execute([$user_id]);
            $cart_count = $max_cart_items->fetchColumn();

            if ($varify_cart->rowCount() > 0) {
                $warning_msg = 'Product already exists in your cart';
            } elseif ($cart_count >= 20) {
                $warning_msg = 'Your cookie jar is full (max 20 items)';
            } else {
                // Get product price
                $select_price = $conn->prepare("SELECT price FROM `products` WHERE id = ?");
                $select_price->execute([$product_id]);
                $fetch_price = $select_price->fetch(PDO::FETCH_ASSOC);

                // Insert into cart
                $insert_cart = $conn->prepare("INSERT INTO `cart`(id, user_id, product_id, price, qty) VALUES(?,?,?,?,?)");
                $insert_cart->execute([$id, $user_id, $product_id, $fetch_price['price'], $qty]);
                $success_msg = 'Cookie added to your jar successfully! 🍪';
            }
        }
    }

    // Redirect to prevent form resubmission
    header("Location: products.php?success_msg=$success_msg&warning_msg=$warning_msg");
    exit();
}

// Handle Buy Now from modal - direct to checkout with quantity
if (isset($_POST['buy_now_modal'])) {
    if (empty($user_id)) {
        $warning_msg = 'Please log in to continue';
        header("Location: login.php?redirect=products.php");
        exit();
    } else {
        $product_id = $_POST['product_id'];
        $qty = $_POST['modal_qty'];
        
        // Check if product exists
        $check_product = $conn->prepare("SELECT * FROM `products` WHERE id = ?");
        $check_product->execute([$product_id]);
        
        if ($check_product->rowCount() > 0) {
            // Store in session for checkout
            $_SESSION['buy_now'] = [
                'product_id' => $product_id,
                'qty' => $qty
            ];
            
            // Redirect to checkout with product ID and quantity
            header("Location: checkout.php?buy_now=1&pid=$product_id&qty=$qty");
            exit();
        }
    }
}
?>
<?php $page = 'products'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Our Cookies</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Additional cookie-specific styles for products page */
        .banner {
            background-image: url('../images/cookie-banner-products.jpg');
        }
        
        .banner h1 {
            color: var(--cookie-cream);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 2.5rem;
            padding: 2rem;
        }
        
        .products .box {
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid var(--cookie-tan);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .products .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.2);
            border-color: var(--cookie-brown);
        }
        
        .products .box .img {
            height: 250px;
            width: 100%;
            object-fit: cover;
            border-bottom: 3px solid var(--cookie-tan);
        }
        
        .products .box .name {
            font-size: 1.8rem;
            color: var(--cookie-chocolate);
            margin: 1rem 1rem 0.5rem;
            padding: 0;
        }
        
        .products .box .price {
            font-size: 2rem;
            color: var(--cookie-brown);
            font-weight: bold;
            margin: 0 1rem;
        }
        
        .products .box .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            width: 90%;
            margin: 1rem auto;
            display: block;
            text-align: center;
            padding: 1rem;
            border: none;
            cursor: pointer;
            font-size: 1.6rem;
            transition: all 0.3s ease;
        }
        
        .products .box .btn:hover {
            background-color: var(--cookie-chocolate);
        }
        
        .products .box .buy-now-btn {
            background-color: var(--cookie-chocolate);
            margin-top: 0.5rem;
        }
        
        .products .box .buy-now-btn:hover {
            background-color: var(--cookie-brown);
        }
        
        .discount-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #ff6b6b;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 30px;
            font-size: 1.4rem;
            font-weight: bold;
            z-index: 3;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 1.4rem;
            margin-right: 0.5rem;
        }
        
        .products .box .flex {
            padding: 1rem;
            background: #f9f9f9;
            margin: 1rem;
            border-radius: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .products .box .flex label {
            color: var(--cookie-chocolate);
            font-weight: 600;
            font-size: 1.4rem;
        }
        
        .products .box .qty {
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            padding: 0.5rem;
            width: 70px;
            text-align: center;
            font-size: 1.4rem;
        }
        
        .products .box .qty:focus {
            border-color: var(--cookie-brown);
            outline: none;
        }
        
        .button-container {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        .button {
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.9);
            padding: 0.5rem;
            border-radius: 30px;
            backdrop-filter: blur(5px);
        }
        
        .button button,
        .button a {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--cookie-brown);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .button button:hover,
        .button a:hover {
            background: var(--cookie-chocolate);
            transform: scale(1.1);
        }
        
        .section-header {
            text-align: center;
            margin: 3rem 0 2rem;
            padding: 0 1rem;
        }
        
        .section-header h2 {
            font-size: 3rem;
            color: var(--cookie-chocolate);
            position: relative;
            display: inline-block;
        }
        
        .section-header h2::after {
            content: '🍪';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 2rem;
        }
        
        .no-products {
            text-align: center;
            padding: 5rem;
            background: var(--cookie-cream);
            border-radius: 20px;
            border: 2px dashed var(--cookie-tan);
            grid-column: 1 / -1;
        }
        
        .no-products i {
            font-size: 5rem;
            color: var(--cookie-tan);
            margin-bottom: 1rem;
        }
        
        .no-products p {
            font-size: 1.8rem;
            color: #666;
        }
        
        /* Global discount banner */
        .global-discount-banner {
            background: linear-gradient(135deg, var(--cookie-brown), var(--cookie-chocolate));
            color: #fff;
            text-align: center;
            padding: 1rem;
            border-radius: 50px;
            margin: 2rem auto;
            max-width: 600px;
            font-size: 1.6rem;
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }
        
        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 2rem;
            border-radius: 20px;
            width: 90%;
            max-width: 500px;
            position: relative;
            animation: modalSlideIn 0.3s ease;
            border: 3px solid var(--cookie-tan);
        }
        
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .close-modal {
            position: absolute;
            right: 1.5rem;
            top: 1rem;
            font-size: 3rem;
            cursor: pointer;
            color: var(--cookie-brown);
            transition: all 0.3s ease;
        }
        
        .close-modal:hover {
            color: var(--cookie-chocolate);
            transform: scale(1.1);
        }
        
        .modal-product-preview {
            display: flex;
            gap: 1.5rem;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px dashed var(--cookie-tan);
        }
        
        .modal-product-preview img {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--cookie-tan);
        }
        
        .modal-product-info h3 {
            color: var(--cookie-chocolate);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .modal-price {
            font-size: 2rem;
            color: var(--cookie-brown);
            font-weight: bold;
        }
        
        .modal-quantity {
            margin: 2rem 0;
            text-align: center;
        }
        
        .modal-quantity label {
            display: block;
            color: var(--cookie-chocolate);
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }
        
        .modal-quantity input {
            width: 120px;
            padding: 1rem;
            text-align: center;
            font-size: 1.8rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            margin: 0 auto;
        }
        
        .modal-quantity input:focus {
            border-color: var(--cookie-brown);
            outline: none;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }
        
        .modal-actions .btn {
            padding: 1rem 2rem;
            font-size: 1.6rem;
            margin: 0;
            width: auto;
        }
        
        .btn-cancel {
            background-color: var(--cookie-tan);
            color: var(--cookie-chocolate);
        }
        
        .btn-cancel:hover {
            background-color: #e5d5b5;
        }
        
        @media (max-width: 768px) {
            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
                gap: 1.5rem;
                padding: 1rem;
            }
            
            .products .box .name {
                font-size: 1.5rem;
            }
            
            .section-header h2 {
                font-size: 2.5rem;
            }
            
            .modal-content {
                margin: 20% auto;
                width: 95%;
                padding: 1.5rem;
            }
            
            .modal-product-preview {
                flex-direction: column;
                text-align: center;
            }
            
            .modal-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Our Fresh Cookies</h1>
        </div>

        <div class="title2">
            <a href="index.php">home </a><span>/ our cookies</span>
        </div>

        <!-- Global Discount Banner -->
        <?php if ($active_discount): ?>
        <div class="global-discount-banner">
            <strong>🎉 <?= htmlspecialchars($active_discount['title']) ?>:</strong> 
            <?= htmlspecialchars($active_discount['description']) ?> - 
            <strong><?= $active_discount['discount_percentage'] ?>% OFF</strong> on all cookies!
            <p style="font-size: 1.2rem; margin-top: 0.5rem;">Valid until: <?= date('M d, Y', strtotime($active_discount['end_date'])) ?></p>
        </div>
        <?php endif; ?>

        <section class="products">
            <div class="section-header">
                <h2>All Our Delicious Cookies</h2>
            </div>
            
            <div class="products-grid">
                <?php
                // Fetch all products without category filtering
                $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY name ASC");
                $select_products->execute();
                $products = $select_products->fetchAll(PDO::FETCH_ASSOC);

                // If there are no products
                if (count($products) == 0) {
                    echo '<div class="no-products">';
                    echo '<i class="bx bx-cookie"></i>';
                    echo '<p>Fresh cookies coming soon! Check back later.</p>';
                    echo '</div>';
                } else {
                    // Loop through and display each product
                    foreach ($products as $fetch_products) {
                        $price = $fetch_products['price'];
                        $discounted_price = $price;
                        
                        // Apply global discount if active
                        if ($active_discount) {
                            $discounted_price = $price - ($price * ($active_discount['discount_percentage'] / 100));
                        }
                ?>
                        <form action="" method="post" class="box">
                            <?php if ($active_discount): ?>
                                <div class="discount-badge">-<?= $active_discount['discount_percentage'] ?>%</div>
                            <?php endif; ?>
                            
                            <img class="img" src="../admin/<?= $fetch_products['images']; ?>" alt="<?= htmlspecialchars($fetch_products['name']); ?> Cookie">
                            
                            <div class="button-container">
                                <div class="button">
                                    <button type="submit" name="add_to_cart"><i class="bx bx-cart"></i></button>
                                    <a href="view_page.php?pid=<?= $fetch_products['id']; ?>" class="bx bxs-show"></a>
                                </div>
                            </div>
                            
                            <h3 class="name"><?= htmlspecialchars($fetch_products['name']); ?></h3>
                            
                            <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                            
                            <div class="flex">
                                <p class="price">
                                    <?php if ($active_discount): ?>
                                        <span class="old-price">₱<?= number_format($price, 2); ?></span>
                                        <strong>₱<?= number_format($discounted_price, 2); ?></strong>
                                    <?php else: ?>
                                        ₱<?= number_format($price, 2); ?>
                                    <?php endif; ?>
                                </p>
                                <div>
                                    <label>Qty:</label>
                                    <input type="number" name="qty" required min="1" value="1" max="99" class="qty">
                                </div>
                            </div>
                            
                            <button type="button" class="btn buy-now-btn" onclick="openBuyNowModal(<?= $fetch_products['id']; ?>, '<?= htmlspecialchars(addslashes($fetch_products['name'])); ?>', '<?= $fetch_products['images']; ?>', <?= $price; ?>, <?= $discounted_price; ?>)">Buy Now</button>
                        </form>
                <?php
                    }
                }
                ?>
            </div>
        </section>
        
        <!-- Buy Now Modal -->
        <div id="buyNowModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2 style="color: var(--cookie-chocolate); text-align: center; margin-bottom: 2rem;">Quick Buy</h2>
                
                <div id="modalProductPreview" class="modal-product-preview">
                    <img id="modalProductImage" src="" alt="Product">
                    <div class="modal-product-info">
                        <h3 id="modalProductName"></h3>
                        <p id="modalProductPrice" class="modal-price"></p>
                    </div>
                </div>
                
                <form method="POST" action="products.php" id="buyNowForm">
                    <input type="hidden" name="product_id" id="modalProductId" value="">
                    
                    <div class="modal-quantity">
                        <label for="modalQty">How many cookies would you like?</label>
                        <input type="number" name="modal_qty" id="modalQty" min="1" max="99" value="1" required>
                    </div>
                    
                    <div class="modal-actions">
                        <button type="submit" name="buy_now_modal" class="btn btn-primary">Buy Now</button>
                        <button type="button" class="btn btn-cancel" onclick="closeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
        
        <?php include '../includes/footer.php'; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
    <script>
        // Modal functions
        const modal = document.getElementById('buyNowModal');
        const closeBtn = document.querySelector('.close-modal');
        
        function openBuyNowModal(id, name, image, originalPrice, discountedPrice) {
            document.getElementById('modalProductId').value = id;
            document.getElementById('modalProductName').textContent = name;
            document.getElementById('modalProductImage').src = '../admin/' + image;
            
            <?php if ($active_discount): ?>
                const priceHtml = '<span class="old-price" style="font-size: 1.4rem;">₱' + originalPrice.toFixed(2) + '</span> ₱' + discountedPrice.toFixed(2);
                document.getElementById('modalProductPrice').innerHTML = priceHtml;
            <?php else: ?>
                document.getElementById('modalProductPrice').textContent = '₱' + originalPrice.toFixed(2);
            <?php endif; ?>
            
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden'; // Prevent scrolling
        }
        
        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
            document.getElementById('modalQty').value = 1; // Reset quantity
        }
        
        // Close modal when clicking the close button
        closeBtn.onclick = closeModal;
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        
        // Show success/warning messages if they exist in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');
        const warningMsg = urlParams.get('warning_msg');
        
        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: 'Added to Jar!',
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
        
        // Add cookie crumbs animation to prices
        document.querySelectorAll('.price').forEach(price => {
            price.addEventListener('mouseover', function() {
                this.style.animation = 'crumb 0.5s ease';
            });
            price.addEventListener('mouseout', function() {
                this.style.animation = '';
            });
        });
        
        // Quantity input validation
        document.querySelectorAll('.qty').forEach(input => {
            input.addEventListener('change', function() {
                if (this.value < 1) this.value = 1;
                if (this.value > 99) this.value = 99;
            });
        });
        
        // Modal quantity validation
        const modalQty = document.getElementById('modalQty');
        if (modalQty) {
            modalQty.addEventListener('change', function() {
                if (this.value < 1) this.value = 1;
                if (this.value > 99) this.value = 99;
            });
        }
    </script>
    
    <?php include '../includes/alert.php'; ?>
</body>

</html>