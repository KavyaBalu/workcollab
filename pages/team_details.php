<?php
session_start();
require_once '../includes/db_connect.php';

// Check if user is logged in and is a Team Lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}



// Get team details based on the team lead's team
$team_lead_id = $_SESSION['user_id'];
$team_query = "SELECT t.team_id, t.team_name 
               FROM teams t 
               WHERE t.team_lead_id = $team_lead_id";
$team_result = mysqli_query($conn, $team_query);
$team = mysqli_fetch_assoc($team_result);

// Updated query to include all task statuses
$members_query = "SELECT 
    u.user_id, 
    u.username, 
    COUNT(DISTINCT t.task_id) as total_tasks,
    SUM(CASE WHEN t.status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
    SUM(CASE WHEN t.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
    SUM(CASE WHEN t.status = 'in_study' THEN 1 ELSE 0 END) as in_study_tasks,
    SUM(CASE WHEN t.status = 'not_viewed' THEN 1 ELSE 0 END) as not_viewed_tasks,
    SUM(CASE WHEN t.status = 'verified' THEN 1 ELSE 0 END) as verified_tasks,
    SUM(t.points) as total_points
FROM team_members tm
JOIN users u ON tm.user_id = u.user_id
LEFT JOIN tasks t ON t.assigned_to = u.user_id
WHERE tm.team_id = {$team['team_id']}
GROUP BY u.user_id, u.username";

$members_result = mysqli_query($conn, $members_query);

// Rerun the query to reset the pointer if needed
mysqli_data_seek($members_result, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details - <?php echo htmlspecialchars($team['team_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { 
            font-family: 'Inter', sans-serif; 
            background-color: #f8f9fa;
        }
        .team-card {
            background-color: #ffffff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .chart-container {
            position: relative;
            height: 250px;
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <div class="row">
            <div class="col-12">
                <a href="teamlead_dashboard.php" class="btn btn-secondary mb-3">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1 class="mb-4" style="color: #2c3e50;">
                    <i class="fas fa-users me-2"></i><?php echo htmlspecialchars($team['team_name']); ?> Team
                </h1>
            </div>
        </div>

        <div class="row">
            <?php 
            // Reset the result pointer
            mysqli_data_seek($members_result, 0);
            
            while($member = mysqli_fetch_assoc($members_result)) { 
            ?>
            <div class="col-md-4 mb-4">
                <div class="card team-card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($member['username']); ?>
                        </h5>
                        <div class="row">
                            <div class="col-6 chart-container">
                                <canvas id="taskChart<?php echo $member['user_id']; ?>" width="200" height="200"></canvas>
                            </div>
                            <div class="col-6 align-self-center">
                                <p class="mb-1">Total Tasks: <?php echo $member['total_tasks']; ?></p>
                                <p class="mb-1">Total Points: <?php echo $member['total_points']; ?></p>
                                <a href="user_task_details.php?user_id=<?php echo $member['user_id']; ?>" 
                                   class="btn btn-sm btn-primary mt-2">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                const ctx<?php echo $member['user_id']; ?> = document.getElementById('taskChart<?php echo $member['user_id']; ?>').getContext('2d');
                new Chart(ctx<?php echo $member['user_id']; ?>, {
                    type: 'pie',
                    data: {
                        labels: ['Completed', 'In Progress', 'In Study', 'Not Viewed', 'Verified'],
                        datasets: [{
                            data: [
                                <?php echo $member['completed_tasks']; ?>,
                                <?php echo $member['in_progress_tasks']; ?>,
                                <?php echo $member['in_study_tasks']; ?>,
                                <?php echo $member['not_viewed_tasks']; ?>,
                                <?php echo $member['verified_tasks']; ?>
                            ],
                            backgroundColor: [
                                '#2ecc71', // Success Green
                                '#3498db', // Accent Blue
                                '#f1c40f', // Warning Yellow
                                '#e74c3c', // Danger Red
                                '#9b59b6'  // Purple for Verified
                            ]
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        },
                        onClick: (event, activeElements) => {
                            if (activeElements.length > 0) {
                                const chartIndex = activeElements[0].index;
                                const statuses = ['completed', 'in_progress', 'in_study', 'not_viewed', 'verified'];
                                window.location.href = `user_task_details.php?user_id=<?php echo $member['user_id']; ?>&status=${statuses[chartIndex]}`;
                            }
                        }
                    }
                });
            </script>
            <?php } ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>