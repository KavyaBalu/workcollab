<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is authorized (Worker only)
if ($_SESSION['role'] != 'Worker') {
    header("Location: error.php");
    exit;
}

$error_message = '';
$product_details = null;

// Handle Barcode Scan
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $barcode = $_POST['barcode'];

    // Fetch product and rack details based on the scanned barcode
    $query = "
        SELECT r.rack_location, p.product_name, p.category, s.supplier_name
        FROM rack r
        JOIN products p ON r.product_id = p.product_id
        JOIN suppliers s ON p.supplier_id = s.supplier_id
        WHERE r.rack_id = ?
    ";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $product_details = $result->fetch_assoc();
        } else {
            $error_message = "Invalid barcode or rack not found.";
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
    <title>Scan Barcode</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Scan Barcode</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Barcode Scan Form -->
        <form method="POST">
            <div class="mb-3">
                <label for="barcode" class="form-label">Scan Barcode</label>
                <input type="text" class="form-control" id="barcode" name="barcode" required>
            </div>
            <button type="submit" class="btn btn-primary">Find Rack</button>
        </form>

        <?php if ($product_details): ?>
            <h2>Product Details</h2>
            <table class="table">
                <tr>
                    <th>Product Name</th>
                    <td><?= $product_details['product_name'] ?></td>
                </tr>
                <tr>
                    <th>Category</th>
                    <td><?= $product_details['category'] ?></td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td><?= $product_details['supplier_name'] ?></td>
                </tr>
                <tr>
                    <th>Rack Location</th>
                    <td><?= $product_details['rack_location'] ?></td>
                </tr>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>
