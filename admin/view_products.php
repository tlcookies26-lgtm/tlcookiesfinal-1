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
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
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

                            <?php
                            $stock = $fetch_products['stock'];
                            if ($stock == 0) {
                                $badgeStyle = 'background:#f8d7da;color:#721c24;';
                                $badgeText  = '❌ Out of Stock';
                            } elseif ($stock <= 10) {
                                $badgeStyle = 'background:#fff3cd;color:#856404;';
                                $badgeText  = '⚠️ Low: ' . $stock . ' left';
                            } else {
                                $badgeStyle = 'background:#d4edda;color:#155724;';
                                $badgeText  = '✅ ' . $stock . ' in stock';
                            }
                            ?>
                            <div style="position: absolute; top: 10px; right: 10px; <?= $badgeStyle ?> padding: 0.3rem 0.9rem; border-radius: 20px; font-size: 1.1rem; z-index: 3; font-weight: bold;">
                                <?= $badgeText ?>
                            </div>
                            
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