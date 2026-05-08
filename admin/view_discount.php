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

$discounts_query = $conn->query("SELECT * FROM discounts ORDER BY 
    CASE 
        WHEN start_date <= CURDATE() AND end_date >= CURDATE() THEN 1
        WHEN start_date > CURDATE() THEN 2
        ELSE 3
    END, start_date DESC");
$discounts = $discounts_query->fetchAll(PDO::FETCH_ASSOC);

// Delete discount
if (isset($_GET['delete_discount'])) {
    $delete_id = $_GET['delete_discount'];

    // Optional: Delete associated image file
    $select_discount = $conn->prepare("SELECT image FROM discounts WHERE id = ?");
    $select_discount->execute([$delete_id]);
    $discount = $select_discount->fetch(PDO::FETCH_ASSOC);

    if ($discount && file_exists($discount['image'])) {
        unlink($discount['image']);
    }

    $conn->prepare("DELETE FROM discounts WHERE id = ?")->execute([$delete_id]);
    header("Location: view_discount.php?success_msg=" . urlencode("Discount deleted successfully! 🍪"));
    exit();
}
?>
<?php $page = 'view_discount'; ?> <!-- Change per page -->


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Discount Manager</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="discount.php" class="btn"><i class='bx bx-plus-circle'></i> Add New Discount</a>
            <span> Cookie Discounts</span>
        </div>

        <?php
        // Separate discounts by status
        $current_date = date('Y-m-d');
        $upcoming_discounts = [];
        $active_discounts = [];
        $expired_discounts = [];

        foreach ($discounts as $discount) {
            if ($discount['start_date'] > $current_date) {
                $upcoming_discounts[] = $discount;
            } elseif ($discount['end_date'] < $current_date) {
                $expired_discounts[] = $discount;
            } else {
                $active_discounts[] = $discount;
            }
        }
        ?>

        <!-- Active Discounts Section -->
        <section class="discounts">
            <h2><i class='bx bx-star'></i> Active Discounts</h2>
            <div class="table-container">
                <?php if (count($active_discounts) > 0): ?>
                    <table border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_discounts as $discount): ?>
                                <tr>
                                    <td><strong>#<?= $discount['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($discount['title']) ?></td>
                                    <td><?= htmlspecialchars(substr($discount['description'], 0, 50)) ?>...</td>
                                    <td><span class="status-badge status-active"><?= number_format($discount['discount_percentage']) ?>% OFF</span></td>
                                    <td><?= date('M d, Y', strtotime($discount['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($discount['end_date'])) ?></td>
                                    <td><img src="<?= htmlspecialchars($discount['image']) ?>" alt="Discount"></td>
                                    <td>
                                        <a class="btn" href="edit_discount.php?id=<?= $discount['id'] ?>"><i class='bx bx-edit'></i> Edit</a>
                                        <a class="btn delete-btn" href="view_discount.php?delete_discount=<?= $discount['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this discount? This action cannot be undone.')"><i class='bx bx-trash'></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-purchase-tag'></i>
                        <p>No active discounts at the moment.</p>
                        <a href="discount.php" class="btn" style="margin-top: 1rem;">Create a Discount</a>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Upcoming Discounts Section -->
        <section class="discounts">
            <h2><i class='bx bx-calendar'></i> Upcoming Discounts</h2>
            <div class="table-container">
                <?php if (count($upcoming_discounts) > 0): ?>
                    <table border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcoming_discounts as $discount): ?>
                                <tr>
                                    <td><strong>#<?= $discount['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($discount['title']) ?></td>
                                    <td><?= htmlspecialchars(substr($discount['description'], 0, 50)) ?>...</td>
                                    <td><span class="status-badge status-upcoming"><?= number_format($discount['discount_percentage']) ?>% OFF</span></td>
                                    <td><?= date('M d, Y', strtotime($discount['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($discount['end_date'])) ?></td>
                                    <td><img src="<?= htmlspecialchars($discount['image']) ?>" alt="Discount"></td>
                                    <td>
                                        <a class="btn" href="edit_discount.php?id=<?= $discount['id'] ?>"><i class='bx bx-edit'></i> Edit</a>
                                        <a class="btn delete-btn" href="view_discount.php?delete_discount=<?= $discount['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this upcoming discount?')"><i class='bx bx-trash'></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-calendar-x'></i>
                        <p>No upcoming discounts scheduled.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- Expired Discounts Section -->
        <section class="discounts">
            <h2><i class='bx bx-history'></i> Expired Discounts</h2>
            <div class="table-container">
                <?php if (count($expired_discounts) > 0): ?>
                    <table border="1">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Discount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Image</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expired_discounts as $discount): ?>
                                <tr>
                                    <td><strong>#<?= $discount['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($discount['title']) ?></td>
                                    <td><?= htmlspecialchars(substr($discount['description'], 0, 50)) ?>...</td>
                                    <td><span class="status-badge status-expired"><?= number_format($discount['discount_percentage']) ?>% OFF</span></td>
                                    <td><?= date('M d, Y', strtotime($discount['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($discount['end_date'])) ?></td>
                                    <td><img src="<?= htmlspecialchars($discount['image']) ?>" alt="Discount"></td>
                                    <td>
                                        <a class="btn" href="edit_discount.php?id=<?= $discount['id'] ?>"><i class='bx bx-edit'></i> Edit</a>
                                        <a class="btn delete-btn" href="view_discount.php?delete_discount=<?= $discount['id'] ?>"
                                            onclick="return confirm('Are you sure you want to delete this expired discount?')"><i class='bx bx-trash'></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class='bx bx-check-circle'></i>
                        <p>No expired discounts.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>

    <script>
        // Show success message if exists in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');

        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: decodeURIComponent(successMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
    </script>

    <?php include '../includes/alert.php'; ?>
</body>

</html>