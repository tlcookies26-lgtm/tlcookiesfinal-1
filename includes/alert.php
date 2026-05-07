<?php
    // Check for session-based success message
    if (isset($_SESSION['success_message'])) {
        echo '<script>
            Swal.fire({
                icon: "success",
                title: "Success",
                text: "' . $_SESSION['success_message'] . '",
                showConfirmButton: false,
                timer: 2000
            });
        </script>';
        unset($_SESSION['success_message']); // Clear after showing
    }

    // Check for other success messages
    if (!empty($success_msg)) {
        foreach ((array) $success_msg as $msg) {
            echo '<script>Swal.fire("' . $msg . '", "", "success");</script>';
        }
    }

    if (!empty($warning_msg)) {
        foreach ((array) $warning_msg as $msg) {
            echo '<script>Swal.fire("' . $msg . '", "", "warning");</script>';
        }
    }

    if (!empty($info_msg)) {
        foreach ((array) $info_msg as $msg) {
            echo '<script>Swal.fire("' . $msg . '", "", "info");</script>';
        }
    }

    if (!empty($error_msg)) {
        foreach ((array) $error_msg as $msg) {
            echo '<script>Swal.fire("' . $msg . '", "", "error");</script>';
        }
    }
?>
