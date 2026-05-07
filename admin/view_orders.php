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

// Fetch all orders, items, and products
$orders_query = $conn->query("SELECT * FROM `orders`");
$orders = $orders_query->fetchAll(PDO::FETCH_ASSOC);
$items_query = $conn->query("SELECT * FROM `order_items`");
$items = $items_query->fetchAll(PDO::FETCH_ASSOC);
$products_query = $conn->query("SELECT * FROM `products`");
$products = $products_query->fetchAll(PDO::FETCH_ASSOC);

// Re-index products by ID for easy lookup
$products_map = [];
foreach ($products as $product) {
    $products_map[$product['id']] = $product;
}

// Delete order
if (isset($_GET['delete_order'])) {
    $delete_id = $_GET['delete_order'];
    
    $check_order = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $check_order->execute([$delete_id]);
    
    if ($check_order->rowCount() > 0) {
        $conn->prepare("DELETE FROM orders WHERE id = ?")->execute([$delete_id]);
        header("Location: view_orders.php?success_msg=" . urlencode("Order #$delete_id has been deleted successfully."));
    } else {
        header("Location: view_orders.php?warning_msg=" . urlencode("Order not found."));
    }
    exit();
}

?>
<?php $page = 'view_orders'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Order Manager</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Order management specific styles */
        .title2 {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.5rem 2rem;
            background: var(--cookie-cream);
            border-radius: 50px;
            margin-bottom: 3rem;
            border: 2px solid var(--cookie-tan);
        }
        
        .title2 a {
            color: var(--cookie-brown);
            font-size: 1.6rem;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        
        .title2 a:hover {
            color: var(--cookie-chocolate);
        }
        
        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .title2 span::before {
            content: '📦';
            margin-right: 0.5rem;
        }
        
        .title {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .title h1 {
            color: var(--cookie-chocolate);
            font-size: 3rem;
            position: relative;
            display: inline-block;
        }
        
        .title h1::after {
            content: '🍪';
            margin-left: 1rem;
            font-size: 2.5rem;
        }
        
        .logo2 {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid var(--cookie-tan);
            margin-bottom: 1rem;
        }
        
        .products .box-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }
        
        .products .box {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .products .box:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.15);
        }
        
        /* Status-based border colors */
        .products .box[data-status="cancelled"] {
            border-left: 5px solid #dc3545;
        }
        
        .products .box[data-status="pending"] {
            border-left: 5px solid #ffc107;
        }
        
        .products .box[data-status="processing"] {
            border-left: 5px solid #17a2b8;
        }
        
        .products .box[data-status="delivered"] {
            border-left: 5px solid #28a745;
        }
        
        .product-billing {
            display: block;
            border-bottom: 2px dashed var(--cookie-tan);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .date {
            background: var(--cookie-cream);
            padding: 0.8rem 1.5rem;
            border-radius: 30px;
            display: inline-block;
            margin-bottom: 1.5rem;
            font-size: 1.4rem;
            color: var(--cookie-chocolate);
            font-weight: bold;
        }
        
        .date i {
            margin-right: 0.5rem;
        }
        
        .product-billing .img {
            width: 80px !important;
            height: 80px !important;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid var(--cookie-tan);
            float: left;
            margin-right: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .product-billing .img:hover {
            transform: scale(1.1);
        }
        
        .product-billing .row {
            margin-left: 100px;
        }
        
        .product-billing .name {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .product-billing .price {
            color: var(--cookie-brown);
            font-size: 1.4rem;
            margin: 0.3rem 0;
        }
        
        .billing-address {
            padding: 1rem 0;
        }
        
        .billing-address > a {
            display: inline-block;
            background: var(--cookie-brown);
            color: #fff;
            padding: 0.5rem 1.5rem;
            border-radius: 20px;
            font-size: 1.2rem;
            text-transform: uppercase;
            margin-bottom: 1rem;
        }
        
        .billing-address p {
            margin: 0.8rem 0;
            font-size: 1.3rem;
            color: #555;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .billing-address p i {
            color: var(--cookie-brown);
            font-size: 1.6rem;
            width: 20px;
        }
        
        .row {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
            flex-wrap: wrap;
        }
        
        .status-select {
            padding: 0.8rem 1.5rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 30px;
            font-size: 1.4rem;
            color: var(--cookie-chocolate);
            background: #fff;
            cursor: pointer;
            flex: 1;
            min-width: 150px;
        }
        
        .status-select:focus {
            outline: none;
            border-color: var(--cookie-brown);
        }
        
        .update-btn {
            background-color: var(--cookie-brown) !important;
            color: #fff !important;
            padding: 0.8rem 1.5rem !important;
            font-size: 1.3rem !important;
            border-radius: 30px !important;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            line-height: normal !important;
        }
        
        .update-btn:hover {
            background-color: var(--cookie-chocolate) !important;
            transform: translateY(-2px);
        }
        
        .bx-trash {
            font-size: 2rem;
            color: #dc3545 !important;
            transition: all 0.3s ease;
            padding: 0.8rem;
            border-radius: 50%;
            background: #fff;
            border: 2px solid #dc3545;
        }
        
        .bx-trash:hover {
            background: #dc3545;
            color: #fff !important;
            transform: scale(1.1);
        }
        
        .empty {
            text-align: center;
            padding: 5rem;
            font-size: 1.8rem;
            color: #666;
            background: #fff;
            border-radius: 20px;
            border: 2px dashed var(--cookie-tan);
            grid-column: 1 / -1;
        }
        
        .empty::before {
            content: '📦';
            display: block;
            font-size: 4rem;
            margin-bottom: 1rem;
        }
        
        /* Status badges for quick reference */
        .status-badge {
            display: inline-block;
            padding: 0.3rem 1rem;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
            margin-left: 1rem;
        }
        
        .status-badge.cancelled {
            background: #dc3545;
            color: #fff;
        }
        
        .status-badge.pending {
            background: #ffc107;
            color: #856404;
        }
        
        .status-badge.processing {
            background: #17a2b8;
            color: #fff;
        }
        
        .status-badge.delivered {
            background: #28a745;
            color: #fff;
        }

        @media (max-width: 768px) {
            .products .box-container {
                grid-template-columns: 1fr;
            }
            
            .title2 {
                flex-direction: column;
                text-align: center;
            }
            
            .product-billing .row {
                margin-left: 0;
                clear: both;
            }
            
            .product-billing .img {
                float: none;
                display: block;
                margin: 0 auto 1rem;
            }
            
            .row {
                flex-direction: column;
            }
            
            .status-select {
                width: 100%;
            }
            
            .bx-trash {
                align-self: flex-end;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="admin.php"><i class='bx bx-home'></i> Dashboard</a>
            <span> Order Management</span>
        </div>
        
        <section class="products">
            <div class="box-container">
                <div class="title">
                    <img src="../assets/images/cookie-logo.png" class="logo2" alt="Cookie Jar Logo">
                    <h1>Cookie Orders</h1>
                </div>
            </div>
            
            <div class="box-container">
                <?php
                if (isset($_POST['update_order_status'])) {
                    $order_id = $_POST['cancel_order_id'] ?: $_POST['processing_order_id'] ?: $_POST['delivered_order_id'] ?: $_POST['pending_order_id'];
                    $new_status = $_POST['order_status'];

                    if ($new_status) {
                        // Update order status in `order_items` table
                        $update_status_stmt = $conn->prepare("UPDATE order_items SET status = ? WHERE order_id = ?");
                        $update_status_stmt->execute([$new_status, $order_id]);

                        echo "<script>
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Order Status Updated',
                                    text: 'The order status has been successfully updated.',
                                    confirmButtonColor: '#3085d6',
                                    timer: 3000
                                }).then(() => {
                                    window.location.href = 'view_orders.php';
                                });
                            </script>";
                    }
                }
                
                $select_orders = $conn->prepare("
                    SELECT o.id AS order_id, o.order_date, oi.status, 
                    u.first_name, u.middle_name, u.surname, u.phone, u.email, u.address, u.barangay,
                    SUM(oi.sub_total) AS total_amount
                    FROM orders o
                    JOIN order_items oi ON o.id = oi.order_id
                    JOIN users u ON o.user_id = u.id
                    GROUP BY o.id, o.order_date, oi.status, u.first_name, u.middle_name, u.surname, u.phone, u.email, u.address, u.barangay
                    ORDER BY o.order_date DESC
                ");
                $select_orders->execute();

                if ($select_orders->rowCount() > 0) {
                    while ($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                        $status_class = '';
                        $status_text = '';
                        
                        switch($fetch_order['status']) {
                            case 'cancelled':
                                $status_class = 'cancelled';
                                $status_text = '❌ Cancelled';
                                break;
                            case 'pending':
                                $status_class = 'pending';
                                $status_text = '⏳ Pending';
                                break;
                            case 'processing':
                                $status_class = 'processing';
                                $status_text = '⚙️ Processing';
                                break;
                            case 'delivered':
                                $status_class = 'delivered';
                                $status_text = '✅ Delivered';
                                break;
                        }
                        ?>
                        <div class="box" data-status="<?= $fetch_order['status'] ?>">
                            <div style="position: absolute; top: 1rem; right: 1rem;">
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </div>
                            
                            <a class="product-billing">
                                <p class="date">
                                    <i class='bx bx-calendar'></i>
                                    <span><?= date('M d, Y h:i A', strtotime($fetch_order['order_date'])); ?></span>
                                </p>

                                <?php
                                // Fetch the products for this order
                                $select_products = $conn->prepare("SELECT p.name, p.images, oi.qty, oi.sub_total, p.price 
                                                FROM products p
                                                JOIN order_items oi ON p.id = oi.product_id
                                                WHERE oi.order_id = ?");
                                $select_products->execute([$fetch_order['order_id']]);

                                while ($fetch_product = $select_products->fetch(PDO::FETCH_ASSOC)) {
                                    ?>
                                    <img src="../admin/<?= $fetch_product['images']; ?>" class="img" alt="<?= $fetch_product['name']; ?>">
                                    <div class="row">
                                        <h3 class="name"><?= htmlspecialchars($fetch_product['name']); ?></h3>
                                        <p class="price">Price: ₱<?= number_format($fetch_product['price'], 2); ?> × <?= $fetch_product['qty']; ?></p>
                                        <p class="price" style="color: var(--cookie-chocolate);">Subtotal: ₱<?= number_format($fetch_product['sub_total'], 2); ?></p>
                                    </div>
                                    <?php
                                }
                                ?>
                                
                                <div style="clear: both; margin-top: 1rem; text-align: right;">
                                    <strong style="font-size: 1.8rem; color: var(--cookie-chocolate);">
                                        Total: ₱<?= number_format($fetch_order['total_amount'], 2); ?>
                                    </strong>
                                </div>
                            </a>
                            
                            <div class="billing-address">
                                <a><i class='bx bx-user'></i> Customer Details</a>
                                <p><i class='bx bxs-user'></i> <?= $fetch_order['first_name'] . ' ' . $fetch_order['middle_name'] . ' ' . $fetch_order['surname']; ?></p>
                                <p><i class='bx bxs-phone'></i> <?= $fetch_order['phone']; ?></p>
                                <p><i class='bx bxs-envelope'></i> <?= $fetch_order['email']; ?></p>
                                <p><i class='bx bxs-map-pin'></i> <?= $fetch_order['address']; ?>, Brgy. <?= $fetch_order['barangay']; ?>, Zamboanga City</p>
                                
                                <form method="POST">
                                    <div class="row">
                                        <input type="hidden" name="cancel_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="processing_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="delivered_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="pending_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        
                                        <select name="order_status" class="status-select" data-current="<?= $fetch_order['status']; ?>" required>
                                            <option value="pending" <?php if ($fetch_order['status'] == 'pending') echo 'selected'; ?>>⏳ Pending</option>
                                            <option value="processing" <?php if ($fetch_order['status'] == 'processing') echo 'selected'; ?>>⚙️ Processing</option>
                                            <option value="delivered" <?php if ($fetch_order['status'] == 'delivered') echo 'selected'; ?>>✅ Delivered</option>
                                            <option value="cancelled" <?php if ($fetch_order['status'] == 'cancelled') echo 'selected'; ?>>❌ Cancelled</option>
                                        </select>
                                        
                                        <button type="submit" name="update_order_status" class="btn update-btn" style="display: none;">
                                            <i class='bx bx-save'></i> Update
                                        </button>
                                        
                                        <a class="bx bx-trash" href="view_orders.php?delete_order=<?= $fetch_order['order_id'] ?>"
                                           onclick="return confirm('Are you sure you want to delete Order #<?= $fetch_order['order_id'] ?>? This action cannot be undone.')"></a>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="empty">No orders have been placed yet. Check back later!</p>';
                }
                ?>
            </div>
        </section>
    </div>

    <script>
        // Show/hide update button based on status change
        document.querySelectorAll('.status-select').forEach(select => {
            const currentStatus = select.dataset.current;
            const updateBtn = select.closest('form').querySelector('.update-btn');

            select.addEventListener('change', () => {
                if (select.value !== currentStatus) {
                    updateBtn.style.display = 'inline-flex';
                    updateBtn.style.alignItems = 'center';
                    updateBtn.style.gap = '0.5rem';
                } else {
                    updateBtn.style.display = 'none';
                }
            });
        });
        
        // Show success/warning messages if they exist in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');
        const warningMsg = urlParams.get('warning_msg');
        
        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: decodeURIComponent(successMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
        
        if (warningMsg) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: decodeURIComponent(warningMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
    </script>

    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>