<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$team_lead_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$success_message = '';
$error_message = '';

// Fetch available projects for the team
$projects_query = "
    SELECT project_id, title 
    FROM projects 
    WHERE team_id = ? 
    AND status != 'completed' 
    AND status != 'verified'
    ORDER BY created_at DESC
";
$stmt = $conn->prepare($projects_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$projects_result = $stmt->get_result();

$has_projects = $projects_result->num_rows > 0;

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create_task') {
            $task_title = $_POST['task_title'];
            $task_description = $_POST['task_description'];
            $assigned_to = $_POST['assigned_to'];
            $due_date = $_POST['due_date'];
            $points = $_POST['points'];
            $project_id = $_POST['project_id'];

            $insert_query = "
                INSERT INTO tasks (task_title, task_description, assigned_to, assigned_by, due_date, team_id, project_id, points, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'not_viewed')
            ";
            $stmt = $conn->prepare($insert_query);
            if ($stmt) {
                $stmt->bind_param("ssiisiii", $task_title, $task_description, $assigned_to, $team_lead_id, $due_date, $team_id, $project_id, $points);
                if ($stmt->execute()) {
                    $task_id = $conn->insert_id;
                    // Record initial status
                    $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                    VALUES (?, NULL, 'not_viewed', ?, 'Task created')";
                    $hist_stmt = $conn->prepare($history_query);
                    $hist_stmt->bind_param("ii", $task_id, $team_lead_id);
                    $hist_stmt->execute();
                    $success_message = "Task assigned successfully!";
                } else {
                    $error_message = "Failed to assign task: " . $stmt->error;
                }
                $stmt->close();
            }
        } elseif ($_POST['action'] == 'verify_task') {
            // ... (keep existing verify task code) addeddddddd
            $task_id = $_POST['task_id'];
            $verify_query = "UPDATE tasks SET status = 'verified' WHERE task_id = ? AND assigned_by = ?";
            $stmt = $conn->prepare($verify_query);
            $stmt->bind_param("ii", $task_id, $team_lead_id);
            if ($stmt->execute()) {
                // Record status change
                $history_query = "INSERT INTO task_status_history (task_id, old_status, new_status, changed_by, notes) 
                                VALUES (?, 'completed', 'verified', ?, 'Task verified by team lead')";
                $hist_stmt = $conn->prepare($history_query);
                $hist_stmt->bind_param("ii", $task_id, $team_lead_id);
                $hist_stmt->execute();
                $success_message = "Task verified successfully!";
            }
        }
    }
}

// Modify tasks query to include project information
$tasks_query = "
    SELECT 
        t.task_id, 
        t.task_title, 
        t.due_date, 
        t.status,
        t.points,
        u.username AS assigned_to_name,
        p.title AS project_name
    FROM tasks t
    LEFT JOIN users u ON t.assigned_to = u.user_id
    LEFT JOIN projects p ON t.project_id = p.project_id
    WHERE t.assigned_by = ?
    ORDER BY t.created_at DESC
