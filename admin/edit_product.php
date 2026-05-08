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

$user_id = $_SESSION['user_id'];

// Get the product ID
if (!isset($_GET['pid'])) {
    header("Location: shop.php");
    exit();
}

$pid = $_GET['pid'];

// Fetch the product
$select_product = $conn->prepare("SELECT * FROM products WHERE id = ?");
$select_product->execute([$pid]);
$product = $select_product->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    echo "<div style='text-align: center; padding: 5rem;'>";
    echo "<h1 style='color: var(--cookie-chocolate);'>🍪 Cookie Not Found</h1>";
    echo "<p style='font-size: 1.6rem; margin: 2rem;'>The cookie you're trying to edit doesn't exist.</p>";
    echo "<a href='view_products.php' class='btn' style='padding: 1rem 3rem;'>Back to Cookies</a>";
    echo "</div>";
    exit();
}

// Handle update
if (isset($_POST['update_product'])) {
    $name = filter_var(trim($_POST['name']), FILTER_SANITIZE_SPECIAL_CHARS);
    $price = filter_var(trim($_POST['price']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $description = filter_var(trim($_POST['description']), FILTER_SANITIZE_SPECIAL_CHARS);
    $benefits = filter_var(trim($_POST['benefits']), FILTER_SANITIZE_SPECIAL_CHARS);
    $image_path = $product['images']; // Keep existing image by default

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
                
                // Optional: Delete old image file
                if (file_exists($product['images']) && $product['images'] != $image_path) {
                    unlink($product['images']);
                }
            } else {
                echo "<script>alert('Error uploading image.');</script>";
            }
        } else {
            echo "<script>alert('Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF, JFIF, WEBP, BMP, TIFF, SVG.');</script>";
        }
    }

    // Update product
    $update = $conn->prepare("UPDATE products SET name = ?, price = ?, description = ?, benefits = ?, images = ? WHERE id = ?");
    $update->execute([$name, $price, $description, $benefits, $image_path, $pid]);

    // Redirect with success message
    header("Location: view_products.php?success_msg=" . urlencode("Cookie updated successfully! 🍪"));
    exit();
}
?>
<?php $page = 'view_products'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Edit Cookie</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="../assets/css/admin_styles.css" />
    <style>
        /* Cookie-themed edit page styles */      
        .title2 {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 2rem;
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
            transform: translateX(-5px);
        }
        
        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            font-weight: bold;
        }
        
        .title2 span::before {
            content: '🍪';
            margin-right: 0.5rem;
        }
        
        .form-container form {
            background: #fff;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: var(--shadow);
            border: 3px solid var(--cookie-tan);
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: start;
        }
        
        .box, .box2 {
            background: var(--cookie-cream);
            padding: 2.5rem;
            border-radius: 20px;
            border: 2px solid var(--cookie-tan);
            transition: all 0.3s ease;
        }
        
        .box:hover, .box2:hover {
            border-color: var(--cookie-brown);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.1);
        }
        
        .box h3 {
            color: var(--cookie-chocolate);
            font-size: 2.5rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
        }
        
        .box h3::after {
            content: '🍪';
            font-size: 2rem;
            margin-left: 1rem;
        }
        
        .box label {
            display: block;
            text-align: center;
            cursor: pointer;
            margin: 0 !important;
        }
        
        .box label img {
            width: 300px;
            height: 300px;
            object-fit: cover;
            border-radius: 20px;
            border: 4px solid var(--cookie-tan);
            transition: all 0.3s ease;
            box-shadow: var(--shadow);
        }
        
        .box label img:hover {
            transform: scale(1.02);
            border-color: var(--cookie-brown);
        }
        
        .box strong {
            display: block;
            color: var(--cookie-chocolate);
            font-size: 1.6rem;
            margin: 1rem 0 0.5rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .columns {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .columns strong {
            text-align: center;
            font-size: 1.4rem;
        }
        
        .columns input {
            width: 100%;
            padding: 1rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            font-size: 1.4rem;
            transition: all 0.3s ease;
            background: #fff;
        }
        
        .columns input:focus {
            border-color: var(--cookie-brown);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }
        
        .columns input[readonly] {
            background: #f5f5f5;
            color: #666;
            cursor: not-allowed;
        }
        
        .box2 {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        
        .box2 strong {
            margin-top: 1.5rem;
        }
        
        .box2 strong:first-of-type {
            margin-top: 0;
        }
        
        .box2 textarea {
            width: 100%;
            min-height: 120px;
            padding: 1.2rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 15px;
            font-size: 1.4rem;
            line-height: 1.6;
            resize: vertical;
            background: #fff;
            transition: all 0.3s ease;
        }
        
        .box2 textarea:focus {
            border-color: var(--cookie-brown);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }
        
        .box2 .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1.5rem 3rem;
            font-size: 1.8rem;
            border-radius: 50px;
            margin-top: 2rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 2px;
            font-weight: bold;
        }
        
        .box2 .btn:hover {
            background-color: var(--cookie-chocolate);
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(139, 69, 19, 0.3);
        }
        
        /* Price input styling */
        input[type="number"] {
            -moz-appearance: textfield;
        }
        
        input[type="number"]::-webkit-outer-spin-button,
        input[type="number"]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="view_products.php" class="btn"><i class='bx bx-arrow-back'></i> Back to Cookies</a>
            <span> Edit Cookie</span>
        </div>

        <section class="form-container">
            <form action="" method="post" enctype="multipart/form-data">
                <div class="box">
                    <h3>Edit Cookie Details</h3>

                    <strong>Current Cookie Photo:</strong>
                    <input type="file" name="image" accept="image/*" id="product_img_input">
                    <label for="product_img_input">
                        <img src="../admin/<?= $product['images']; ?>" alt="<?= htmlspecialchars($product['name']); ?> Cookie">
                        <p style="color: var(--cookie-brown); margin-top: 1rem; font-size: 1.2rem;">Click to change photo</p>
                    </label>

                    <div class="columns">
                        <strong>Cookie Name:</strong>
                        <strong>Price (₱):</strong>

                        <input type="text" name="name" required value="<?= htmlspecialchars($product['name']); ?>" placeholder="e.g., Double Chocolate Chip">
                        <input type="number" name="price" required step="0.01" min="0" value="<?= $product['price']; ?>" placeholder="0.00">
                    </div>
                    
                </div>

                <div class="box2">
                    <strong>Description:</strong>
                    <textarea name="description" required class="input" placeholder="Describe this delicious cookie..."><?= htmlspecialchars($product['description']); ?></textarea>

                    <strong>Why Customers Love It:</strong>
                    <textarea name="benefits" required class="input" placeholder="What makes this cookie special?"><?= htmlspecialchars($product['benefits']); ?></textarea>

                    <input type="submit" name="update_product" value="Update Cookie" class="btn">
                </div>
            </form>
        </section>
    </div>

    <script src="../assets/js/script.js"></script>
    
    <script>
        // Show image preview when new image is selected
        document.getElementById('product_img_input').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('.box label img');
                    img.src = e.target.result;
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
        
        // Confirm before leaving without saving
        let formChanged = false;
        const form = document.querySelector('form');
        const inputs = form.querySelectorAll('input[type="text"], input[type="number"], textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                formChanged = true;
            });
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        form.addEventListener('submit', function() {
            formChanged = false;
        });
    </script>
    
    <?php include '../includes/alert.php'; ?>
</body>

</html>