<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    header("Location: login.php");
    exit();
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$period = isset($_GET['period']) ? $_GET['period'] : '1month';

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

// Get member details
$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_data = $result->fetch_assoc();

// Get detailed task history
$query = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.status,
        t.points,
        t.created_at,
        t.due_date,
        th.old_status,
        th.new_status,
        th.changed_at,
        th.notes
    FROM tasks t
    LEFT JOIN task_status_history th ON t.task_id = th.task_id
    WHERE t.assigned_to = ?
    AND t.created_at BETWEEN ? AND ?
    ORDER BY t.created_at DESC, th.changed_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("iss", $user_id, $start_date, $current_date);
$stmt->execute();
$result = $stmt->get_result();
$task_history = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Performance Details</title>
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
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Performance Details: <?php echo htmlspecialchars($user_data['username']); ?></h1>
            <a href="performance_analysis.php?period=<?php echo $period; ?>" class="btn btn-secondary">Back to Overview</a>
        </div>

        <!-- Task Status Timeline -->
        <div class="timeline mb-4">
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
                        <span class="status-badge <?php 
                            switch($task['status']) {
                                case 'verified':
                                    echo 'bg-success text-white';
                                    break;
                                case 'completed':
                                    echo 'bg-info text-white';
                                    break;
                                case 'in_progress':
                                    echo 'bg-warning';
                                    break;
                                case 'in_study':
                                    echo 'bg-primary text-white';
                                    break;
                                default:
                                    echo 'bg-secondary text-white';
                            }
                        ?>">
                            Status: <?php echo ucfirst($task['status']); ?>
                        </span>
                        <span class="badge bg-primary">Points: <?php echo $task['points']; ?></span>
                    </div>

                    <?php if ($task['notes']): ?>
                    <div class="mt-2">
                        <small class="text-muted">Notes: <?php echo htmlspecialchars($task['notes']); ?></small>
                    </div>
                    <?php endif; ?>

                    <?php if ($task['old_status'] && $task['new_status']): ?>
                    <div class="mt-2 small">
                        <span class="text-muted">
                            Status changed from <?php echo ucfirst($task['old_status']); ?> 
                            to <?php echo ucfirst($task['new_status']); ?>
                            on <?php echo date('M d, Y H:i', strtotime($task['changed_at'])); ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Performance Metrics -->
        <?php
        // Calculate performance metrics
        $total_tasks = count($task_history);
        $verified_tasks = 0;
        $total_points = 0;
        $earned_points = 0;
        $status_counts = array_fill_keys(['not_viewed', 'in_study', 'in_progress', 'completed', 'verified'], 0);
        
        foreach ($task_history as $task) {
            $total_points += $task['points'];
            if ($task['status'] === 'verified') {
                $verified_tasks++;
                $earned_points += $task['points'];
            }
            $status_counts[$task['status']]++;
        }
        
        $completion_rate = $total_tasks > 0 ? ($verified_tasks / $total_tasks) * 100 : 0;
        $points_rate = $total_points > 0 ? ($earned_points / $total_points) * 100 : 0;
        ?>

        <div class="row mb-4">
            <!-- Performance Summary Cards -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Task Completion Rate</h5>
                        <p class="card-text display-4"><?php echo number_format($completion_rate, 1); ?>%</p>
                        <p class="text-muted"><?php echo $verified_tasks; ?> of <?php echo $total_tasks; ?> tasks completed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title">Points Earned</h5>
                        <p class="card-text display-4"><?php echo number_format($points_rate, 1); ?>%</p>
                        <p class="text-muted"><?php echo $earned_points; ?> of <?php echo $total_points; ?> points</p>
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

        <!-- Task Progression Chart -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Task Progress Over Time</h5>
                <canvas id="progressionChart"></canvas>
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
                        '#6c757d',
                        '#007bff',
                        '#ffc107',
                        '#17a2b8',
                        '#28a745'
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

        // Task Progression Chart
        const progressionData = <?php 
            $progression = [];
            foreach ($task_history as $task) {
                $date = date('Y-m-d', strtotime($task['created_at']));
                if (!isset($progression[$date])) {
                    $progression[$date] = ['total' => 0, 'completed' => 0];
                }
                $progression[$date]['total']++;
                if ($task['status'] === 'verified') {
                    $progression[$date]['completed']++;
                }
            }
            echo json_encode($progression);
        ?>;

        new Chart(document.getElementById('progressionChart'), {
            type: 'line',
            data: {
                labels: Object.keys(progressionData),
                datasets: [{
                    label: 'Total Tasks',
                    data: Object.values(progressionData).map(d => d.total),
                    borderColor: '#007bff',
                    fill: false
                }, {
                    label: 'Completed Tasks',
                    data: Object.values(progressionData).map(d => d.completed),
                    borderColor: '#28a745',
                    fill: false
                }]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'day'
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>