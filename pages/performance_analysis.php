<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get time period from filter
$period = isset($_GET['period']) ? $_GET['period'] : '1month';
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

// Updated query to correctly handle points and task statuses
$query = "
    SELECT 
        u.user_id,
        u.username,
        COUNT(DISTINCT t.task_id) as total_tasks,
        COALESCE(SUM(CASE WHEN t.status = 'verified' THEN t.points ELSE 0 END), 0) as verified_points,
        COALESCE(SUM(t.points), 0) as total_points,
        SUM(CASE WHEN t.status = 'verified' THEN 1 ELSE 0 END) as verified_tasks,
        SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
        SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
        SUM(CASE WHEN t.status = 'in_study' THEN 1 ELSE 0 END) as in_study_tasks,
        SUM(CASE WHEN t.status = 'not_viewed' THEN 1 ELSE 0 END) as not_viewed_tasks,
        COALESCE(AVG(CASE 
            WHEN t.status = 'verified' THEN 100
            WHEN t.status = 'completed' THEN 75
            WHEN t.status = 'in_progress' THEN 50
            WHEN t.status = 'in_study' THEN 25
            ELSE 0 
        END), 0) as avg_performance
    FROM users u 
    LEFT JOIN tasks t ON u.user_id = t.assigned_to 
        AND (t.created_at BETWEEN ? AND ? OR ? = DATE_SUB(CURRENT_DATE, INTERVAL 1 YEAR))
    WHERE u.role = 'TeamMember'
    GROUP BY u.user_id, u.username
    ORDER BY verified_points DESC, avg_performance DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("sss", $start_date, $current_date, $start_date);
$stmt->execute();
$result = $stmt->get_result();
$performance_data = $result->fetch_all(MYSQLI_ASSOC);

