<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'HR') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$success_message = '';
$error_message = '';

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = $_POST['team_name'];
    $team_lead_id = $_POST['team_lead_id'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert into teams table
        $query = "INSERT INTO teams (team_name, team_lead_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("si", $team_name, $team_lead_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to create team: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->commit();
        $success_message = "Team created successfully!";
    } catch (Exception $e) {
        $conn->rollback();
        $error_message = $e->getMessage();
    }
}

// Fetch available team leads
$team_leads_query = "
    SELECT u.user_id, u.username 
    FROM users u 
    LEFT JOIN teams t ON u.user_id = t.team_lead_id
    WHERE u.role = 'TeamLead' 
    AND t.team_id IS NULL";
$team_leads_result = $conn->query($team_leads_query);

// Fetch all teams with their team leads
$teams_query = "
    SELECT t.team_id, t.team_name, u.username AS team_lead 
    FROM teams t 
    JOIN users u ON t.team_lead_id = u.user_id";
$teams_result = $conn->query($teams_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Management</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #2c3e50;
        }
        .card {
            border: none;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .alert-success {
            background-color: #2ecc71;
            border-color: #27ae60;
            color: white;
        }
        .alert-danger {
            background-color: #e74c3c;
            border-color: #c0392b;
            color: white;
        }
        .table th {
            background-color: #34495e;
            color: white;
            font-weight: 500;
        }
        .table td {
            vertical-align: middle;
        }
        .badge {
            font-weight: 500;
            padding: 0.5em 1em;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users-cog me-2"></i>Team Management
            </a>
            <a href="hr_dashboard.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
            </a>
        </div>
    </nav>

    <div class="container">
        <?php if ($success_message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Create Team Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Create New Team
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($team_leads_result->num_rows > 0): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="team_name" class="form-label">Team Name</label>
                                    <input type="text" class="form-control" id="team_name" name="team_name" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="team_lead_id" class="form-label">Team Lead</label>
                                    <select class="form-select" id="team_lead_id" name="team_lead_id" required>
                                        <option value="">Select Team Lead</option>
                                        <?php while ($lead = $team_leads_result->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($lead['user_id']) ?>">
                                                <?= htmlspecialchars($lead['username']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Create Team
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                All team leads are currently assigned to teams.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Teams List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>Existing Teams
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Team Name</th>
                                        <th>Team Lead</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($team = $teams_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($team['team_id']) ?></td>
                                            <td><?= htmlspecialchars($team['team_name']) ?></td>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-user me-1"></i>
                                                    <?= htmlspecialchars($team['team_lead']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="add_members.php?team_id=<?= htmlspecialchars($team['team_id']) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="fas fa-users me-1"></i>
                                                    Manage Members
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>