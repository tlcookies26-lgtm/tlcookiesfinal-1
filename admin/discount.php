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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_discount'])) {
    // Sanitize and validate inputs
    $title = filter_var(trim($_POST['title']), FILTER_SANITIZE_STRING);
    $description = filter_var(trim($_POST['description']), FILTER_SANITIZE_STRING);
    $discount_percentage = filter_var(trim($_POST['discount_percentage']), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $image_path = '';

    // Handle image upload
    if (isset($_FILES['discount_image']) && $_FILES['discount_image']['error'] == 0) {
        $target_dir = "uploads/banner_pictures/";
        
        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $image_name = time() . '_' . basename($_FILES["discount_image"]["name"]); // Add timestamp to prevent duplicates
        $target_file = $target_dir . $image_name;
        $image_file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Validate image file type
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($image_file_type, $allowed_extensions)) {
            if (move_uploaded_file($_FILES["discount_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file;
            } else {
                $warning_msg = "Error uploading discount image.";
            }
        } else {
            $warning_msg = "Invalid image format. Only JPG, PNG, GIF, and WEBP are allowed.";
        }
    }

    if (!empty($title) && !empty($description) && !empty($discount_percentage) && !empty($start_date) && !empty($end_date) && !empty($image_path)) {
        $stmt = $conn->prepare("INSERT INTO discounts (title, description, discount_percentage, start_date, end_date, image) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $discount_percentage, $start_date, $end_date, $image_path]);
        $success_msg = 'Discount added successfully! 🏷️';
        header("Location: view_discount.php?success_msg=" . urlencode($success_msg));
        exit();
    } else {
        $warning_msg = "All fields are required!";
        header("Location: discount.php?warning_msg=" . urlencode($warning_msg));
        exit();
    }
}

// Check if there's already an active discount (global, not category-specific)
$today = date('Y-m-d');
$active_discount_stmt = $conn->prepare("SELECT COUNT(*) FROM discounts WHERE end_date >= ?");
$active_discount_stmt->execute([$today]);
$active_discount_count = $active_discount_stmt->fetchColumn();

// Determine if a new discount can be added (limit to one active discount at a time)
$can_add_discount = ($active_discount_count == 0);

?>
<?php $page = 'view_discount'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Add Discount</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Discount form specific styles */
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
        
        h2 {
            color: var(--cookie-chocolate);
            font-size: 2.5rem;
            margin: 2rem 0;
            text-align: center;
            position: relative;
        }
        
        h2::after {
            content: '🍪';
            font-size: 2rem;
            margin-left: 1rem;
            opacity: 0.5;
        }
        
        form {
            background: #fff;
            border-radius: 30px;
            padding: 3rem;
            box-shadow: var(--shadow);
            border: 3px solid var(--cookie-tan);
        }
        
        form input[type="text"],
        form input[type="number"],
        form input[type="date"],
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
        form input[type="date"]:focus,
        form textarea:focus {
            border-color: var(--cookie-brown);
            outline: none;
            box-shadow: 0 0 0 3px rgba(139, 69, 19, 0.1);
        }
        
        form textarea {
            min-height: 120px;
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
        
        /* Date input styling */
        input[type="date"] {
            color: #555;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(0.5);
            cursor: pointer;
        }
        
        /* Form section dividers */
        .form-section {
            margin-bottom: 2rem;
            padding: 1rem;
            background: rgba(244, 230, 193, 0.2);
            border-radius: 10px;
        }
        
        /* Info box for discount status */
        .info-box {
            background: var(--cookie-cream);
            border-left: 5px solid var(--cookie-brown);
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
        }
        
        .info-box h3 {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-box p {
            font-size: 1.4rem;
            color: #555;
            line-height: 1.6;
        }
        
        /* Warning for no available discount slot */
        .warning-message {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #856404;
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            margin: 2rem 0;
        }
        
        .warning-message i {
            font-size: 4rem;
            margin-bottom: 1rem;
            display: block;
        }
        
        .warning-message p {
            font-size: 1.6rem;
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .title2 {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            form {
                padding: 2rem;
            }
            
            h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="view_discount.php" class="btn"><i class='bx bx-list-ul'></i> View All Discounts</a>
            <span> Add New Discount</span>
        </div>

        <h2>Create a Store-Wide Discount</h2>
        
        <!-- Discount Status Info -->
        <?php if ($can_add_discount): ?>
        <div class="info-box">
            <h3><i class='bx bx-info-circle'></i> Global Discount</h3>
            <p>This discount will apply to <strong>all cookies</strong> in your store. Only one active discount is allowed at a time.</p>
            <p style="margin-top: 1rem; color: var(--cookie-brown);">✨ Create a compelling offer to attract more customers!</p>
        </div>
        <?php else: ?>
        <div class="warning-message">
            <i class='bx bx-error-circle'></i>
            <p>There is already an active discount running!</p>
            <p style="font-size: 1.4rem;">You can only have one active discount at a time. Wait for the current discount to expire or delete it to add a new one.</p>
            <a href="view_discount.php" class="btn" style="margin-top: 1rem; display: inline-block;">Manage Existing Discounts</a>
        </div>
        <?php endif; ?>

        <?php if ($can_add_discount): ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-section">
                <label for="title" style="color: var(--cookie-chocolate); font-weight: bold;">Discount Title</label>
                <input type="text" name="title" id="title" placeholder="e.g., Summer Sale, Holiday Special" required>
            </div>
            
            <div class="form-section">
                <label for="description" style="color: var(--cookie-chocolate); font-weight: bold;">Description</label>
                <textarea name="description" id="description" placeholder="Describe the discount offer..." required></textarea>
            </div>
            
            <div class="form-section">
                <label for="discount_percentage" style="color: var(--cookie-chocolate); font-weight: bold;">Discount Percentage (%)</label>
                <input type="number" step="1" min="1" max="100" name="discount_percentage" id="discount_percentage" placeholder="e.g., 20" required>
            </div>
            
            <div class="form-section">
                <label for="start_date" style="color: var(--cookie-chocolate); font-weight: bold;">Start Date</label>
                <input type="date" name="start_date" id="start_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-section">
                <label for="end_date" style="color: var(--cookie-chocolate); font-weight: bold;">End Date</label>
                <input type="date" name="end_date" id="end_date" min="<?= date('Y-m-d') ?>" required>
            </div>
            
            <div class="form-section">
                <label for="discount_image" style="color: var(--cookie-chocolate); font-weight: bold;">Promotional Banner Image</label>
                <input type="file" name="discount_image" id="discount_image" accept="image/*" required>
                <p style="color: #666; font-size: 1.2rem; margin-top: 0.5rem;">Upload a banner image for this discount (JPG, PNG, GIF, WEBP)</p>
            </div>
            
            <input type="submit" name="add_discount" value="Add Discount">
        </form>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    
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
        
        // Date validation - ensure end date is after start date
        const startDate = document.getElementById('start_date');
        const endDate = document.getElementById('end_date');
        
        function validateDates() {
            if (startDate.value && endDate.value) {
                if (endDate.value < startDate.value) {
                    endDate.setCustomValidity('End date must be after start date');
                } else {
                    endDate.setCustomValidity('');
                }
            }
        }
        
        startDate.addEventListener('change', validateDates);
        endDate.addEventListener('change', validateDates);
        
        // Preview image before upload
        document.getElementById('discount_image').addEventListener('change', function(e) {
            if (e.target.files && e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // You could show a preview here if desired
                    console.log('Image selected');
                }
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
    
    <?php include '../includes/alert.php'; ?>
</body>

</html>