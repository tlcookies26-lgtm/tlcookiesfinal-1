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
    <style>
        /* Discount management specific styles */
        .title2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem 2rem;
            background: var(--cookie-cream);
            border-radius: 50px;
            margin-bottom: 3rem;
            border: 2px solid var(--cookie-tan);
        }

        .title2 .btn {
            background-color: var(--cookie-brown);
            color: #fff !important;
            padding: 1rem 2rem !important;
            font-size: 1.4rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .title2 .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(139, 69, 19, 0.3);
        }

        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            font-weight: bold;
        }

        .title2 span::before {
            content: '🏷️';
            margin-right: 0.5rem;
        }

        .discounts {
            background: #fff;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow);
            border: 2px solid var(--cookie-tan);
        }

        .discounts h2 {
            color: var(--cookie-chocolate);
            font-size: 2.2rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 3px solid var(--cookie-tan);
            position: relative;
        }

        .discounts h2::after {
            content: '🍪';
            position: absolute;
            right: 0;
            top: 0;
            font-size: 2rem;
            opacity: 0.3;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 15px;
            overflow: hidden;
        }

        table th {
            background: var(--cookie-brown);
            color: #fff;
            font-size: 1.4rem;
            padding: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        table td {
            padding: 1.2rem;
            font-size: 1.4rem;
            border-bottom: 1px solid var(--cookie-tan);
            color: #555;
        }

        table tr:hover {
            background: var(--cookie-cream);
        }

        table img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid var(--cookie-tan);
            transition: transform 0.3s ease;
        }

        table img:hover {
            transform: scale(2);
            z-index: 10;
            box-shadow: var(--shadow);
        }

        table .btn {
            background-color: var(--cookie-brown);
            color: #fff !important;
            padding: 0.8rem 1.5rem !important;
            font-size: 1.2rem !important;
            border-radius: 20px;
            margin: 0 0.3rem;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
        }

        table .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
        }

        table .btn.delete-btn {
            background-color: #dc3545;
        }

        table .btn.delete-btn:hover {
            background-color: #c82333;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 1.2rem;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-upcoming {
            background: #ffc107;
            color: #856404;
        }

        .status-active {
            background: #28a745;
            color: #fff;
        }

        .status-expired {
            background: #6c757d;
            color: #fff;
        }

        /* Section headers with different colors */
        .discounts:first-of-type h2 {
            color: #28a745;
        }

        .discounts:first-of-type h2::after {
            content: '✨';
        }

        .discounts:nth-of-type(2) h2 {
            color: #ffc107;
        }

        .discounts:nth-of-type(2) h2::after {
            content: '⏳';
        }

        .discounts:last-of-type h2 {
            color: #6c757d;
        }

        .discounts:last-of-type h2::after {
            content: '⌛';
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #999;
            font-size: 1.6rem;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
            color: var(--cookie-tan);
        }

        @media (max-width: 768px) {
            .title2 {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            table th,
            table td {
                font-size: 1.2rem;
                padding: 0.8rem;
            }

            table .btn {
                padding: 0.5rem 1rem !important;
                font-size: 1rem !important;
                margin: 0.2rem;
            }

            .discounts h2 {
                font-size: 1.8rem;
            }
        }
    </style>
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