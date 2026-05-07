<header class="header">
    <div class="flex">
        <a href="index.php" class="logo-container"><img src="../assets/images/tlc.jpg" class="logo"></a>
        <nav class="navbar">
            <a href="admin.php" class="<?= ($page == 'admin') ? 'active' : '' ?>">dashboard</a>
            <a href="users.php" class="<?= ($page == 'users') ? 'active' : '' ?>">view users</a>
            <a href="view_orders.php" class="<?= ($page == 'view_orders') ? 'active' : '' ?>">view orders</a>
            <a href="view_message.php" class="<?= ($page == 'view_message') ? 'active' : '' ?>">view feedbacks</a>
            <a href="view_products.php" class="<?= ($page == 'view_products') ? 'active' : '' ?>">view shop</a>
            <a href="view_discount.php" class="<?= ($page == 'view_discount') ? 'active' : '' ?>">view discounts</a>
        </nav>
        <div class="icons">
            <?php
            $query = $conn->prepare("SELECT profile_picture FROM `users` WHERE id = ?");
            $query->execute([$user_id]);
            $user = $query->fetch(PDO::FETCH_ASSOC);

            // Check if user data exists before trying to access the profile picture
            if ($user) {
                $profile_picture_path = $user['profile_picture'] ? $user['profile_picture'] : '../assets/images/default-profile-photo.jpg';
            } else {
                // Default profile picture if no user found
                $profile_picture_path = '../assets/images/default-profile-photo.jpg';
            }
            ?>

            <i class="bx bxs-user" id="user-btn"></i>
            <i class="bx bx-list-plus" id="menu-btn" style="font-size: 2rem;"></i>
        </div>
        <div class="user-box">
            <!-- Display profile picture if available -->
            <div style="display: flex; flex-direction: column; align-items: center;">
                <img src="<?= $profile_picture_path ?>" alt="Profile Picture" class="logo">
                <a href="view_profile.php" class="btn" style="margin-top: 10px;">Edit Profile</a>
            </div>
            <p>username : <span><?= $_SESSION['user_name'] ?? 'Guest'; ?></span></p>
            <p>email : <span><?= $_SESSION['user_email'] ?? 'Not Logged In'; ?></span></p>
            <?php if (isset($_SESSION['user_id'])) { ?>
                <form method="post">
                    <button type="submit" name="logout" class="logout-btn">log out</button>
                </form>
            <?php } else { ?>
                <a href="login.php" class="btn">login</a>
            <?php } ?>
        </div>


    </div>
</header>