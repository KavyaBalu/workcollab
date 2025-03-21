<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in and is a Manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    header("Location: login.php");
    exit();
}

// Function to truncate description
function truncateDescription($description, $length = 100) {
    return strlen($description) > $length 
        ? substr($description, 0, $length) . '...' 
        : $description;
}

// Fetch all projects with team and team lead details
$projectQuery = "
    SELECT 
        p.project_id, 
        p.title, 
        p.description, 
        p.status, 
        p.start_date, 
        p.due_date, 
        t.team_name, 
        u.username AS team_lead_name
    FROM 
        projects p
    JOIN 
        teams t ON p.team_id = t.team_id
    JOIN 
        users u ON t.team_lead_id = u.user_id
    ORDER BY 
        p.created_at DESC
";
$projectResult = $conn->query($projectQuery);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Projects View</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .project-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 12px;
        }
        .project-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }
        .back-btn {
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            transform: translateX(-5px);
        }
    </style>
</head>
<body>
    <div class="container-fluid px-4 py-4">
        <div class="mb-4">
            <a href="dashboard.php" class="btn btn-outline-secondary back-btn">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>

        <h2 class="mb-4">Project Inventory</h2>

        <div class="row g-4">
            <?php if ($projectResult->num_rows > 0): ?>
                <?php while($project = $projectResult->fetch_assoc()): ?>
                    <div class="col-md-4">
                        <div class="card project-card shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($project['title']); ?></h5>
                                    <?php 
                                    $statusClass = match($project['status']) {
                                        'completed' => 'success',
                                        'verified' => 'primary',
                                        'inprogress' => 'warning',
                                        'instudy' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?> status-badge">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                </div>
                                
                                <p class="card-text text-muted mb-3">
                                    <?php echo htmlspecialchars(truncateDescription($project['description'])); ?>
                                </p>
                                
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-6">
                                            <small class="text-muted d-block">Start Date</small>
                                            <strong><?php echo date('M d, Y', strtotime($project['start_date'])); ?></strong>
                                        </div>
                                        <div class="col-6">
                                            <small class="text-muted d-block">Due Date</small>
                                            <strong><?php echo date('M d, Y', strtotime($project['due_date'])); ?></strong>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-3">
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted d-block">Team</small>
                                        <strong><?php echo htmlspecialchars($project['team_name']); ?></strong>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">Team Lead</small>
                                        <strong><?php echo htmlspecialchars($project['team_lead_name']); ?></strong>
                                    </div>
                                </div>
                                
                                <div class="mt-3 text-center">
                                    <a href="project_details.php?id=<?php echo $project['project_id']; ?>" class="btn btn-outline-primary btn-sm">
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info text-center" role="alert">
                        No projects found.
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>