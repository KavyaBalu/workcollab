<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

$supplier_id = $_GET['supplier_id'] ?? null;
$success_message = '';
$error_message = '';

// Check if supplier_id is provided
if (!$supplier_id) {
    header("Location: manage_suppliers.php");
    exit;
}

// Fetch supplier details
$query = "SELECT * FROM suppliers WHERE supplier_id = ?";
$stmt = $conn->prepare($query);
if ($stmt) {
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $supplier = $result->fetch_assoc();
    $stmt->close();
    
    if (!$supplier) {
        header("Location: manage_suppliers.php");
        exit;
    }
} else {
    $error_message = "Failed to fetch supplier details: " . $conn->error;
}

// Handle supplier update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_supplier'])) {
    $supplier_name = $_POST['supplier_name'];
    $contact_details = $_POST['contact_details'];
    $categories = $_POST['categories'];

    $query = "UPDATE suppliers SET name = ?, contact_info = ?, product_categories = ? WHERE supplier_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("sssi", $supplier_name, $contact_details, $categories, $supplier_id);
        if ($stmt->execute()) {
            $success_message = "Supplier updated successfully!";
        } else {
            $error_message = "Failed to update supplier: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Supplier</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Supplier</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Edit Supplier Form -->
        <form method="POST">
            <h2>Supplier Details</h2>
            <div class="form-group">
                <label for="supplier_name">Supplier Name</label>
                <input type="text" id="supplier_name" name="supplier_name" value="<?= $supplier['name'] ?>" required class="form-control">
            </div>
            <div class="form-group">
                <label for="contact_details">Contact Details</label>
                <textarea id="contact_details" name="contact_details" required class="form-control"><?= $supplier['contact_info'] ?></textarea>
            </div>
            <div class="form-group">
                <label for="categories">Categories (Comma-separated)</label>
                <input type="text" id="categories" name="categories" value="<?= $supplier['product_categories'] ?>" required class="form-control" placeholder="E.g., Phones, Mixers">
            </div>
            <button type="submit" name="update_supplier" class="btn btn-primary">Update Supplier</button>
        </form>
    </div>
</body>
</html>
