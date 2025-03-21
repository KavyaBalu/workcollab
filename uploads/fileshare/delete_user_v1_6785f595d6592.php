<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

$error_message = '';

// Fetch the user ID from the query string
if (isset($_GET['user_id'])) {
    $user_id = $_GET['user_id'];

    // Delete the user from the database
    $delete_query = "DELETE FROM users WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    if ($delete_stmt) {
        $delete_stmt->bind_param("i", $user_id);
        if ($delete_stmt->execute()) {
            header("Location: manage_users.php?success=User deleted successfully.");
            exit;
        } else {
            $error_message = "Failed to delete user: " . $delete_stmt->error;
        }
        $delete_stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
} else {
    $error_message = "User ID not specified!";
}

if ($error_message) {
    echo "<div class='alert alert-danger'>{$error_message}</div>";
    echo "<a href='manage_user.php'>Back to User Management</a>";
}
