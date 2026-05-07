<header class="header">
    <div class="flex">
        <a href="index.php" class="logo-container"><img src="../assets/images/tlc.jpg" class="logo"></a>
        <nav class="navbar">
            <a href="index.php" class="<?= ($page == 'home') ? 'active' : '' ?>">home</a>
            <a href="products.php" class="<?= ($page == 'products') ? 'active' : '' ?>">products</a>
            <a href="orders.php" class="<?= ($page == 'orders') ? 'active' : '' ?>">orders</a>
            <a href="about.php" class="<?= ($page == 'about') ? 'active' : '' ?>">about us</a>
        </nav>
        <div class="icons">
            <?php
            $cart_count_items = $conn->prepare("SELECT * FROM `cart` WHERE user_id = ?");
            $cart_count_items->execute([$user_id]);
            $total_cart_items = $cart_count_items->rowCount();

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

            <a href="#" class="cart-btn" id="cartLink">
                <i class="bx bx-cart-download"></i><sup><?= $total_cart_items ?></sup></a>
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
                <a href="register.php" class="btn">sign up</a>
            <?php } ?>
        </div>


    </div>
</header>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const cartLink = document.getElementById("cartLink");

        cartLink.addEventListener("click", function(event) {
            <?php if (!isset($_SESSION['user_id'])) { ?>
                event.preventDefault();
                Swal.fire({
                    title: "You are not logged in!",
                    text: "Please login or create an account to access your cart.",
                    icon: "warning",
                    showCancelButton: true,
                    confirmButtonText: "Login",
                    cancelButtonText: "Sign Up"
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = "login.php";
                    } else {
                        window.location.href = "register.php";
                    }
                });
            <?php } else { ?>
                window.location.href = "cart.php";
            <?php } ?>
        });
    });
</script>