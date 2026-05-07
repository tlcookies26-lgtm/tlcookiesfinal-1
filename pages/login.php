<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

if (isset($_POST['submit'])) {
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_SPECIAL_CHARS);
    $pass  = trim($_POST['pass']);

    if (empty($email) || empty($pass)) {
        $_SESSION['login_error'] = 'Please fill in all fields.';
        header('Location: login.php');
        exit;
    }

    $select_user = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
    $select_user->execute([$email]);
    $row = $select_user->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['user_id']     = $row['id'];
        $_SESSION['user_name']   = $row['username'];
        $_SESSION['user_email']  = $row['email'];
        $_SESSION['is_admin']    = $row['is_admin'];
        $_SESSION['first_name']  = $row['first_name'];
        $_SESSION['surname']     = $row['surname'];
        $_SESSION['middle_name'] = $row['middle_name'];
        $_SESSION['email']       = $row['email'];
        $_SESSION['phone']       = $row['phone'];
        $_SESSION['barangay']    = $row['barangay'];
        $_SESSION['address']     = $row['address'];

        $redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'index.php';
        header('Location: ' . $redirect);
    } else {
        $_SESSION['login_error'] = 'Incorrect email or password.';
        header('Location: login.php');
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Log In</title>
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body class="auth-page">
    <?php include '../includes/header.php'; ?>

    <!-- Login Form -->
    <div class="main-container-login">
        <section class="form-container">
            <form action="" method="post">
                <h1>Login</h1>

                <?php if (isset($_SESSION['login_error'])): ?>
                    <div class="error-message"><?= htmlspecialchars($_SESSION['login_error']); ?></div>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['unauthorized'])): ?>
                    <div class="error-message">You are not authorized to access that page.</div>
                    <?php unset($_SESSION['unauthorized']); ?>
                <?php endif; ?>

                <div class="input-field">
                    <label>Email <sup>*</sup></label>
                    <input type="email" name="email" required maxlength="50" minlength="12"
                        oninput="this.value = this.value.replace(/\s/g, '')">
                </div>

                <div class="input-field">
                    <label>Password <sup>*</sup></label>
                    <div class="password-container">
                        <input type="password" id="password" name="pass" required maxlength="16" minlength="6"
                            oninput="this.value = this.value.replace(/\s/g, '')">
                        <i id="toggle-pass" class="bx bx-hide toggle-password"></i>
                    </div>
                </div>

                <button type="submit" name="submit" class="btn">Login</button>
                <p>
                    Don't have an account?
                    <a href="register.php" class="link">Sign Up</a>
                </p>
            </form>
        </section>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        document.getElementById('toggle-pass').addEventListener('click', function () {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('bx-hide');
            this.classList.toggle('bx-show');
        });
    </script>
    <style></style>
    <?php include '../includes/alert.php'; ?>
</body>

</html>