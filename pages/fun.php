<?php
session_start();
require '../includes/db_connect.php';

// Check if user is HR
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'HR') {
    header('Location: login.php');
    exit();
}

// Get time interval from filter (default 1 month)
$interval = isset($_GET['interval']) ? $_GET['interval'] : '1m';

// Calculate date range based on interval
$end_date = date('Y-m-d');
switch($interval) {
    case '3m':
        $start_date = date('Y-m-d', strtotime('-3 months'));
        break;
    case '6m':
        $start_date = date('Y-m-d', strtotime('-6 months'));
        break;
    case '1y':
        $start_date = date('Y-m-d', strtotime('-1 year'));
        break;
    default: // 1m
        $start_date = date('Y-m-d', strtotime('-1 month'));
}

// Get team performance data with COALESCE to handle NULL values
$team_query = "
    SELECT 
        t.team_id,
        t.team_name,
        u.username as team_lead,
        COUNT(p.project_id) as total_projects,
        COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.points ELSE 0 END), 0) as total_points,
        COALESCE(SUM(CASE WHEN p.status = 'verified' THEN 1 ELSE 0 END), 0) as verified_projects,
        COALESCE(AVG(CASE 
            WHEN p.status = 'verified' AND p.actual_end_date IS NOT NULL 
            THEN DATEDIFF(p.actual_end_date, p.start_date) 
            ELSE NULL 
        END), 0) as avg_completion_days,
        COALESCE(COUNT(DISTINCT pe.extension_id), 0) as extension_requests,
        COALESCE(AVG(CASE WHEN p.status = 'verified' 
            THEN p.points ELSE NULL END), 0) as avg_points_per_project
    FROM teams t
    LEFT JOIN users u ON t.team_lead_id = u.user_id
    LEFT JOIN projects p ON t.team_id = p.team_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id
    WHERE p.created_at BETWEEN ? AND ?
    GROUP BY t.team_id, t.team_name, u.username
    ORDER BY total_points DESC";