// Calculate totals for status chart
$total_verified = array_sum(array_column($performance_data, 'verified_tasks'));
$total_completed = array_sum(array_column($performance_data, 'completed_tasks'));
$total_in_progress = array_sum(array_column($performance_data, 'in_progress_tasks'));
$total_in_study = array_sum(array_column($performance_data, 'in_study_tasks'));
$total_not_viewed = array_sum(array_column($performance_data, 'not_viewed_tasks'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Performance Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .performance-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        .performance-card:hover {
            transform: translateY(-5px);
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
        }
        .filter-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0,0,0,.05);
        }
        .performance-score {
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
        }
        .score-high {
            background-color: rgba(40, 167, 69, 0.2);
            color: #28a745;
        }
        .score-medium {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
        }
        .score-low {
            background-color: rgba(220, 53, 69, 0.2);
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Team Performance Analysis</h1>
            <a href="hr_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="GET" class="row align-items-center">
                <div class="col-auto">
                    <label class="me-2">Time Period:</label>
                    <select name="period" class="form-select" onchange="this.form.submit()">
                        <option value="1month" <?php echo $period == '1month' ? 'selected' : ''; ?>>Last Month</option>
                        <option value="3months" <?php echo $period == '3months' ? 'selected' : ''; ?>>Last 3 Months</option>
                        <option value="6months" <?php echo $period == '6months' ? 'selected' : ''; ?>>Last 6 Months</option>
                        <option value="1year" <?php echo $period == '1year' ? 'selected' : ''; ?>>Last Year</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-primary" onclick="exportReport()">Export Report</button>
                </div>
            </form>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card performance-card">
                    <div class="card-body">
                        <h5 class="card-title">Total Team Members</h5>
                        <p class="metric-value"><?php echo count($performance_data); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card performance-card">
                    <div class="card-body">
                        <h5 class="card-title">Verified Tasks</h5>
                        <p class="metric-value"><?php echo $total_verified; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card performance-card">
                    <div class="card-body">
                        <h5 class="card-title">Tasks In Progress</h5>
                        <p class="metric-value"><?php echo $total_in_progress; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card performance-card">
                    <div class="card-body">
                        <h5 class="card-title">Tasks Under Study</h5>
                        <p class="metric-value"><?php echo $total_in_study; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card performance-card">
                    <div class="card-body">
                        <canvas id="performanceChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card performance-card">
                    <div class="card-body">
                        <canvas id="taskStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Performance Table -->
        <div class="card performance-card">
            <div class="card-body">
                <h5 class="card-title mb-3">Detailed Performance</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Team Member</th>
                                <th>Total Tasks</th>
                                <th>Verified Tasks</th>
                                <th>Points Earned</th>
                                <th>Total Points Available</th>
                                <th>Performance Score</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($performance_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($data['username']); ?></td>
                                <td><?php echo $data['total_tasks']; ?></td>
                                <td><?php echo $data['verified_tasks']; ?></td>
                                <td><?php echo $data['verified_points']; ?></td>
                                <td><?php echo $data['total_points']; ?></td>
                                <td>
                                    <?php 
                                    $score = number_format($data['avg_performance'], 1);
                                    $score_class = $score >= 75 ? 'score-high' : ($score >= 50 ? 'score-medium' : 'score-low');
                                    echo "<span class='performance-score $score_class'>$score%</span>";
                                    ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-info" onclick="viewDetails(<?php echo $data['user_id']; ?>)">
                                        View Details
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Performance Chart
        new Chart(
            document.getElementById('performanceChart'),
            {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode(array_column($performance_data, 'username')); ?>,
                    datasets: [{
                        label: 'Performance Score (%)',
                        data: <?php echo json_encode(array_map(function($item) { 
                            return round($item['avg_performance'], 1); 
                        }, $performance_data)); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Team Member Performance Scores'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            }
        );

        // Task Status Chart
        new Chart(
            document.getElementById('taskStatusChart'),
            {
                type: 'doughnut',
                data: {
                    labels: ['Verified', 'Completed', 'In Progress', 'In Study', 'Not Viewed'],
                    datasets: [{
                        data: [
                            <?php echo "$total_verified, $total_completed, $total_in_progress, $total_in_study, $total_not_viewed"; ?>
                        ],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.5)',  // green for verified
                            'rgba(75, 192, 192, 0.5)', // teal for completed
                            'rgba(54, 162, 235, 0.5)', // blue for in progress
                            'rgba(255, 193, 7, 0.5)',  // yellow for in study
                            'rgba(108, 117, 125, 0.5)' // gray for not viewed
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 193, 7, 1)',
                            'rgba(108, 117, 125, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Task Status Distribution'
                        },
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            }
        );

        function viewDetails(userId) {
            window.location.href = `member_details.php?user_id=${userId}&period=<?php echo $period; ?>`;
        }

        function exportReport() {
            // Get the data from the table
            const table = document.querySelector('table');
            const rows = Array.from(table.querySelectorAll('tr'));
            
            // Process header row - skip the Action column
            const headers = Array.from(rows[0].querySelectorAll('th'))
                .slice(0, -1) // Remove the Action column
                .map(header => escapeCsvField(header.textContent.trim()));
            
            // Process data rows
            const csvRows = rows.slice(1).map(row => {
                const cells = Array.from(row.querySelectorAll('td'))
                    .slice(0, -1) // Remove the Action column
                    .map(cell => {
                        // If the cell contains a span with performance score, get just the number
                        const scoreSpan = cell.querySelector('.performance-score');
                        const value = scoreSpan ? scoreSpan.textContent.replace('%', '') : cell.textContent;
                        return escapeCsvField(value.trim());
                    });
                return cells.join(',');
            });
            
            // Combine headers and rows
            const csv = [headers.join(','), ...csvRows].join('\n');
            
            // Create and trigger download
            const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            const fileName = `team_performance_report_${new Date().toISOString().split('T')[0]}.csv`;
            
            downloadLink.href = url;
            downloadLink.download = fileName;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            window.URL.revokeObjectURL(url);
        }

        // Helper function to escape CSV fields
        function escapeCsvField(field) {
            // If the field contains commas, quotes, or newlines, wrap it in quotes and escape existing quotes
            if (/[",\n]/.test(field)) {
                return `"${field.replace(/"/g, '""')}"`;
            }
            return field;
        }
    </script>
</body>
</html>