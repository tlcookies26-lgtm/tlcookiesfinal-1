<?php
    include '../includes/connection.php';
    session_start();

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    } else {
        $user_id = '';
    }

    if (isset($_POST['logout'])) {
        session_unset(); // Unset all session variables
        session_destroy(); // Destroy the session

        header('Location: index.php');
        exit;
    }
    ?>
    <?php $page = 'orders'; ?> <!-- Change per page -->

    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Tender Loving Cookies - Orders</title>
        <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
        <link rel="stylesheet" href="../assets/css/styles.css" />
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>

    <body>
        <?php include '../includes/header.php'; ?>

        <div class="main">
            <div class="banner">
                <div class="overlay"></div>
                <h1>my orders</h1>
            </div>

            <div class="title2">
                <a href="index.php">home </a><span>/ orders</span>
            </div>

            <section class="products">
                <div class="box-container">
                    <div class="title">
                        <img src="../assets/images/download.png" class="logo2">
                        <h1>my orders</h1>
                    </div>
                </div>
                <div class="box-container">
                    <?php
                    // Check if the reorder button is clicked
                    if (isset($_POST['reorder']) && !empty($_POST['cancel_order_id'])) {
                        $reorder_id = $_POST['cancel_order_id'];

                        // Update order status to 'pending'
                        $reorder_stmt = $conn->prepare("UPDATE order_items SET status = 'pending' WHERE order_id = ?");
                        $reorder_stmt->execute([$reorder_id]);

                        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Order Reordered',
                text: 'Your order has been successfully reordered and status updated to pending.',
                confirmButtonColor: '#3085d6'
            }).then(() => {
                window.location.href = 'orders.php';
                die();
            });
        </script>";
                    }
                    if (isset($_POST['cancel_order']) && !empty($_POST['cancel_order_id'])) {
                        $cancel_order_id = $_POST['cancel_order_id'];

                        // Update order status to 'cancelled'
                        $cancel_stmt = $conn->prepare("UPDATE order_items SET status = 'cancelled' WHERE order_id = ?");
                        $cancel_stmt->execute([$cancel_order_id]);

                        echo "<script>
                            Swal.fire({
                                icon: 'success',
                                title: 'Order Cancelled',
                                text: 'Your order has been successfully cancelled.',
                                confirmButtonColor: '#3085d6'
                            }).then(() => {
                                window.location.href = 'orders.php';
                                die();
                            });
                        </script>";
                    }

                    $select_orders = $conn->prepare("
    SELECT o.id AS order_id, o.order_date, oi.status, 
    u.first_name, u.middle_name, u.surname, u.phone, u.email, u.address, u.barangay,
    SUM(oi.sub_total) AS total_amount
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN users u ON o.user_id = u.id
    WHERE o.user_id = ?
    GROUP BY o.id, o.order_date, oi.status, u.first_name, u.middle_name, u.surname, u.phone, u.email, u.address, u.barangay
    ORDER BY o.order_date DESC
    ");

                    $select_orders->execute([$user_id]);


                    if ($select_orders->rowCount() > 0) {
                        while ($fetch_order = $select_orders->fetch(PDO::FETCH_ASSOC)) {
                            ?>
                            <div class="box" <?php if ($fetch_order['status'] == 'cancelled') {
                                echo 'style="border:2px solid red";';
                            } elseif ($fetch_order['status'] == 'pending') {
                                echo 'style="border:2px solid orange";';
                            } elseif ($fetch_order['status'] == 'processing') {
                                echo 'style="border:2px solid blue";';
                            } elseif ($fetch_order['status'] == 'delivered') {
                                echo 'style="border:2px solid green";';
                            } ?>>
                                <a class="product-billing">
                                    <p class="date">
                                        <i class="bi bi-calendar-fill"></i>
                                        <span><?= $fetch_order['order_date']; ?></span>
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
                                        <img src="../admin/<?= $fetch_product['images']; ?>" class="img">
                                        <div class="row">
                                            <h3 class="name"><?= $fetch_product['name']; ?></h3>
                                            <p class="price">Price : ₱<?= $fetch_product['price']; ?> X <?= $fetch_product['qty']; ?>
                                            </p>
                                            <p class="price">Total : ₱<?= $fetch_product['sub_total']; ?></p>
                                        </div>
                                        <?php
                                    }
                                    ?>

                                </a>
                                <div class="billing-address">
                                    <a>Billing Address</a>
                                    <p class="bx bxs-user">
                                        <?= $fetch_order['first_name'] . ' ' . $fetch_order['middle_name'] . ' ' . $fetch_order['surname']; ?>
                                    </p>
                                    <p class="bx bxs-phone"> <?= $fetch_order['phone']; ?></p>
                                    <p class="bx bxs-envelope"> <?= $fetch_order['email']; ?></p>
                                    <p class="bx bxs-map-pin"> <?= $fetch_order['address']; ?>, <?= $fetch_order['barangay']; ?>,
                                        Zamboanga City</p>
                                    <p class="status" style="color:<?php
                                    if ($fetch_order['status'] == 'delivered') {
                                        echo 'green';
                                    } elseif ($fetch_order['status'] == 'cancelled') {
                                        echo 'red';
                                    } elseif ($fetch_order['status'] == 'pending') {
                                        echo 'orange';
                                    } elseif ($fetch_order['status'] == 'processing') {
                                        echo 'blue';
                                    } else {
                                        echo 'yellow';
                                    } ?>">
                                        <?= ucfirst($fetch_order['status']); ?>
                                    </p>
                                    <form method="POST">
                                        <input type="hidden" name="cancel_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="processing_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="delivered_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <input type="hidden" name="pending_order_id" value="<?= $fetch_order['order_id']; ?>">
                                        <?php if ($fetch_order['status'] == 'cancelled'): ?>
                                            <button type="submit" name="reorder" class="btn">Reorder</button>
                                        <?php elseif ($fetch_order['status'] == 'pending'): ?>
                                            <button type="submit" name="cancel_order" class="btn">Cancel Order</button>
                                        <?php else: ?>
                                        <?php endif; ?>
                                    </form>
                                </div>
                            </div>
                            <?php
                        }
                    } else {
                        echo '<p class="empty">no order takes placed yet</p>';
                    }
                    ?>
            </section>
            <?php include '../includes/footer.php'; ?>

        </div>

        <style>
            .box {
                width: 95%;
                display: grid !important;
                grid-template-columns: repeat(2, auto);
                align-items: center !important;
                margin: 20px auto !important;
                justify-items: center;
                min-height: 500px;
            }

            .img {
                max-width: 400px !important;
                min-width: 400px;
                max-height: 250px;
            }

            .product-billing {
                justify-items: center;
                align-self: center;
            }

            .row {
                margin: 0px;
            }

            .billing-address {
                display: flex;
                flex-direction: column;
            }

            .billing-address a {
                background: var(--green);
                background-size: cover;
                font-size: 25px;
                padding: 10px 20px;
                border-radius: 25px;
                text-transform: capitalize;
            }

            .billing-address p {
                margin-top: 30px !important;
                width: 100%;
            }

            .btn {
                position: relative !important;
            }

            .products .box-container .box:hover {
                transform: scale(1);
            }
        </style>
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <script>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Product ordered successfully.',
                    confirmButtonColor: "#87a243", // Green color for success
                });
            </script>
        <?php endif; ?>

        <script>
            if (window.location.search.includes('success=1')) {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                window.history.replaceState({}, document.title, url.toString());
            }
        </script>


        <script src="../assets/js/script.js"></script>
        <?php include '../includes/alert.php'; ?>
    </body>

    </html>