$stmt = $conn->prepare($team_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$team_result = $stmt->get_result();
$teams_data = $team_result->fetch_all(MYSQLI_ASSOC);

// Get project status distribution
$status_query = "
    SELECT 
        t.team_name,
        p.status,
        COUNT(*) as count
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    WHERE p.created_at BETWEEN ? AND ?
    GROUP BY t.team_name, p.status";

$stmt = $conn->prepare($status_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$status_result = $stmt->get_result();
$status_data = $status_result->fetch_all(MYSQLI_ASSOC);

// Get timeline data for completion trends
$timeline_query = "
    SELECT 
        DATE_FORMAT(p.actual_end_date, '%Y-%m') as month,
        COUNT(*) as completed_projects,
        AVG(p.points) as avg_points
    FROM projects p
    WHERE p.status = 'verified' 
    AND p.actual_end_date BETWEEN ? AND ?
    GROUP BY month
    ORDER BY month";

$stmt = $conn->prepare($timeline_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$timeline_result = $stmt->get_result();
$timeline_data = $timeline_result->fetch_all(MYSQLI_ASSOC);

// Prepare data for CSV export
$export_data = array();
foreach ($teams_data as $team) {
    $export_data[] = array(
        'Team Name' => $team['team_name'],
        'Team Lead' => $team['team_lead'],
        'Total Projects' => $team['total_projects'],
        'Verified Projects' => $team['verified_projects'],
        'Total Points' => $team['total_points'],
        'Avg Points per Project' => round($team['avg_points_per_project'], 1),
        'Avg Completion Days' => round($team['avg_completion_days'], 1),
        'Extension Requests' => $team['extension_requests']
    );
}

// Handle CSV export
if (isset($_POST['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="team_performance_report.csv"');
    $output = fopen('php://output', 'w');
    
    fputcsv($output, array_keys($export_data[0]));
    foreach ($export_data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Performance Analysis</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .performance-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .performance-card:hover {
            transform: translateY(-5px);
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin: 20px 0;
        }
        .metric-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            padding: 5px 10px;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-md-8">
                <h2>Project Performance Analysis</h2>
                <p class="text-muted">Data from <?php echo date('M d, Y', strtotime($start_date)); ?> to <?php echo date('M d, Y', strtotime($end_date)); ?></p>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <a href="?interval=1m" class="btn btn-outline-primary <?php echo $interval === '1m' ? 'active' : ''; ?>">1 Month</a>
                    <a href="?interval=3m" class="btn btn-outline-primary <?php echo $interval === '3m' ? 'active' : ''; ?>">3 Months</a>
                    <a href="?interval=6m" class="btn btn-outline-primary <?php echo $interval === '6m' ? 'active' : ''; ?>">6 Months</a>
                    <a href="?interval=1y" class="btn btn-outline-primary <?php echo $interval === '1y' ? 'active' : ''; ?>">1 Year</a>
                </div>
                <form method="POST" class="d-inline-block ms-2">
                    <button type="submit" name="export" class="btn btn-success">
                        <i class="bi bi-download"></i> Export Report
                    </button>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <?php foreach ($teams_data as $team): ?>
            <div class="col-md-4 mb-3">
                <div class="card performance-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php echo htmlspecialchars($team['team_name']); ?>
                            <?php if ($team['total_points'] > 0): ?>
                                <span class="badge bg-success metric-badge"><?php echo $team['total_points']; ?> pts</span>
                            <?php endif; ?>
                        </h5>
                        <h6 class="card-subtitle mb-2 text-muted">Lead: <?php echo htmlspecialchars($team['team_lead']); ?></h6>
                        <div class="row mt-3">
                            <div class="col-6">
                                <p class="mb-1">Total Projects</p>
                                <h4><?php echo $team['total_projects']; ?></h4>
                            </div>
                            <div class="col-6">
                                <p class="mb-1">Avg Days/Project</p>
                                <h4><?php echo round($team['avg_completion_days']); ?></h4>
                            </div>
                        </div>
                        <div class="progress mt-3">
                            <?php 
                            $completion_rate = $team['total_projects'] > 0 
                                ? ($team['verified_projects'] / $team['total_projects'] * 100) 
                                : 0;
                            ?>
                            <div class="progress-bar" role="progressbar" 
                                style="width: <?php echo $completion_rate; ?>%">
                                <?php echo round($completion_rate); ?>% Completed
                            </div>
                        </div>
                        <div class="mt-2 text-muted">
                            <small>Extension Requests: <?php echo $team['extension_requests']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Team Performance Comparison</h5>
                        <div class="chart-container">
                            <canvas id="performanceChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Project Status Distribution</h5>
                        <div class="chart-container">
                            <canvas id="statusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline Chart -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Project Completion Trends</h5>
                        <div class="chart-container">
                            <canvas id="timelineChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Prepare data for charts
        const teamsData = <?php echo json_encode($teams_data); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        const timelineData = <?php echo json_encode($timeline_data); ?>;

        // Performance Chart
        const performanceCtx = document.getElementById('performanceChart').getContext('2d');
        new Chart(performanceCtx, {
            type: 'bar',
            data: {
                labels: teamsData.map(team => team.team_name),
                datasets: [
                    {
                        label: 'Total Points',
                        data: teamsData.map(team => team.total_points),
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Avg Completion Days',
                        data: teamsData.map(team => team.avg_completion_days),
                        backgroundColor: 'rgba(255, 99, 132, 0.5)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Total Points'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average Days to Complete'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                }
            }
        });

        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        const statusLabels = ['notviewed', 'instudy', 'inprogress', 'completed', 'verified'];
        const statusColors = [
            'rgba(255, 99, 132, 0.5)',
            'rgba(255, 159, 64, 0.5)',
            'rgba(255, 205, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)',
            'rgba(54, 162, 235, 0.5)'
        ];

        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels.map(label => label.replace(/([A-Z])/g, ' $1').replace(/^./, str => str.toUpperCase())),
                datasets: [{
                    data: statusLabels.map(status => 
                        statusData.reduce((sum, item) => 
                            sum + (item.status === status ? parseInt(item.count) : 0), 0)
                    ),
                    backgroundColor: statusColors,
                    borderColor: statusColors.map(color => color.replace('0.5', '1')),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right'
                    }
                }
            }
        });
        labels: timelineData.map(item => {
                    const date = new Date(item.month + '-01');
                    return date.toLocaleDateString('default', { month: 'short', year: 'numeric' });
                }),
                datasets: [
                    {
                        label: 'Completed Projects',
                        data: timelineData.map(item => item.completed_projects),
                        borderColor: 'rgba(75, 192, 192, 1)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        yAxisID: 'y',
                        fill: true
                    },
                    {
                        label: 'Average Points',
                        data: timelineData.map(item => item.avg_points),
                        borderColor: 'rgba(153, 102, 255, 1)',
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        yAxisID: 'y1',
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Month'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: true,
                            text: 'Completed Projects'
                        }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: true,
                            text: 'Average Points'
                        },
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y.toFixed(1);
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>