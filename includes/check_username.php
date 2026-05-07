<?php
// check_username.php

// Include your database connection (adjust the path if needed)
include '../includes/connection.php';

if (isset($_GET['username'])) {
    $username = trim($_GET['username']);
    
    // Prepare a statement to check for the username in the database.
    $select_username = $conn->prepare("SELECT 1 FROM `users` WHERE username = ? LIMIT 1");
    $select_username->execute([$username]);

    if ($select_username->rowCount() > 0) {
        echo "taken";
    } else {
        echo "available";
    }
}
?>
