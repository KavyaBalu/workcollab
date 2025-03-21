<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is an Admin
if ($_SESSION['role'] != 'Admin') {
    header("Location: error.php");
    exit;
}

// Messages for success or error
$success_message = '';
$error_message = '';

// Handle User Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $category = isset($_POST['category']) ? $_POST['category'] : null;

    $query = "INSERT INTO users (username, password, role, assigned_category) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ssss", $username, $password, $role, $category);
        if ($stmt->execute()) {
            $success_message = "User added successfully!";
        } else {
            $error_message = "Failed to add user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}

// Fetch all users
$users_query = "SELECT * FROM users";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Manage Users</h1>

        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Add User Form -->
        <form method="POST">
            <h2>Add User</h2>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required class="form-control">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required class="form-control">
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select id="role" name="role" class="form-control" required>
                    <option value="">Select Role</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Accounting">Accounting</option>
                    <option value="Worker">Worker</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Category (For Managers Only)</label>
                <input type="text" id="category" name="category" class="form-control" placeholder="Enter category (e.g., Phones)">
            </div>
            <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
        </form>

        <!-- User List -->
        <h2>Existing Users</h2>
        <table class="table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($user = $users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $user['user_id'] ?></td>
                        <td><?= $user['username'] ?></td>
                        <td><?= $user['role'] ?></td>
                        <td><?= $user['assigned_category'] ?? 'N/A' ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-info">Edit</a>
                            <a href="delete_user.php?user_id=<?= $user['user_id'] ?>" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
