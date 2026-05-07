<?php
include '../includes/connection.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Script started<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not logged in') . "<br>";
echo "POST Data: ";
print_r($_POST);
echo "<br>";

$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['user_id'])) {
    echo "User not logged in. Redirecting...<br>";
    header("Location: ../pages/login.php");
    exit();
}

// Handle quantity update
if (isset($_POST['update_qty'])) {
    echo "Updating quantity...<br>";
    $cart_id = $_POST['cart_id'];
    $new_qty = intval($_POST['qty']);

    if ($new_qty > 0) {
        $update_cart = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
        $update_cart->execute([$new_qty, $cart_id, $user_id]);

        $_SESSION['success_message'] = "Quantity updated successfully!";
    } else {
        $_SESSION['error_message'] = "Invalid quantity!";
    }

    header("Location: ../pages/cart.php");
    exit();
}

// Handle remove item
if (isset($_POST['remove_from_cart'])) {
    echo "Removing item from cart...<br>";
    $cart_id = $_POST['cart_id'];

    $delete_cart = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete_cart->execute([$cart_id, $user_id]);

    $_SESSION['success_message'] = "Item successfully removed from cart!";
    header("Location: ../pages/cart.php");
    exit();
}

// Debugging: Print received data
error_log("Script started");
error_log("User ID: " . $user_id);
error_log("POST Data: " . print_r($_POST, true));

if (isset($_POST['empty_cart'])) {
    error_log("Emptying cart...");
    $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart->execute([$user_id]);

    $_SESSION['success_message'] = "Your cart has been emptied!";
    header("Location: ../pages/cart.php");
    exit();
}

error_log("No action performed.");
?>
