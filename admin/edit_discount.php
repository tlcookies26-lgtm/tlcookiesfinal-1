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
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: view_discount.php");
    exit();
}

$discount_id = $_GET['id'];
$select_discount = $conn->prepare("SELECT * FROM discounts WHERE id = ?");
$select_discount->execute([$discount_id]);
$discount = $select_discount->fetch(PDO::FETCH_ASSOC);

if (!$discount) {
    echo "Discount not found!";
    exit();
}

if (isset($_POST['update_discount'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $percentage = $_POST['discount_percentage'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    if (!empty($image)) {
        $upload_dir = __DIR__ . "/uploads/discount_pictures/";
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $image_path = "uploads/discount_pictures/" . basename($image);
        move_uploaded_file($image_tmp, $upload_dir . basename($image));

        $update = $conn->prepare("UPDATE discounts SET title=?, description=?, discount_percentage=?, start_date=?, end_date=?, image=? WHERE id=?");
        $update->execute([$title, $description, $percentage, $start_date, $end_date, $image_path, $discount_id]);
    } else {
        $update = $conn->prepare("UPDATE discounts SET title=?, description=?, discount_percentage=?, start_date=?, end_date=? WHERE id=?");
        $update->execute([$title, $description, $percentage, $start_date, $end_date, $discount_id]);
    }

    header("Location: view_discount.php");
    exit();
}
?>
<?php $page = 'view_discount'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Edit Discount</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="view_discount.php" class="btn" style="color: #000; padding: 2px 20px; line-height: normal;"><i class='bx bx-arrow-back'></i></a><span> / edit discount</span>
        </div>

        <section class="form-container">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="box">
                    <h3>Edit Discount</h3>
                    <strong>Current Image:</strong>
                    <input type="file" name="image" accept="image/*" id="discount_img_input" style="display: none;">
                    <label for="discount_img_input">
                        <img src="<?= htmlspecialchars($discount['image'] ?? ''); ?>" alt="Upload"
                            style="width: 300px; cursor: pointer;">
                    </label>

                    <div class="columns">
                        <strong>Title:</strong>
                        <strong>Discount Percentage:</strong>

                        <input type="text" name="title" required value="<?= htmlspecialchars($discount['title'] ?? ''); ?>">
                        <input type="number" name="discount_percentage" required step="0.01" min="0"
                            value="<?= htmlspecialchars($discount['discount_percentage'] ?? ''); ?>">
                    </div>
                </div>

                <div class="box2">
                    <strong>Description:</strong>
                    <textarea name="description" required
                        class="input"><?= htmlspecialchars($discount['description'] ?? ''); ?></textarea>

                    <strong>Start Date:</strong>
                    <input type="date" name="start_date" required
                        value="<?= htmlspecialchars($discount['start_date'] ?? ''); ?>">

                    <strong>End Date:</strong>
                    <input type="date" name="end_date" required value="<?= htmlspecialchars($discount['end_date'] ?? ''); ?>">

                    <input type="submit" name="update_discount" value="Update Discount" class="btn">
                </div>
            </form>
        </section>
    </div>

    <script src="../assets/js/script.js"></script>
</body>

<style>
    .form-container label {
        margin: 0 !important;
    }

    .columns {
        display: grid;
        grid-template-columns: repeat(2, auto);
        gap: 0px 20px;
    }

    .box {
        padding: 1rem;
        position: relative;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        flex-direction: column;
        align-items: center;
    }

    .box2 {
        width: 40%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .box2 textarea,
    .box2 input[type="date"] {
        width: 450px;
    }

    .form-container form {
        padding: 1rem;
        border-radius: 10%;
        position: relative;
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        flex-direction: row;
        align-items: center;
    }
</style>

</html>