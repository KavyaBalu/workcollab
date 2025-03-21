<?php
require '../includes/db_connect.php';

$task_id = $_GET['task_id'] ?? 0;

// Fetch task details with related user information
$task_query = "
    SELECT 
        t.*,
        u_assigned.username as assigned_to_name,
        u_assignedby.username as assigned_by_name,
        teams.team_name
    FROM tasks t
    LEFT JOIN users u_assigned ON t.assigned_to = u_assigned.user_id
    LEFT JOIN users u_assignedby ON t.assigned_by = u_assignedby.user_id
    LEFT JOIN teams ON t.team_id = teams.team_id
    WHERE t.task_id = ?
";
$stmt = $conn->prepare($task_query);
$stmt->bind_param("i", $task_id);
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
    <title>View Task Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .task-details {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 4px;
            background-color: #007bff;
            color: white;
            display: inline-block;
        }
        .history-item {
            border-left: 3px solid #007bff;
            padding: 10px 20px;
            margin-bottom: 15px;
            background-color: #fff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Task Details</h1>
        
        <!-- Task Information -->
        <div class="task-details">
            <div class="row">
                <div class="col-md-8">
                    <h2><?= htmlspecialchars($task['task_title']) ?></h2>
                    <div class="description">
                        <h4>Description:</h4>
                        <p><?= nl2br(htmlspecialchars($task['task_description'])) ?></p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="status-badge">
                        Status: <?= str_replace('_', ' ', ucfirst($task['status'])) ?>
                    </div>
                </div>
            </div>
            
            <div class="row mt-4">
                <div class="col-md-6">
                    <p><strong>Assigned To:</strong> <?= htmlspecialchars($task['assigned_to_name']) ?></p>
                    <p><strong>Assigned By:</strong> <?= htmlspecialchars($task['assigned_by_name']) ?></p>
                    <p><strong>Team:</strong> <?= htmlspecialchars($task['team_name']) ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Points:</strong> <?= $task['points'] ?></p>
                    <p><strong>Due Date:</strong> <?= date('F j, Y', strtotime($task['due_date'])) ?></p>
                    <p><strong>Created At:</strong> <?= date('F j, Y g:i A', strtotime($task['created_at'])) ?></p>
                </div>
            </div>
        </div>

        <!-- Task History -->
        <h3>Task History</h3>
        <div class="history-timeline">
            <?php while ($history = $history_result->fetch_assoc()): ?>
                <div class="history-item">
                    <div class="timestamp">
                        <?= date('F j, Y g:i A', strtotime($history['changed_at'])) ?>
                    </div>
                    <div class="change-details">
                        <p>
                            <strong>Changed By:</strong> <?= htmlspecialchars($history['changed_by_name']) ?><br>
                            <strong>Status Change:</strong> 
                            <?= $history['old_status'] ? str_replace('_', ' ', ucfirst($history['old_status'])) : 'Initial Status' ?> 
                            → 
                            <?= str_replace('_', ' ', ucfirst($history['new_status'])) ?>
                        </p>
                        <?php if ($history['notes']): ?>
                            <p><strong>Notes:</strong> <?= nl2br(htmlspecialchars($history['notes'])) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Back Button -->
        <div class="mt-4 mb-4">
            <a href="javascript:history.back()" class="btn btn-primary">Back</a>
        </div>
    </div>
</body>
</html>