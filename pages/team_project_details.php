<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Validate team_id
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
if ($team_id <= 0) {
    // header("Location: teams_view.php");
    // exit();
    echo "<script>window.location.href = 'teams_view.php';</script>";

}

// Fetch team details
$teamQuery = "
    SELECT 
        t.team_id, 
        t.team_name, 
        u.username AS team_lead_name,
        u.email AS team_lead_email
    FROM 
        teams t
    JOIN 
        users u ON t.team_lead_id = u.user_id
    WHERE 
        t.team_id = ?
";
$teamStmt = $conn->prepare($teamQuery);
$teamStmt->bind_param("i", $team_id);
$teamStmt->execute();
$teamDetails = $teamStmt->get_result()->fetch_assoc();

// Fetch team members
$membersQuery = "
    SELECT 
        u.user_id,
        u.username,
        u.email,
        u.role
    FROM 
        team_members tm
    JOIN 
        users u ON tm.user_id = u.user_id
    WHERE 
        tm.team_id = ?
";
$membersStmt = $conn->prepare($membersQuery);
$membersStmt->bind_param("i", $team_id);
$membersStmt->execute();
$membersResult = $membersStmt->get_result();

// Fetch team projects
$projectsQuery = "
    SELECT 
        project_id,
        title,
        description,
        status,
        start_date,
        due_date
    FROM 
        projects
    WHERE 
        team_id = ?
    ORDER BY 
        created_at DESC
";
$projectsStmt = $conn->prepare($projectsQuery);
$projectsStmt->bind_param("i", $team_id);
$projectsStmt->execute();
$projectsResult = $projectsStmt->get_result();

// Fetch team tasks
$tasksQuery = "
    SELECT 
        t.task_id,
        t.task_title,
        t.task_description,
        t.status,
        t.due_date,
        u.username AS assigned_to
    FROM 
        tasks t
    LEFT JOIN 
        users u ON t.assigned_to = u.user_id
    WHERE 
        t.team_id = ?
    ORDER BY 
        t.created_at DESC
";
$tasksStmt = $conn->prepare($tasksQuery);
$tasksStmt->bind_param("i", $team_id);
$tasksStmt->execute();
$tasksResult = $tasksStmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Details</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f4f6f9;
        }
        .section-title {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .task-card {
            transition: transform 0.2s;
        }
        .task-card:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="mb-4">
            <a href="manage_teams.php" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-2"></i>Back to Teams
            </a>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <h1 class="mb-3"><?php echo htmlspecialchars($teamDetails['team_name']); ?> Team</h1>
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="card-title">Team Lead Details</h5>
                                <p>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($teamDetails['team_lead_name']); ?><br>
                                    <strong>Email:</strong> <?php echo htmlspecialchars($teamDetails['team_lead_email']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h5 class="card-title">Team Members</h5>
                                <ul class="list-unstyled">
                                    <?php 
                                    // Reset pointer for second iteration
                                    $membersResult->data_seek(0);
                                    while($member = $membersResult->fetch_assoc()): 
                                    ?>
                                        <li>
                                            <?php echo htmlspecialchars($member['username']); ?> 
                                            <small class="text-muted">(<?php echo htmlspecialchars($member['role']); ?>)</small>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <h3 class="section-title">Team Projects</h3>
                <?php if ($projectsResult->num_rows > 0): ?>
                    <div class="list-group">
                        <?php while($project = $projectsResult->fetch_assoc()): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($project['title']); ?></h5>
                                    <?php 
                                    $statusClass = match($project['status']) {
                                        'completed' => 'success',
                                        'verified' => 'primary',
                                        'inprogress' => 'warning',
                                        'instudy' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                                <p class="mb-1"><?php echo htmlspecialchars(substr($project['description'], 0, 100)); ?>...</p>
                                <small>
                                    <strong>Start:</strong> <?php echo date('M d, Y', strtotime($project['start_date'])); ?> | 
                                    <strong>Due:</strong> <?php echo date('M d, Y', strtotime($project['due_date'])); ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No projects found for this team.</div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <h3 class="section-title">Team Tasks</h3>
                <?php if ($tasksResult->num_rows > 0): ?>
                    <div class="row g-3">
                        <?php while($task = $tasksResult->fetch_assoc()): ?>
                            <div class="col-12">
                                <div class="card task-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h5 class="card-title mb-0"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                                            <?php 
                                            $statusClass = match($task['status']) {
                                                'completed' => 'success',
                                                'inprogress' => 'warning',
                                                'pending' => 'secondary',
                                                default => 'info'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo ucfirst($task['status']); ?>
                                            </span>
                                        </div>
                                        <p class="card-text text-muted mb-2">
                                            <?php echo htmlspecialchars(substr($task['task_description'], 0, 100)); ?>...
                                        </p>
                                        <div class="d-flex justify-content-between">
                                            <small class="text-muted">
                                                <strong>Assigned to:</strong> 
                                                <?php echo $task['assigned_to'] ? htmlspecialchars($task['assigned_to']) : 'Unassigned'; ?>
                                            </small>
                                            <small class="text-muted">
                                                <strong>Due:</strong> <?php echo date('M d, Y', strtotime($task['due_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No tasks found for this team.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>