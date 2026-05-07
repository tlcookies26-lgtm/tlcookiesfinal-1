<?php
// check_email.php

// Include your database connection (adjust the path if needed)
include '../includes/connection.php';

if (isset($_GET['email'])) {
    $email = trim($_GET['email']);
    
    // Prepare a statement to check for the email in the database.
    $select_email = $conn->prepare("SELECT 1 FROM `users` WHERE email = ? LIMIT 1");
    $select_email->execute([$email]);

    if ($select_email->rowCount() > 0) {
        echo "taken";
    } else {
        echo "available";
    }
}
?>
