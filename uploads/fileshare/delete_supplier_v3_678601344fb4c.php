<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

// Check if supplier ID is provided
if (!isset($_GET['supplier_id']) || empty($_GET['supplier_id'])) {
    header("Location: manage_suppliers.php");
    exit;
}

$supplier_id = $_GET['supplier_id'];

// Delete supplier
$query = "DELETE FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $supplier_id);
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Supplier deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to delete supplier: " . $stmt->error;
    }
    $stmt->close();
} else {
    $_SESSION['error_message'] = "Failed to prepare statement: " . $conn->error;
}

// Redirect back to the suppliers management page
header("Location: manage_suppliers.php");
exit;
?>
