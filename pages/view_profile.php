<?php
include '../includes/connection.php';
session_start();

// Redirect to login page if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch user profile details using PDO
$user_id = $_SESSION['user_id']; // Assume the user ID is stored in session

// Prepare the query
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->execute(['user_id' => $user_id]);

// Fetch the result
$user = $stmt->fetch(PDO::FETCH_ASSOC);




if (isset($_POST['logout'])) {
    // Destroy the session
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect the user to the login page (or any other page after logout)
    header('Location: index.php');
    exit; // Make sure to call exit after header redirection
}

$profile_picture_path = !empty($user['profile_picture']) ? '../' . $user['profile_picture'] : '../assets/images/default-profile-photo.jpg';
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Profile</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
</head>

<body>
    <?php include '../includes/header.php'; ?>

    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Profile Information</h1>
        </div>

        <div class="title2">
            <a href="index.php">Home</a><span>/ Profile Information</span>
        </div>
        <section class="view-page">
            <div class="profile-info">
                <div class="profile-picture">
                    <h1>Profile Picture</h1>
                    <div class="form-container">
                        <form method="post" enctype="multipart/form-data" action="../includes/update_profile.php">
                            <input type="file" name="profile_picture" accept="image/*" required
                                id="profile_picture_input" style="display: none;" />
                            <label for="profile_picture_input">
                                <img class="profile" src="<?= $profile_picture_path ?>" alt="Upload"
                                    style="cursor: pointer;" />
                                <div class="btn-container" id="uploadBtnContainer" style="display: none;">
                                    <button type="submit" name="update_profile_pic" class="btn">Upload</button>
                                </div>
                            </label>
                            </form>
                        </form>
                    </div>
                </div>
                <div class="profile-details">
                    <h1>Profile Details
                        <button type="button" class="fa-edit" id="editProfileBtn">
                            <i class="bx bx-edit"></i>
                        </button>
                    </h1>

                    <form method="post" action="../includes/update_profile.php" id="profileForm">
                        <div class="update-field">
                            <strong>Username:</strong>
                            <span class="field-text"><?= $_SESSION['user_name']; ?></span>
                            <input type="text" name="username" class="field-input"
                                value="<?= $_SESSION['user_name']; ?>" maxlength="50"
                                oninput="this.value = this.value.replace(/\s/g, '')" style="display:none;">
                            <span class="error-message"></span>
                        </div>

                        <div class="update-field">
                            <strong>Email:</strong>
                            <span class="field-text"><?= $_SESSION['email']; ?></span>
                            <input type="email" name="email" value="<?= $_SESSION['email']; ?>" maxlength="50"
                                minlength="12" class="field-input" oninput="this.value = this.value.replace(/\s/g, '')"
                                style="display:none;">
                        </div>

                        <div class="update-field">
                            <strong>Surname:</strong>
                            <span class="field-text"><?= $_SESSION['surname']; ?></span>
                            <input type="text" name="surname" class="field-input" value="<?= $_SESSION['surname']; ?>"
                                maxlength="15" style="display:none;">
                        </div>

                        <div class="update-field">
                            <strong>First Name:</strong>
                            <span class="field-text"><?= $_SESSION['first_name']; ?></span>
                            <input type="text" name="first_name" class="field-input"
                                value="<?= $_SESSION['first_name']; ?>" maxlength="15" style="display:none;">
                        </div>

                        <div class="update-field">
                            <strong>Middle Name:</strong>
                            <span class="field-text"><?= $_SESSION['middle_name']; ?></span>
                            <input type="text" name="middle_name" class="field-input"
                                value="<?= $_SESSION['middle_name']; ?>" maxlength="15" style="display:none;">
                        </div>

                        <div class="update-field">
                            <strong>Phone Number:</strong>
                            <span class="field-text"><?= $_SESSION['phone']; ?></span>
                            <input type="tel" name="phone" value="<?= $_SESSION['phone']; ?>" class="field-input"
                                maxlength="15" oninput="this.value = this.value.replace(/\s/g, '')"
                                style="display:none;">
                        </div>

                        <div class="update-field">
                            <strong>Address:</strong>
                            <span class="field-text"><?= $_SESSION['address']; ?>, <?= $_SESSION['barangay']; ?>,
                                Zamboanga City</span>
                            <input type="text" name="address" class="field-input" value="<?= $_SESSION['address']; ?>"
                                required maxlength="100" style="display:none;">
                            <select name="barangay" required style="display:none;" class="field-input">
                                <option value="<?= $_SESSION['barangay']; ?>" selected>
                                    <?= $_SESSION['barangay']; ?>
                                </option>
                                <?php include '../includes/select.php'; ?>
                            </select>
                        </div>
                        <div id="saveProfileBtn" style="width: 100%; align-items: center; margin-top: 15px; display:none;">
                            <button type="submit" form="profileForm" name="update_user" class="btn" style="width: 20%;"
                                id="saveProfileBtn" style="display:none;">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        <?php include '../includes/footer.php'; ?>
    </div>
    <style>
        .view-page {
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .profile-info {
            width: 100%;
        }

        .form-container form .input-field {
            margin: 0px 20px;
        }

        .modal-content form {
            border-radius: 0%;
        }

        .modal-content .form-container {
            margin: 0 auto;
        }

        .modal-content .form-container form {
            height: 100%;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: center;
        }

        .view-page form {
            display: grid;
            grid-template-columns: repeat(1, 100%);
            width: 100%;
        }

        .update-field {
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 25px;
            margin-top: 10px;
            max-height: 50px;
        }

        .profile {
            object-fit: cover;
            border: 2px solid #000;
            width: 100%;
            height: 100%;
            max-width: 400px !important;
            max-height: 400px;
            border-radius: 50%;
        }

        .profile-details {
            margin: 30px auto;
            text-align: center;
        }

        .fa-edit {
            border-radius: .25rem;
            font-size: 1.7rem;
            height: 2.5rem;
            cursor: pointer;
        }

        .form-container form label {
            display: block;
            font-weight: bold;
            margin-top: 0% !important;
        }

        strong {
            width: 60%;
        }

        .field-text {
            width: 100%;
        }

        .field-input {
            height: 36px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
</body>

</html>