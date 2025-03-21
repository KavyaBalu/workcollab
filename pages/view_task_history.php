<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['task_id'] ?? 0;

// Verify user has access to this task
$access_query = "
    SELECT t.*, u_assigned.username as assigned_to_name, u_assignedby.username as assigned_by_name
    FROM tasks t
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.user_id
    LEFT JOIN users u_assignedby ON t.assigned_by = u_assignedby.user_id
    WHERE t.task_id = ? AND (t.assigned_to = ? OR t.assigned_by = ?)
";
$stmt = $conn->prepare($access_query);
$stmt->bind_param("iii", $task_id, $user_id, $user_id);
$stmt->execute();
$task_result = $stmt->get_result();
$task = $task_result->fetch_assoc();

if (!$task) {
    // header("Location: error.php");
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

// Fetch task history
$history_query = "
    SELECT 
        h.*,
        u.username as changed_by_name
    FROM task_status_history h
    LEFT JOIN users u ON h.changed_by = u.user_id
    WHERE h.task_id = ?
    ORDER BY h.changed_at DESC
";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $task_id);
$stmt->execute();
$history_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task History | Team Workspace</title>
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
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .back-button:hover {
            color: #3498db;
            background-color: rgba(255, 255, 255, 0.1);
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

        .task-card {
            border-left: 4px solid #3498db;
        }

        .timeline-card {
            position: relative;
            border-left: 2px solid #e9ecef;
            margin-left: 1rem;
            padding-left: 2rem;
        }

        .timeline-card::before {
            content: '';
            position: absolute;
            left: -0.5625rem;
            top: 1.5rem;
            width: 1rem;
            height: 1rem;
            border-radius: 50%;
            background-color: #3498db;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            padding: 1rem 1.25rem;
        }

        .status-change {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
            padding: 0.5rem 1rem;
            background-color: #f8f9fa;
            border-radius: 6px;
            font-weight: 500;
        }

        .status-arrow {
            color: #3498db;
            margin: 0 0.5rem;
        }

        .notes-section {
            background-color: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
            border-left: 3px solid #3498db;
        }

        .meta-info {
            color: #6c757d;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .points-badge {
            background-color: #3498db;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .current-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background-color: #e9ecef;
            border-radius: 50px;
            font-weight: 500;
        }

        .timeline {
            padding: 1rem 0;
        }

        @media (max-width: 768px) {
            .timeline-card {
                margin-left: 0.5rem;
                padding-left: 1.5rem;
            }

            .card-body {
                padding: 1rem;
            }

            .status-change {
                flex-direction: column;
                align-items: flex-start;
            }

            .status-arrow {
                transform: rotate(90deg);
                margin: 0.5rem 0;
            }
        }

        .old-status, .new-status {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            background-color: #e9ecef;
        }

        .timestamp {
            font-size: 0.875rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark fixed-top">
        <div class="container">
            <a href="<?= $_SESSION['role'] == 'TeamMember' ? 't_member_mytasks.php' : 'assign_tasks.php' ?>" 
               class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Tasks</span>
            </a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="h3 mb-0">Task History</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <!-- Task Details Card -->
        <div class="card task-card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0"><?= htmlspecialchars($task['task_title']) ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="meta-info">
                            <i class="far fa-user"></i>
                            <strong>Assigned To:</strong> 
                            <?= htmlspecialchars($task['assigned_to_name']) ?>
                        </div>
                        <div class="meta-info">
                            <i class="fas fa-user-plus"></i>
                            <strong>Assigned By:</strong> 
                            <?= htmlspecialchars($task['assigned_by_name']) ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="points-badge mb-2">
                            <i class="fas fa-star"></i>
                            <?= $task['points'] ?> Points
                        </div>
                        <div class="current-status">
                            <i class="fas fa-info-circle"></i>
                            <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- History Timeline -->
        <div class="timeline">
            <?php if ($history_result->num_rows === 0): ?>
                <div class="card">
                    <div class="card-body text-center text-muted">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p class="mb-0">No history records found for this task.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php while ($history = $history_result->fetch_assoc()): ?>
                    <div class="card timeline-card">
                        <div class="card-body">
                            <div class="meta-info">
                                <i class="far fa-clock"></i>
                                <?= date('F j, Y g:i A', strtotime($history['changed_at'])) ?>
                            </div>
                            
                            <div class="meta-info">
                                <i class="far fa-user"></i>
                                <strong>Changed By:</strong> 
                                <?= htmlspecialchars($history['changed_by_name']) ?>
                            </div>

                            <div class="status-change">
                                <span class="old-status">
                                    <?= $history['old_status'] ? str_replace('_', ' ', ucfirst($history['old_status'])) : 'Initial Status' ?>
                                </span>
                                <i class="fas fa-arrow-right status-arrow"></i>
                                <span class="new-status">
                                    <?= str_replace('_', ' ', ucfirst($history['new_status'])) ?>
                                </span>
                            </div>

                            <?php if ($history['notes']): ?>
                                <div class="notes-section">
                                    <div class="meta-info">
                                        <i class="far fa-comment-alt"></i>
                                        <strong>Notes:</strong>
                                    </div>
                                    <p class="mb-0"><?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>