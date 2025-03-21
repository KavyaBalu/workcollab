<?php
session_start();
require 'includes/db_connect.php';

// Redirect to login if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: pages/login.php");
    exit;
}

// Fetch user role from session
$role = $_SESSION['role'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Warehouse Management System - Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Warehouse Management System - Dashboard</h1>

        <?php if ($role == 'Admin'): ?>
            <h2>Admin Dashboard</h2>
            <ul>
                <li><a href="manage_suppliers.php">Manage Suppliers</a></li>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="manage_racks.php">Manage Racks</a></li>
            </ul>
        <?php elseif ($role == 'Manager'): ?>
            <h2>Manager Dashboard</h2>
            <ul>
                <li><a href="manage_products.php">Manage Products</a></li>
                <li><a href="manage_racks.php">Manage Racks</a></li>
            </ul>
        <?php elseif ($role == 'Accounting'): ?>
            <h2>Accounting Dashboard</h2>
            <ul>
                <li><a href="incoming_stock.php">Manage Incoming Stock</a></li>
                <li><a href="outgoing_stock.php">Manage Outgoing Stock</a></li>
            </ul>
        <?php elseif ($role == 'Worker'): ?>
            <h2>Worker Dashboard</h2>
            <ul>
                <li><a href="scan_barcode.php">Scan Product Barcode</a></li>
            </ul>
        <?php else: ?>
            <p>Access Denied</p>
        <?php endif; ?>

        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>
</body>
</html>
