<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Check if the user is an admin
    $check_admin = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $check_admin->execute([$user_id]);
    $fetch_admin = $check_admin->fetch(PDO::FETCH_ASSOC);

    if (!$fetch_admin || $fetch_admin['is_admin'] == 0) {
        $_SESSION['unauthorized'] = true;
        header("Location: ../pages/index.php");
        exit();

    }

} else {
    // Not logged in
    header("Location: login.php");
    exit();
}

if (!$user_id) {
    header("Location: login.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: login.php');
}

// Delete product
if (isset($_GET['delete_product'])) {
    $delete_id = $_GET['delete_product'];
    $conn->prepare("DELETE FROM products WHERE id = ?")->execute([$delete_id]);
    header("Location: view_products.php");
    exit();
}

// Fetch active global discount
$today = date('Y-m-d');
$get_discount = $conn->prepare("SELECT * FROM discounts WHERE start_date <= ? AND end_date >= ? LIMIT 1");
$get_discount->execute([$today, $today]);
$active_discount = $get_discount->fetch(PDO::FETCH_ASSOC);

?>
<?php $page = 'view_products'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Products</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Admin-specific cookie theme styles */
        .title2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        
        .title2 .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1rem 2rem;
            font-size: 1.4rem;
            border-radius: 30px;
            transition: all 0.3s ease;
        }
        
        .title2 .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }
        
        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.6rem;
        }
        
        .products .box {
            position: relative;
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.3s ease;
            border: 2px solid var(--cookie-tan);
            box-shadow: var(--shadow);
        }
        
        .products .box:hover {
            transform: translateY(-5px);
            border-color: var(--cookie-brown);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.2);
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
            margin: 1.5rem 1rem 0.5rem;
            padding: 0;
            text-align: center;
        }
        
        .products .box .price {
            font-size: 2rem;
            color: var(--cookie-brown);
            font-weight: bold;
            text-align: center;
            margin: 0.5rem 0;
            padding: 0;
        }
        
        .products .box .flex {
            padding: 1rem;
            background: #f9f9f9;
            margin: 1rem;
            border-radius: 10px;
            display: flex;
            justify-content: center;
        }
        
        .button-container {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 2;
        }
        
        .button-container .button {
            display: flex;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.8rem;
            border-radius: 40px;
            backdrop-filter: blur(5px);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: auto !important;
        }
        
        .button-container .button .btn {
            background-color: var(--cookie-brown) !important;
            color: #fff !important;
            padding: 0.8rem 1.5rem !important;
            font-size: 1.2rem !important;
            border-radius: 25px !important;
            margin: 0 !important;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
            transition: all 0.3s ease;
            position: relative !important;
            top: auto !important;
            left: auto !important;
        }
        
        .button-container .button .btn:hover {
            background-color: var(--cookie-chocolate) !important;
            transform: scale(1.05);
        }
        
        .button-container .button .btn.delete-btn:hover {
            background-color: #dc3545 !important;
        }
        
        .category-title {
            background: var(--cookie-brown);
            color: var(--cookie-cream);
            font-size: 2.2rem;
            padding: 1.5rem;
            margin: 2rem 0 1rem;
            border-radius: 50px;
            text-transform: uppercase;
            letter-spacing: 2px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }
        
        .category-title::before {
            content: '🍪';
            position: absolute;
            right: 30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.2;
        }
        
        .category-title::after {
            content: '🍪';
            position: absolute;
            left: 30px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.5rem;
            opacity: 0.2;
        }
        
        .category-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 2rem;
            background: var(--cookie-cream);
            border-radius: 20px;
            margin: 1rem 0 3rem;
        }
        
        .category-row p {
            grid-column: 1 / -1;
            text-align: center;
            font-size: 1.8rem;
            color: #666;
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            border: 2px dashed var(--cookie-tan);
        }
        
        .discount-label {
            background: #ff6b6b;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 1.2rem;
            margin-left: 0.5rem;
            display: inline-block;
        }
        
        .old-price {
            text-decoration: line-through;
            color: #999;
            font-size: 1.4rem;
            margin-right: 0.5rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="shop.php" class="btn"><i class="bx bx-plus-circle"></i> Add New Cookie</a>
            <span>/ Cookie Inventory</span>
        </div>
        
        <section class="products">
            <div class="box-container">
                <?php
                // Fetch all products without category filtering
                $select_products = $conn->prepare("SELECT * FROM `products` ORDER BY name ASC");
                $select_products->execute();
                $products = $select_products->fetchAll(PDO::FETCH_ASSOC);

                // If there are no products
                if (count($products) == 0) {
                    echo "<div class='category-row' style='display: block;'>";
                    echo "<p>No cookies yet. <br> <a href='shop.php' style='color: var(--cookie-brown); text-decoration: underline;'>Add your first cookie!</a> 🍪</p>";
                    echo "</div>";
                } else {
                    // Display all products in a single grid
                    echo "<div class='category-row'>"; // Using existing category-row class for styling
                    
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
                                <div style="position: absolute; top: 10px; left: 10px; background: #ff6b6b; color: white; padding: 0.3rem 1rem; border-radius: 20px; font-size: 1.2rem; z-index: 3;">-<?= $active_discount['discount_percentage'] ?>%</div>
                            <?php endif; ?>
                            
                            <img class="img" src="../admin/<?= $fetch_products['images']; ?>" alt="<?= $fetch_products['name']; ?> Cookie">
                            
                            <div class="button-container">
                                <div class="button">
                                    <a class="btn delete-btn" href="view_products.php?delete_product=<?= $fetch_products['id'] ?>"
                                        onclick="return confirm('Are you sure you want to delete this cookie?')">
                                        <i class="bx bx-trash"></i> Delete
                                    </a>
                                    <a href="edit_product.php?pid=<?= $fetch_products['id']; ?>" class="btn">
                                        <i class="bx bx-edit"></i> Edit
                                    </a>
                                    <a href="view_page.php?pid=<?= $fetch_products['id'] ?>" class="btn">
                                        <i class="bx bx-show"></i> View
                                    </a>
                                </div>
                            </div>
                            
                            <h3 class="name"><?= $fetch_products['name']; ?></h3>
                            
                            <input type="hidden" name="product_id" value="<?= $fetch_products['id']; ?>">
                            
                            <div class="flex">
                                <p class="price">
                                    <?php if ($active_discount): ?>
                                        <span class="old-price">₱<?= number_format($price, 2); ?></span>
                                        <strong>₱<?= number_format($discounted_price, 2); ?></strong>
                                        <span class="discount-label">-<?= $active_discount['discount_percentage'] ?>%</span>
                                    <?php else: ?>
                                        ₱<?= number_format($price, 2); ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </form>
                    <?php
                    }
                    echo "</div>"; // Close category-row
                }
                ?>
            </div>
        </section>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>