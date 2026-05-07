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
?>
<?php $page = 'admin'; ?> <!-- Change per page -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Admin</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>
    <div class="main">
        <section class="dashboard">
            <h1>Dashboard Panel</h1>
            <div class="box-container">
                <div class="box">
                    <?php
                    $select_user = $conn->prepare("SELECT * FROM `users`");
                    $select_user->execute();
                    $num_of_users = $select_user->rowCount();
                    ?>

                    <h3><?= $num_of_users; ?></h3>
                    <p>Users Available</p>
                </div>

                <div class="box">
                    <?php
                    $select_product = $conn->prepare("SELECT * FROM `products`");
                    $select_product->execute();
                    $num_of_products = $select_product->rowCount();
                    ?>

                    <h3><?= $num_of_products; ?></h3>
                    <p>products available</p>
                </div>
                <div class="box">
                    <?php
                    $select_discount = $conn->prepare("SELECT * FROM `discounts`");
                    $select_discount->execute();
                    $num_of_discount = $select_discount->rowCount();
                    ?>

                    <h3><?= $num_of_discount; ?></h3>
                    <p>discounts</p>
                </div>

                <div class="box">
                    <?php
                    $select_order = $conn->prepare("SELECT * FROM `orders`");
                    $select_order->execute();
                    $num_of_orders = $select_order->rowCount();
                    ?>

                    <h3><?= $num_of_orders; ?></h3>
                    <p>Orders</p>
                </div>

                <div class="box">
                    <?php
                    $select_message = $conn->prepare("SELECT * FROM `testimonials`");
                    $select_message->execute();
                    $num_of_messages = $select_message->rowCount();
                    ?>

                    <h3><?= $num_of_messages; ?></h3>
                    <p>Feedbacks</p>
                </div>
            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>