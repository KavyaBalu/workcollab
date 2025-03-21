<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['TeamMember', 'TeamLead'])) {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_project_file = isset($_POST['is_project_file']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];
    $team_id = $_SESSION['team_id'];

    $query = "INSERT INTO file_shares (title, description, team_id, user_id, username, is_project_file) 
              VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssiisi", $title, $description, $team_id, $user_id, $username, $is_project_file);

    if ($stmt->execute()) {
        // header("Location: file_sharing.php");
        // exit;
        echo "<script>window.location.href = 'file_sharing.php';</script>";
    } else {
        $error = "Failed to create file share. Please try again.";
    }
}

// Determine dashboard URL based on role
$dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start File Share | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #2c3e50;
            padding: 1rem 0;
        }

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .navbar .back-btn:hover {
            color: #3498db;
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
        }

        .form-container {
            max-width: 800px;
            margin: 2rem auto;
        }

        .form-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 2rem;
        }

        .form-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.75rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .checkbox-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .btn-submit {
            background-color: #3498db;
            border-color: #3498db;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .alert {
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background-color: #fde8e8;
            border-color: #f8b4b4;
            color: #c81e1e;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="file_sharing.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Files</span>
            </a>
            <span class="navbar-brand">Start File Share</span>
        </div>
    </nav>

    <div class="container form-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <h2 class="form-title">Create New File Share</h2>
            <form method="POST">
                <div class="mb-4">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control" id="title" name="title" required
                           placeholder="Enter file share title">
                </div>

                <div class="mb-4">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4" required
                              placeholder="Enter file share description"></textarea>
                </div>

                <div class="checkbox-wrapper">
                    <input type="checkbox" class="form-check-input" id="is_project_file" name="is_project_file">
                    <label class="form-check-label" for="is_project_file">
                        This is a project file
                    </label>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-plus me-2"></i>
                        Create File Share
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>