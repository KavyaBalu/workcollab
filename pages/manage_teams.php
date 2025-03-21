<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}

// Function to get team performance metrics
function getTeamPerformance($conn, $team_id) {
    // Get total projects
    $projectQuery = "SELECT COUNT(*) as total_projects, 
                            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects
                     FROM projects 
                     WHERE team_id = ?";
    $projectStmt = $conn->prepare($projectQuery);
    $projectStmt->bind_param("i", $team_id);
    $projectStmt->execute();
    $projectResult = $projectStmt->get_result()->fetch_assoc();

    // Get total tasks
    $taskQuery = "SELECT COUNT(*) as total_tasks, 
                         SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
                  FROM tasks 
                  WHERE team_id = ?";
    $taskStmt = $conn->prepare($taskQuery);
    $taskStmt->bind_param("i", $team_id);
    $taskStmt->execute();
    $taskResult = $taskStmt->get_result()->fetch_assoc();

    return [
        'total_projects' => $projectResult['total_projects'],
        'completed_projects' => $projectResult['completed_projects'],
        'total_tasks' => $taskResult['total_tasks'],
        'completed_tasks' => $taskResult['completed_tasks']
    ];
}

// Fetch teams with team lead details
$teamQuery = "
    SELECT 
        t.team_id, 
        t.team_name, 
        u.username AS team_lead_name,
        u.email AS team_lead_email,
        COUNT(tm.user_id) AS team_member_count
    FROM 
        teams t
    JOIN 
        users u ON t.team_lead_id = u.user_id
    LEFT JOIN 
        team_members tm ON t.team_id = tm.team_id
    GROUP BY 
        t.team_id, t.team_name, u.username, u.email
    ORDER BY 
        t.team_id
";
$teamResult = $conn->query($teamQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Overview</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --background: #f8f9fa;
            --accent: #3498db;
            --success: #2ecc71;
            --warning: #f1c40f;
            --danger: #e74c3c;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem;
        }

        .navbar-brand {
            font-weight: 600;
            font-size: 1.25rem;
        }

        .back-btn {
            background-color: var(--accent);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .back-btn:hover {
            background-color: #2980b9;
        }

        .team-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .team-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .performance-bar {
            height: 10px;
            border-radius: 5px;
        }

        .dashboard-header {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .dashboard-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
        }

        .dashboard-subtitle {
            color: var(--secondary);
            font-weight: 500;
            margin: 0.5rem 0 0;
        }

        .badge {
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-outline-primary {
            border-color: var(--accent);
            color: var(--accent);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent);
            color: white;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users me-2"></i>
                Manager Dashboard
            </a>
            <a href="manager_dashboard.php" class="back-btn ms-auto">
                <i class="fas fa-arrow-left me-2"></i>Back
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container" style="margin-top: 100px;">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Team Overview</h1>
            <p class="dashboard-subtitle">Monitor and manage team performance</p>
        </div>

        <!-- Team Cards Grid -->
        <div class="row g-4">
            <?php while($team = $teamResult->fetch_assoc()): 
                // Get team performance metrics
                $performance = getTeamPerformance($conn, $team['team_id']);
                
                // Calculate performance percentages
                $project_completion_rate = $performance['total_projects'] > 0 
                    ? round(($performance['completed_projects'] / $performance['total_projects']) * 100) 
                    : 0;
                $task_completion_rate = $performance['total_tasks'] > 0 
                    ? round(($performance['completed_tasks'] / $performance['total_tasks']) * 100) 
                    : 0;
            ?>
                <div class="col-md-4">
                    <div class="card team-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($team['team_name']); ?></h5>
                                <span class="badge bg-primary"><?php echo $team['team_member_count']; ?> Members</span>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted">Team Lead</small>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-tie me-2"></i>
                                    <strong><?php echo htmlspecialchars($team['team_lead_name']); ?></strong>
                                </div>
                                <small class="text-muted"><?php echo htmlspecialchars($team['team_lead_email']); ?></small>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Project Completion</small>
                                <div class="progress performance-bar" style="width: 100%">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $project_completion_rate; ?>%" 
                                         aria-valuenow="<?php echo $project_completion_rate; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $performance['completed_projects']; ?>/<?php echo $performance['total_projects']; ?> Projects
                                </small>
                            </div>
                            
                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Task Completion</small>
                                <div class="progress performance-bar" style="width: 100%">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $task_completion_rate; ?>%" 
                                         aria-valuenow="<?php echo $task_completion_rate; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    <?php echo $performance['completed_tasks']; ?>/<?php echo $performance['total_tasks']; ?> Tasks
                                </small>
                            </div>
                            
                            <div class="text-center">
                                <a href="team_project_details.php?team_id=<?php echo $team['team_id']; ?>" class="btn btn-outline-primary btn-sm">
                                    View Team Details
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>