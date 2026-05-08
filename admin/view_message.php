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

$messages_query = $conn->query("SELECT * FROM testimonials ORDER BY created_at DESC");
$messages = $messages_query->fetchAll(PDO::FETCH_ASSOC);

// Delete discount
if (isset($_GET['delete_testimonials'])) {
    $delete_id = $_GET['delete_testimonials'];
    $conn->prepare("DELETE FROM testimonials WHERE id = ?")->execute([$delete_id]);
    header("Location: view_message.php");
    exit();
}
?>
<?php $page = 'view_message'; ?> <!-- Change per page -->


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Messages</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="admin.php">home </a><span>/ feedbacks</span>
        </div>
        <section class="feedbacks">
            <h2>Feedback</h2>
            <div class="table-container">
                <table border="1">
                    <tr>
                        <th style="color: #3df1f0e !important">ID</th>
                        <th style="color: #3df1f0e !important">User ID</th>
                        <th style="color: #3df1f0e !important">Feedback</th>
                        <th style="color: #3df1f0e !important">Date</th>
                        <th style="color: #3df1f0e !important">Action</th>
                    </tr>
                    <?php foreach ($messages as $message): ?>
                        <tr>
                            <td><?= $message['id'] ?></td>
                            <td><?= htmlspecialchars($message['user_id']) ?></td>
                            <td><?= htmlspecialchars($message['message']) ?></td>
                            <td><?= htmlspecialchars($message['created_at']) ?></td>
                            <td>
                                <a class="btn" href="view_message.php?delete_testimonials=<?= $message['id'] ?>"
                                    onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>

            </div>
        </section>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>