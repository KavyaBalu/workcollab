<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'TeamMember') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_status') {
    $task_id = $_POST['task_id'];
    $new_status = $_POST['new_status'];
    $notes = $_POST['notes'];

    // Get current status
    $curr_status_query = "SELECT status FROM tasks WHERE task_id = ? AND assigned_to = ?";
    $stmt = $conn->prepare($curr_status_query);
    $stmt->bind_param("ii", $task_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_task = $result->fetch_assoc();

    if ($current_task) {
        $old_status = $current_task['status'];
        
        // Validate status transition
        $valid_transition = false;
        switch ($old_status) {
            case 'not_viewed':
                $valid_transition = ($new_status == 'in_study');
                break;
            case 'in_study':
                $valid_transition = ($new_status == 'in_progress');
                break;
            case 'in_progress':
                $valid_transition = ($new_status == 'completed');
                break;
            default:
                $valid_transition = false;
        }

        if ($valid_transition) {
            // Update task status
            $update_query = "UPDATE tasks SET status = ? WHERE task_id = ? AND assigned_to = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("sii", $new_status, $task_id, $user_id);
            
            if ($stmt->execute()) {
                // Record status change in history
                $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                VALUES (?, ?, ?, ?, ?)";
                $hist_stmt = $conn->prepare($history_query);
                $hist_stmt->bind_param("issss", $task_id, $old_status, $new_status, $user_id, $notes);
                $hist_stmt->execute();
                $success_message = "Task status updated successfully!";
            } else {
                $error_message = "Failed to update task status.";
            }
        } else {
            $error_message = "Invalid status transition.";
        }
    }
}

// Fetch assigned tasks with project name
$tasks_query = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.due_date,
        t.status,
        t.points,
        u.username AS assigned_by_name,
        p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_by = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.assigned_to = ?
    ORDER BY 
        CASE 
            WHEN t.status = 'not_viewed' THEN 1
            WHEN t.status = 'in_study' THEN 2
            WHEN t.status = 'in_progress' THEN 3
            WHEN t.status = 'completed' THEN 4
            WHEN t.status = 'verified' THEN 5
        END,
        t.due_date ASC
";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$tasks_result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Tasks | Team Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            padding-top: 60px;
        }

        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .back-button:hover {
            color: #3498db;
        }

        .page-header {
            background: #ffffff;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-2px);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.25rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .not-viewed { background-color: #34495e; color: white; }
        .in-study { background-color: #f1c40f; color: #2c3e50; }
        .in-progress { background-color: #3498db; color: white; }
        .completed { background-color: #2ecc71; color: white; }
        .verified { background-color: #27ae60; color: white; }

        .points-badge {
            background-color: #3498db;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            color: white;
            font-weight: 500;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .btn-info {
            background-color: #34495e;
            border-color: #34495e;
            color: white;
        }

        .btn-info:hover {
            background-color: #2c3e50;
            border-color: #2c3e50;
            color: white;
        }

        .task-meta {
            color: #666;
            font-size: 0.9rem;
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .form-control {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.625rem;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark fixed-top">
        <div class="container">
            <a href="teammember_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="h3 mb-0">My Tasks</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= $success_message ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= $error_message ?>
            </div>
        <?php endif; ?>

        <!-- Task List -->
        <div class="task-list">
            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0"><?= htmlspecialchars($task['task_title']) ?></h5>
                        <span class="points-badge">
                            <i class="fas fa-star me-1"></i>
                            <?= $task['points'] ?> Points
                        </span>
                    </div>
                    <div class="card-body">
                        <p class="card-text"><?= nl2br(htmlspecialchars($task['task_description'])) ?></p>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="far fa-calendar me-2"></i>
                                    <strong>Due Date:</strong> <?= $task['due_date'] ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="far fa-user me-2"></i>
                                    <strong>Assigned By:</strong> <?= htmlspecialchars($task['assigned_by_name']) ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <i class="fas fa-project-diagram me-2"></i>
                                    <strong>Project:</strong> <?= htmlspecialchars($task['project_name'] ?? 'N/A') ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="task-meta">
                                    <strong>Status:</strong>
                                    <span class="status-badge <?= str_replace('_', '-', $task['status']) ?> ms-2">
                                        <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if ($task['status'] != 'verified'): ?>
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="task_id" value="<?= $task['task_id'] ?>">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="new_status_<?= $task['task_id'] ?>" class="form-label">Update Status</label>
                                        <select class="form-select" id="new_status_<?= $task['task_id'] ?>" name="new_status" required>
                                            <option value="">Select new status</option>
                                            <?php if ($task['status'] == 'not_viewed'): ?>
                                                <option value="in_study">In Study</option>
                                            <?php elseif ($task['status'] == 'in_study'): ?>
                                                <option value="in_progress">In Progress</option>
                                            <?php elseif ($task['status'] == 'in_progress'): ?>
                                                <option value="completed">Completed</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="notes_<?= $task['task_id'] ?>" class="form-label">Status Update Notes</label>
                                        <textarea class="form-control" id="notes_<?= $task['task_id'] ?>" name="notes" rows="2" required></textarea>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary mt-3">
                                    <i class="fas fa-check me-2"></i>Update Status
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="view_task_history.php?task_id=<?= $task['task_id'] ?>" class="btn btn-info">
                            <i class="fas fa-history me-2"></i>View History
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>