<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch user details from session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Role-specific content
function getDashboardContent($role) {
    switch ($role) {
        case 'Admin':
            return "
                <h2>Admin Dashboard</h2>
                <ul>
                    <li><a href='manage_users.php'>Manage Users</a></li>
                    <li><a href='manage_suppliers.php'>Manage Suppliers</a></li>
                    <li><a href='manage_products.php'>Manage Products</a></li>
                </ul>
            ";
        case 'Manager':
            return "
                <h2>Manager Dashboard</h2>
                <ul>
                    <li><a href='assign_racks.php'>Assign Racks</a></li>
                    <li><a href='generate_barcodes.php'>Generate Barcodes</a></li>
                </ul>
            ";
        case 'Accounting':
            return "
                <h2>Accounting Dashboard</h2>
                <ul>
                    <li><a href='incoming_stock.php'>Manage Incoming Stock</a></li>
                    <li><a href='outgoing_stock.php'>Manage Outgoing Stock</a></li>
                </ul>
            ";
        case 'Worker':
            return "
                <h2>Worker Dashboard</h2>
                <ul>
                    <li><a href='scan_barcode.php'>Scan Barcode</a></li>
                    <li><a href='view_assigned_tasks.php'>View Assigned Tasks</a></li>
                </ul>
            ";
        default:
            return "<h2>Invalid Role</h2>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Warehouse Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="dashboard-container">
        <header>
            <h1>Welcome, <?= htmlspecialchars($username) ?>!</h1>
            <p>Your Role: <?= htmlspecialchars($role) ?></p>
            <a href="logout.php" class="btn btn-danger">Logout</a>
        </header>
        <main>
            <?= getDashboardContent($role) ?>
        </main>
    </div>
</body>
</html>
