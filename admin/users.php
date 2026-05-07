<?php
include '../includes/connection.php';
session_start(); // Ensure DB and tables exist

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

// Fetch users
$users_query = $conn->query("SELECT id, username, profile_picture, email, is_admin, created_at FROM users ORDER BY created_at DESC");
$users = $users_query->fetchAll(PDO::FETCH_ASSOC);

// Delete user
if (isset($_GET['delete_user'])) {
    $delete_id = $_GET['delete_user'];

    // Check if the user is an admin before deleting
    $check = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $check->execute([$delete_id]);
    $user = $check->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['is_admin'] != 1) {
        $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$delete_id]);
    }

    header("Location: users.php");
    exit();
}


// Promote user to admin
if (isset($_GET['admin_user']) && isset($_GET['level'])) {
    $promote_id = $_GET['admin_user'];
    $level = intval($_GET['level']); // Ensure it's numeric

    if ($promote_id !== $user_id && in_array($level, [1, 2])) {
        $stmt = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $stmt->execute([$level, $promote_id]);
    }

    header("Location: users.php");
    exit();
}

// Demote user to regular user (level 0)
if (isset($_GET['demote_user']) && isset($_GET['level'])) {
    $admin_id = $_GET['demote_user'];
    $new_level = $_GET['level'];  // The new level for the user (0 = regular user)

    // Only proceed if the user is a limited admin (is_admin = 2)
    $check_user = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $check_user->execute([$admin_id]);
    $user = $check_user->fetch(PDO::FETCH_ASSOC);

    // Proceed if the user is a limited admin (is_admin == 2)
    if ($user && $user['is_admin'] == 2) {
        // Update the user's is_admin level
        $update_level = $conn->prepare("UPDATE users SET is_admin = ? WHERE id = ?");
        $update_level->execute([$new_level, $admin_id]);
    }

    // Redirect back to users page
    header("Location: users.php");
    exit();
}


?>
<?php $page = 'users'; ?> <!-- Change per page -->


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Users</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="admin.php">home </a><span>/ users</span>
        </div>
        <section class="users">
            <h1>Users</h1>
            <div class="table-container">
                <table border="1">
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Profile</th>
                        <th>Email</th>
                        <th>Created At</th>
                        <th>Actions</th>
                        <th>Role</th>

                    </tr>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['id']) ?></td>
                            <td><?= htmlspecialchars($user['username']) ?></td>
                            <td><img src="<?= htmlspecialchars($user['profile_picture']) ?>" width="50" height="50"></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td><?= htmlspecialchars($user['created_at']) ?></td>
                            <td>
                                <?php if ($user['id'] !== $user_id): ?>
                                    <?php if ($user['is_admin'] == 1): ?>
                                        <span style="color: gray;">Cannot Delete</span>
                                    <?php else: ?>
                                        <a class="btn" href="users.php?delete_user=<?= $user['id'] ?>"
                                            onclick="return confirm('Are you sure?')">Delete</a>
                                    <?php endif; ?>

                                    <!-- Check if the logged-in user is a Limited Admin -->
                                    <?php if ($_SESSION['is_admin'] != 2): ?>
                                        <!-- Promote to Limited Admin only for regular users (is_admin == 0) -->
                                        <?php if ($user['is_admin'] == 0): ?>
                                            <a class="btn" href="users.php?admin_user=<?= $user['id'] ?>&level=2"
                                                onclick="return confirm('Promote to Limited Admin?')">Limited Admin</a>
                                        <?php endif; ?>

                                        <!-- Demote to regular user only for Limited Admins (is_admin == 2) -->
                                        <?php if ($user['is_admin'] == 2): ?>
                                            <a class="btn" href="users.php?demote_user=<?= $user['id'] ?>&level=0"
                                                onclick="return confirm('Demote to User?')">User</a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span style="color: gray;">You</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php
                                if ($user['is_admin'] == 1)
                                    echo "<span style='color: green;'>Full Admin</span>";
                                elseif ($user['is_admin'] == 2)
                                    echo "<span style='color: orange;'>Limited Admin</span>";
                                else
                                    echo "User";
                                ?>
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