";
$stmt = $conn->prepare($tasks_query);
$stmt->bind_param("i", $team_lead_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

// Fetch team members (keep existing code)
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

// Keep existing getStatusColor function
function getStatusColor($status) {
    // ... (keep existing code) added
    switch ($status) {
        case 'not_viewed':
            return 'secondary';
        case 'in_progress':
            return 'primary';
        case 'completed':
            return 'success';
        case 'verified':
            return 'info';
        default:
            return 'secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Keep existing head content   addded -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Assignment | Team Management</title>
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

        .btn-success {
            background-color: #2ecc71;
            border: none;
        }

        .btn-warning {
            background-color: #f1c40f;
            border: none;
            color: #2c3e50;
        }

        .btn-info {
            background-color: #3498db;
            border: none;
            color: #ffffff;
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

        .table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
            padding: 1rem;
        }

        .table td {
            padding: 1rem;
            vertical-align: middle;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .alert {
            border: none;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
    </style>
    
</head>
<body>
    <!-- Keep existing navbar   addded -->
     <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
        <div class="container">
            <a href="teamlead_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand ms-3">Task Assignment</span>
        </div>
    </nav>
    
    <div class="container py-4">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!$has_projects): ?>
            <div class="alert alert-warning" role="alert">
                <h4 class="alert-heading">No Active Projects Available</h4>
                <p>There are currently no active projects assigned to your team. Tasks can only be created within projects.</p>
            </div>
        <?php else: ?>
            <!-- Task Assignment Card -->
            <div class="card">
                <div class="card-header bg-white border-0 pt-4 ps-4">
                    <h4 class="mb-0">Assign New Task</h4>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="action" value="create_task">
                        
                        <div class="col-md-6">
                            <label for="project_id" class="form-label">Project</label>
                            <select class="form-control" id="project_id" name="project_id" required>
                                <option value="">-- Select Project --</option>
                                <?php while ($project = $projects_result->fetch_assoc()): ?>
                                    <option value="<?= $project['project_id'] ?>"><?= htmlspecialchars($project['title']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <!-- Keep existing form fields -->
                        <div class="col-md-6">
                            <label for="task_title" class="form-label">Task Title</label>
                            <input type="text" class="form-control" id="task_title" name="task_title" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="points" class="form-label">Task Points (1-20)</label>
                            <input type="number" class="form-control" id="points" name="points" min="1" max="20" required>
                        </div>
                        
                        <div class="col-12">
                            <label for="task_description" class="form-label">Task Description</label>
                            <textarea class="form-control" id="task_description" name="task_description" rows="3" required></textarea>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="assigned_to" class="form-label">Assign To</label>
                            <select class="form-control" id="assigned_to" name="assigned_to" required>
                                <option value="">-- Select Team Member --</option>
                                <?php while ($member = $team_members_result->fetch_assoc()): ?>
                                    <option value="<?= $member['user_id'] ?>"><?= htmlspecialchars($member['username']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" required>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-tasks me-2"></i>Assign Task
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tasks List Card -->
        <div class="card">
            <div class="card-header bg-white border-0 pt-4 ps-4">
                <h4 class="mb-0">Assigned Tasks</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Task ID</th>
                                <th>Project</th>
                                <th>Title</th>
                                <th>Assigned To</th>
                                <th>Points</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($task = $tasks_result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= $task['task_id'] ?></td>
                                    <td><?= htmlspecialchars($task['project_name']) ?: 'No Project' ?></td>
                                    <td><?= htmlspecialchars($task['task_title']) ?></td>
                                    <td><?= htmlspecialchars($task['assigned_to_name']) ?: 'Unassigned' ?></td>
                                    <td><?= $task['points'] ?></td>
                                    <td>
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?= $task['due_date'] ?>
                                    </td>
                                    <td>
                                        <span class="status-badge bg-<?= getStatusColor($task['status']) ?> bg-opacity-10 text-<?= getStatusColor($task['status']) ?>">
                                            <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task['status'] == 'completed'): ?>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success me-2" 
                                                    onclick="verifyTask(<?= $task['task_id'] ?>)">
                                                <i class="fas fa-check me-1"></i>Verify
                                            </button>
                                        <?php endif; ?>
                                        
                                        <a href="view_task_history.php?task_id=<?= $task['task_id'] ?>" 
                                           class="btn btn-sm btn-info me-2">
                                            <i class="fas fa-eye me-1"></i>View
                                        </a>
                                        
                                        <?php if ($task['status'] == 'not_viewed'): ?>
                                            <a href="edit_task.php?task_id=<?= $task['task_id'] ?>" 
                                               class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Keep existing hidden form and scripts  added-->
        <!-- Verification Form (Hidden) -->
    <form id="verifyForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="verify_task">
        <input type="hidden" name="task_id" id="verify_task_id">
    </form>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Initialize date input min value
    window.addEventListener('load', function() {
        const dateInput = document.getElementById('due_date');
        const today = new Date();
        today.setMinutes(today.getMinutes() - today.getTimezoneOffset());
        dateInput.min = today.toISOString().split('T')[0];
    });

    function verifyTask(taskId) {
        if (confirm('Are you sure you want to verify this task?')) {
            document.getElementById('verify_task_id').value = taskId;
            document.getElementById('verifyForm').submit();
        }
    }
    </script>
</body>
</html>