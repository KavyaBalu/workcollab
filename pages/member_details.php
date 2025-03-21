<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Validate and sanitize input parameters
$user_id = isset($_GET['user_id']) ? filter_var($_GET['user_id'], FILTER_VALIDATE_INT) : 0;
if (!$user_id) {
    // header("Location: performance_analysis.php");
    // exit();
    echo "<script>window.location.href = 'performance_analysis.php';</script>";
}

$period = isset($_GET['period']) ? $_GET['period'] : '1month';
$valid_periods = ['1month', '3months', '6months', '1year'];
if (!in_array($period, $valid_periods)) {
    $period = '1month';
}

// Get time period
$current_date = date('Y-m-d');
switch($period) {
    case '3months':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case '6months':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1year':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-1 month'));
}

// Get member details with error handling
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

if (!$user_data) {
    // header("Location: performance_analysis.php");
    // exit();
    echo "<script>window.location.href = 'performance_analysis.php';</script>";
}

// Get detailed task history with status changes
$query = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.status,
        t.points,
        t.created_at,
        t.due_date,
        COALESCE(t.status, 'not_viewed') as current_status,
        GROUP_CONCAT(
            CONCAT(
                th.old_status, ':', 
                th.new_status, ':', 
                th.changed_at, ':', 
                COALESCE(th.notes, '')
            ) 
            ORDER BY th.changed_at ASC SEPARATOR '||'
        ) as status_history
    FROM tasks t
    LEFT JOIN task_status_history th ON t.task_id = th.task_id
    WHERE t.assigned_to = ?
    AND t.created_at BETWEEN ? AND ?
    GROUP BY t.task_id
    ORDER BY t.created_at DESC";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("iss", $user_id, $start_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$task_history = $result->fetch_all(MYSQLI_ASSOC);

// Calculate performance metrics
$total_tasks = count($task_history);
$verified_tasks = 0;
$total_points = 0;
$earned_points = 0;
$status_counts = array_fill_keys(['not_viewed', 'in_study', 'in_progress', 'completed', 'verified'], 0);
$progression_data = [];

foreach ($task_history as $task) {
    $total_points += $task['points'];
    if ($task['current_status'] === 'verified') {
        $verified_tasks++;
        $earned_points += $task['points'];
    }
    $status_counts[$task['current_status']]++;
    
    // Calculate progression data
    $date = date('Y-m-d', strtotime($task['created_at']));
    if (!isset($progression_data[$date])) {
        $progression_data[$date] = ['total' => 0, 'completed' => 0];
    }
    $progression_data[$date]['total']++;
    if ($task['current_status'] === 'verified') {
        $progression_data[$date]['completed']++;
    }
}

$completion_rate = $total_tasks > 0 ? ($verified_tasks / $total_tasks) * 100 : 0;
$points_rate = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;

// Status color mapping
$status_colors = [
    'not_viewed' => 'secondary',
    'in_study' => 'primary',
    'in_progress' => 'warning',
    'completed' => 'info',
    'verified' => 'success'
];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Performance Details - <?php echo htmlspecialchars($user_data['username']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .timeline {
            position: relative;
            padding: 20px 0;
        }
        .timeline-item {
            padding: 20px;
            border-left: 2px solid #dee2e6;
            position: relative;
            margin-left: 20px;
            margin-bottom: 20px;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -9px;
            top: 28px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background-color: #fff;
            border: 2px solid #007bff;
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status-history {
            margin-top: 10px;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .metric-card {
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="mb-0"><?php echo htmlspecialchars($user_data['username']); ?></h1>
                <p class="text-muted"><?php echo htmlspecialchars($user_data['email']); ?></p>
            </div>
            <div>
                <a href="performance_analysis.php?period=<?php echo urlencode($period); ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Overview
                </a>
            </div>
        </div>

        <!-- Performance Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card h-100 metric-card">
                    <div class="card-body">
                        <h5 class="card-title">Task Completion Rate</h5>
                        <p class="card-text display-4">
                            <?php echo number_format($completion_rate, 1); ?>%
                        </p>
                        <p class="text-muted">
                            <?php echo $verified_tasks; ?> of <?php echo $total_tasks; ?> tasks completed
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100 metric-card">
                    <div class="card-body">
                        <h5 class="card-title">Points Earned</h5>
                        <p class="card-text display-4">
                            <?php echo number_format($points_rate, 1); ?>%
                        </p>
                        <p class="text-muted">
                            <?php echo $earned_points; ?> of <?php echo $total_points; ?> points
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Task Status Distribution</h5>
                        <canvas id="statusDistribution"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Task Timeline -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Task Timeline</h5>
                <div class="timeline">
                    <?php foreach ($task_history as $task): ?>
                        <div class="timeline-item">
                            <h5><?php echo htmlspecialchars($task['task_title']); ?></h5>
                            <p class="text-muted mb-2">
                                Created: <?php echo date('M d, Y', strtotime($task['created_at'])); ?>
                                <?php if ($task['due_date']): ?>
                                    | Due: <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                <?php endif; ?>
                            </p>
                            <p><?php echo htmlspecialchars($task['task_description']); ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="status-badge bg-<?php echo $status_colors[$task['current_status']]; ?> text-white">
                                    Current Status: <?php echo ucfirst($task['current_status']); ?>
                                </span>
                                <span class="badge bg-primary">Points: <?php echo $task['points']; ?></span>
                            </div>

                            <?php if ($task['status_history']): ?>
                                <div class="status-history">
                                    <h6>Status History:</h6>
                                    <?php
                                    $history_items = explode('||', $task['status_history']);
                                    foreach ($history_items as $item) {
                                        list($old_status, $new_status, $changed_at, $notes) = array_pad(explode(':', $item), 4, '');
                                        if ($old_status && $new_status):
                                    ?>
                                        <div class="small mb-1">
                                            <span class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($changed_at)); ?>:
                                                <?php echo ucfirst($old_status); ?> → <?php echo ucfirst($new_status); ?>
                                                <?php if ($notes): ?>
                                                    <br>Note: <?php echo htmlspecialchars($notes); ?>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php
                                        endif;
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Status Distribution Chart
        new Chart(document.getElementById('statusDistribution'), {
            type: 'doughnut',
            data: {
                labels: ['Not Viewed', 'In Study', 'In Progress', 'Completed', 'Verified'],
                datasets: [{
                    data: [
                        <?php echo implode(',', array_values($status_counts)); ?>
                    ],
                    backgroundColor: [
                        '#6c757d',  // secondary
                        '#007bff',  // primary
                        '#ffc107',  // warning
                        '#17a2b8',  // info
                        '#28a745'   // success
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>