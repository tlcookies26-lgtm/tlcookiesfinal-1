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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    // Sanitize and validate inputs
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_STRING);
    $price = filter_var(trim($_POST['price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_var(trim($_POST['description']), FILTER_SANITIZE_STRING);
    $benefits = filter_var(trim($_POST['benefits']), FILTER_SANITIZE_STRING);
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "uploads/product_pictures/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES["image"]["name"]); // Add timestamp to prevent duplicates
        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'jfif', 'webp', 'bmp', 'tiff', 'svg'];
        if (in_array($image_file_type, $allowed_extensions)) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $warning_msg = "Error uploading image.";
            }
        } else {
            $warning_msg = "Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, JFIF, WEBP, BMP, TIFF, SVG.";
        }
    }

    // Insert into database if all fields are filled
    if (!empty($name) && !empty($price) && !empty($description) && !empty($image_path)) {
        $stmt = $conn->prepare("INSERT INTO products (name, price, description, benefits, images, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$name, $price, $description, $benefits, $image_path]);
        $success_msg = 'Cookie added to shop successfully! 🍪';
        header("Location: view_products.php?success_msg=" . urlencode($success_msg));
        exit();
    } else {
        $warning_msg = "All fields are required!";
        header("Location: shop.php?warning_msg=" . urlencode($warning_msg));
        exit();
    }
}
?>
<?php $page = 'shop'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Add New Cookie</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>        
        h1 {
            color: var(--cookie-chocolate);
            font-size: 3rem;
            margin-bottom: 0.5rem;
            text-align: center;
            position: relative;
        }
        
        h1::after {
            content: '🍪';
            font-size: 2.5rem;
            margin-left: 1rem;
        }
        
        h2 {
            color: var(--cookie-brown);
            font-size: 2.2rem;
            margin: 2rem 0;
            text-align: center;
            border-bottom: 3px solid var(--cookie-tan);
            padding-bottom: 1rem;
        }
        
        .title2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: var(--cookie-cream);
            border-radius: 50px;
            margin-bottom: 2rem;
        }
        
        .title2 .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1rem 2rem !important;
            font-size: 1.4rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
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
        
        form {
            background: var(--cookie-cream);
            padding: 3rem;
            border-radius: 20px;
            border: 2px solid var(--cookie-tan);
        }
        
        form input[type="text"],
        form input[type="number"],
        form textarea {
            width: 100%;
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 15px;
            font-size: 1.4rem;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        form input[type="text"]:focus,
        form input[type="number"]:focus,
        form textarea:focus {
            border-color: var(--cookie-brown);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }
        
        form textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        form input[type="file"] {
            width: 100%;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border: 2px dashed var(--cookie-tan);
            border-radius: 15px;
            background: #fff;
            cursor: pointer;
        }
        
        form input[type="file"]:hover {
            border-color: var(--cookie-brown);
        }
        
        form input[type="submit"] {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1.5rem 3rem;
            border: none;
            border-radius: 50px;
            font-size: 1.8rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        form input[type="submit"]:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(139, 69, 19, 0.4);
        }
        
        /* Label styling */
        form label {
            display: block;
            color: var(--cookie-chocolate);
            font-size: 1.4rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Form section dividers */
        .form-section {
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 10px;
        }
        
        /* Required field indicator */
        .required::after {
            content: ' *';
            color: #ff6b6b;
            font-size: 1.8rem;
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="view_products.php" class="btn"><i class="bx bx-list-ul"></i> View All Cookies</a>
            <span>/ Add New Cookie</span>
        </div>        
        <h2>Bake a New Cookie</h2>
        
        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <label for="name" class="required">Cookie Name</label>
                <input type="text" name="name" id="name" placeholder="e.g., Double Chocolate Chip" required>
            </div>
            
            <div class="form-section">
                <label for="price" class="required">Price (₱)</label>
                <input type="number" step="0.01" name="price" id="price" placeholder="e.g., 45.00" required>
            </div>
            
            <div class="form-section">
                <label for="description" class="required">Description</label>
                <textarea name="description" id="description" placeholder="Describe your delicious cookie..." required></textarea>
            </div>
            
            <div class="form-section">
                <label for="benefits" class="required">Why Customers Love It</label>
                <textarea name="benefits" id="benefits" placeholder="What makes this cookie special?" required></textarea>
            </div>
            
            <div class="form-section">
                <label for="image" class="required">Cookie Photo</label>
                <input type="file" name="image" id="image" accept="image/*" required>
                <p style="color: #666; font-size: 1.2rem; margin-top: 0.5rem;">Upload a mouth-watering photo of your cookie!</p>
            </div>
            
            <input type="submit" name="add_product" value="Add Cookie to Shop">
        </form>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>
    
    <script>
        // Show success/warning messages if they exist in URL
        const urlParams = new URLSearchParams(window.location.search);
        const successMsg = urlParams.get('success_msg');
        const warningMsg = urlParams.get('warning_msg');
        
        if (successMsg) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: decodeURIComponent(successMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
        
        if (warningMsg) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning',
                text: decodeURIComponent(warningMsg),
                timer: 3000,
                showConfirmButton: true
            });
        }
    </script>
</body>

</html>