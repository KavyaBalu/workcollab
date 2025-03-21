<?php
session_start();
require '../includes/db_connect.php';

// Access control
if ($_SESSION['role'] != 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$team_lead_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$task_id = isset($_GET['task_id']) ? (int)$_GET['task_id'] : 0;
$success_message = '';
$error_message = '';

// Fetch Task Details with points and project information
$task_query = "
    SELECT t.*, u.username AS assigned_to_name, p.project_id, p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.task_id = ? AND t.assigned_by = ?
";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("ii", $task_id, $team_lead_id);
$stmt->execute();
$task_result = $stmt->get_result();
$task = $task_result->fetch_assoc();

if (!$task) {
    echo "<script>window.location.href = 'error.php';</script>";
}

// Only allow editing of tasks that are 'not_viewed'
if ($task['status'] !== 'not_viewed') {
    echo "<script>window.location.href = 'assign_tasks.php';</script>";
}

// Fetch available projects for the team
$projects_query = "
    SELECT project_id, title 
    FROM projects 
    WHERE team_id = ? 
    AND (status != 'completed' AND status != 'verified' OR project_id = ?)
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("ii", $team_id, $task['project_id']);
$stmt->execute();
$projects_result = $stmt->get_result();

// Handle Task Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $task_title = trim($_POST['task_title']);
    $task_description = trim($_POST['task_description']);
    $assigned_to = (int)$_POST['assigned_to'];
    $due_date = $_POST['due_date'];
    $points = (int)$_POST['points'];
    $project_id = (int)$_POST['project_id'];
    
    // Validation
    $errors = [];
    if (empty($task_title)) {
        $errors[] = "Task title is required";
    }
    if (empty($task_description)) {
        $errors[] = "Task description is required";
    }
    if ($points < 1 || $points > 20) {
        $errors[] = "Points must be between 1 and 20";
    }
    if (empty($due_date)) {
        $errors[] = "Due date is required";
    } else {
        $due_date_obj = new DateTime($due_date);
        $current_date = new DateTime();
        if ($due_date_obj < $current_date) {
            $errors[] = "Due date cannot be in the past";
        }
    }

    if (empty($errors)) {
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Update task
            $update_query = "
                UPDATE tasks 
                SET task_title = ?, 
                    task_description = ?, 
                    assigned_to = ?, 
                    due_date = ?,
                    points = ?,
                    project_id = ?
                WHERE task_id = ? AND assigned_by = ?
            ";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssissiii", 
                $task_title, 
                $task_description, 
                $assigned_to, 
                $due_date,
                $points,
                $project_id,
                $task_id, 
                $team_lead_id
            );
            $stmt->execute();

            // Add history record
            $history_query = "
                INSERT INTO task_status_history 
                (task_id, old_status, new_status, changed_by, notes) 
                VALUES (?, 'not_viewed', 'not_viewed', ?, 'Task details updated')
            ";
            $hist_stmt = $conn->prepare($history_query);
            $hist_stmt->bind_param("ii", $task_id, $team_lead_id);
            $hist_stmt->execute();

            $conn->commit();
            // header("Location: assign_tasks.php?success=Task updated successfully");
            echo "<script>window.location.href = 'assign_tasks.php?success=Task updated successfully';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating task: " . $e->getMessage();
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch team members
$team_members_query = "
    SELECT u.user_id, u.username 
    FROM users u
    INNER JOIN team_members tm ON u.user_id = tm.user_id
    WHERE tm.team_id = ?
";
$stmt = $conn->prepare($team_members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$team_members_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task | Team Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #2c3e50;
        }

        .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: #3498db;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #3498db;
            border: none;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-secondary {
            background-color: #95a5a6;
            border: none;
            transition: background-color 0.3s;
        }

        .btn-secondary:hover {
            background-color: #7f8c8d;
        }

        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
        <div class="container">
            <a href="assign_tasks.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Tasks</span>
            </a>
            <span class="navbar-brand ms-3">Edit Task</span>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-white border-0 pt-4 ps-4">
                <h4 class="mb-0">Edit Task Details</h4>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3 needs-validation" novalidate>
                    <div class="col-md-6">
                        <label for="project_id" class="form-label">Project</label>
                        <select class="form-control" id="project_id" name="project_id" required>
                            <option value="">-- Select Project --</option>
                            <?php while ($project = $projects_result->fetch_assoc()): ?>
                                <option value="<?= $project['project_id'] ?>" 
                                    <?= $task['project_id'] == $project['project_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($project['title']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="task_title" class="form-label">Task Title</label>
                        <input type="text" class="form-control" id="task_title" name="task_title" 
                               value="<?= htmlspecialchars($task['task_title']) ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label for="points" class="form-label">Task Points (1-20)</label>
                        <input type="number" class="form-control" id="points" name="points" 
                               value="<?= htmlspecialchars($task['points']) ?>" min="1" max="20" required>
                    </div>

                    <div class="col-12">
                        <label for="task_description" class="form-label">Task Description</label>
                        <textarea class="form-control" id="task_description" name="task_description" 
                                  required rows="3"><?= htmlspecialchars($task['task_description']) ?></textarea>
                    </div>

                    <div class="col-md-6">
                        <label for="assigned_to" class="form-label">Assign To</label>
                        <select class="form-control" id="assigned_to" name="assigned_to" required>
                            <option value="">-- Select Team Member --</option>
                            <?php while ($member = $team_members_result->fetch_assoc()): ?>
                                <option value="<?= $member['user_id'] ?>" 
                                    <?= $task['assigned_to'] == $member['user_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($member['username']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="due_date" class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" 
                               value="<?= htmlspecialchars($task['due_date']) ?>" required>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                        <a href="assign_tasks.php" class="btn btn-secondary ms-2">
                            <i class="fas fa-times me-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize date input min value
    window.addEventListener('load', function() {
        const dateInput = document.getElementById('due_date');
        const today = new Date();
        today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
        dateInput.min = today.toISOString().split('T')[0];
    });

    // Form validation
    (function() {
        'use strict';
        const forms = document.querySelectorAll('.needs-validation');
        Array.from(forms).forEach(form => {
            form.addEventListener('submit', event => {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>