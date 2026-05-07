<?php
include '../includes/connection.php';
session_start(); // Start the session

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch the user ID from session
$user_id = $_SESSION['user_id'];

// Handle profile picture update
if (isset($_POST['update_profile_pic'])) {
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $profile_picture = $_FILES['profile_picture'];
        $target_dir = "../admin/uploads/profile_pictures/";
        $imageFileType = strtolower(pathinfo($profile_picture["name"], PATHINFO_EXTENSION));

        // Ensure target directory exists
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0755, true);
        }

        // Check if the file is an actual image
        $check = getimagesize($profile_picture["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
        } else {
            // Check file size (5MB limit)
            if ($profile_picture["size"] > 5000000) {
                echo "Sorry, your file is too large.";
            } else {
                // Allow certain file formats
                $allowed_formats = ["jpg", "jpeg", "png", "gif"];
                if (!in_array($imageFileType, $allowed_formats)) {
                    echo "Sorry, only JPG, JPEG, PNG, and GIF files are allowed.";
                } else {
                    // Create a unique filename
                    $filename = $user_id . "_" . time() . "." . $imageFileType;
                    $target_file = $target_dir . $filename;
                    $relative_path = "../admin/uploads/profile_pictures/" . $filename;

                    // Move the uploaded file
                    if (move_uploaded_file($profile_picture["tmp_name"], $target_file)) {
                        // Update the profile picture path in the database
                        $stmt = $conn->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
                        $stmt->execute([
                            'profile_picture' => $relative_path,
                            'user_id' => $user_id
                        ]);
                        header("Location: ../admin/view_profile.php");
                        exit;
                    } else {
                        echo "Sorry, there was an error uploading your file.";
                    }
                }
            }
        }
    }
}

// Handle user profile details update
if (isset($_POST['update_user'])) {
    $surname = $_POST['surname'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'];
    $phone = $_POST['phone'];
    $barangay = $_POST['barangay'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $username = $_POST['username'];

    // Update the user profile details in the database
    $stmt = $conn->prepare("UPDATE users SET surname = :surname, first_name = :first_name, middle_name = :middle_name, phone = :phone, barangay = :barangay, address = :address, email = :email, username = :username WHERE id = :user_id");
    $stmt->execute([
        'surname' => $surname,
        'first_name' => $first_name,
        'middle_name' => $middle_name,
        'phone' => $phone,
        'barangay' => $barangay,
        'address' => $address,
        'email' => $email,
        'username' => $username,
        'user_id' => $user_id
    ]);

    // Update session variables
    $_SESSION['surname'] = $surname;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['middle_name'] = $middle_name;
    $_SESSION['phone'] = $phone;
    $_SESSION['barangay'] = $barangay;
    $_SESSION['address'] = $address;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $username;

    header("Location: ../admin/view_profile.php");
    exit;
}
?>