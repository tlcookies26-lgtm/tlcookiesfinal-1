<?php
include '../includes/connection.php';
session_start();

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    header("Location: ../pages/login.php");
    exit();
}

// Handle quantity update
if (isset($_POST['update_qty'])) {
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
    $cart_id = $_POST['cart_id'];
    $delete_cart = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delete_cart->execute([$cart_id, $user_id]);
    $_SESSION['success_message'] = "Item successfully removed from cart!";
    header("Location: ../pages/cart.php");
    exit();
}

// Handle empty cart
if (isset($_POST['empty_cart'])) {
    $clear_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clear_cart->execute([$user_id]);
    $_SESSION['success_message'] = "Your cart has been emptied!";
    header("Location: ../pages/cart.php");
    exit();
}

header("Location: ../pages/cart.php");
exit();
?>
