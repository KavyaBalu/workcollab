<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a Team Lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    // header("Location: login.php");
    echo "<script>window.location.href = 'error.php';</script>";
    exit();
}



$user_id = intval($_GET['user_id']);
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get user details
$user_query = "SELECT username FROM users WHERE user_id = $user_id";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Get tasks for the user with optional status filter
$tasks_query = "SELECT 
    task_id, 
    task_title, 
    task_description, 
    status, 
    points, 
    due_date 
FROM tasks 
WHERE assigned_to = $user_id 
" . (!empty($status) ? " AND status = '$status'" : "") . "
ORDER BY due_date";

$tasks_result = mysqli_query($conn, $tasks_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks for <?php echo htmlspecialchars($user['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8f9fa;
        }
        .task-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .task-card:hover {
            transform: scale(1.02);
        }
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <div class="row">
            <div class="col-12">
                <a href="team_details.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Back to Team
                </a>
                <h1 class="mb-4" style="color: #2c3e50;">
                    <i class="fas fa-tasks me-2"></i>Tasks for <?php echo htmlspecialchars($user['username']); ?>
                </h1>
            </div>
        </div>

        <?php if (mysqli_num_rows($tasks_result) == 0): ?>
            <div class="alert alert-info" role="alert">
                No tasks found for this user.
            </div>
        <?php else: ?>
            <div class="row">
                <?php while($task = mysqli_fetch_assoc($tasks_result)) { ?>
                <div class="col-md-4 mb-4">
                    <div class="card task-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title m-0">
                                    <?php echo htmlspecialchars($task['task_title']); ?>
                                </h5>
                                <span class="badge status-badge 
                                    <?php 
                                    switch($task['status']) {
                                        case 'completed': echo 'bg-success'; break;
                                        case 'in_progress': echo 'bg-primary'; break;
                                        case 'in_study': echo 'bg-warning'; break;
                                        case 'not_viewed': echo 'bg-danger'; break;
                                    }
                                    ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $task['status'])); ?>
                                </span>
                            </div>
                            <p class="card-text text-muted mb-2">
                                <?php echo htmlspecialchars($task['task_description']); ?>
                            </p>
                            <div class="d-flex justify-content-between">
                                <span>
                                    <i class="fas fa-calendar me-2"></i>
                                    <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                </span>
                                <span class="badge bg-info">
                                    <?php echo $task['points']; ?> Points
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>