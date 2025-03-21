<?php
session_start();
require '../includes/db_connect.php';

// Check if user is either Manager or HR
if ($_SESSION['role'] != 'Manager' && $_SESSION['role'] != 'HR') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$team_id = $_GET['team_id'] ?? null;
if (!$team_id) {
    // header("Location: create_team.php");
    echo "<script>window.location.href = 'create_team.php';</script>";
}

$success_message = '';
$error_message = '';

// Add member to the team
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_member'])) {
    $user_id = $_POST['user_id'];
    
    // Check if user is already in any team
    $check_query = "SELECT team_id FROM team_members WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    if ($check_stmt) {
        $check_stmt->bind_param("i", $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = "This team member is already assigned to another team!";
        } else {
            // Add member to the team
            $query = "INSERT INTO team_members (team_id, user_id) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $stmt->bind_param("ii", $team_id, $user_id);
                if ($stmt->execute()) {
                    $success_message = "Team member added successfully!";
                } else {
                    $error_message = "Failed to add team member.";
                }
                $stmt->close();
            }
        }
        $check_stmt->close();
    }
}

// Remove member from the team
if (isset($_GET['remove_member_id'])) {
    $member_id = $_GET['remove_member_id'];
    
    $query = "DELETE FROM team_members WHERE member_id = ? AND team_id = ?";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("ii", $member_id, $team_id);
        if ($stmt->execute()) {
            $success_message = "Team member removed successfully!";
        } else {
            $error_message = "Failed to remove team member.";
        }
        $stmt->close();
    }
}

// Fetch team details
$team_query = "SELECT team_name FROM teams WHERE team_id = ?";
$team_stmt = $conn->prepare($team_query);
$team_stmt->bind_param("i", $team_id);
$team_stmt->execute();
$team_result = $team_stmt->get_result();
$team_name = $team_result->fetch_assoc()['team_name'];
$team_stmt->close();

// Fetch current team members
$members_query = "
    SELECT tm.member_id, u.username, u.user_id 
    FROM team_members tm
    JOIN users u ON tm.user_id = u.user_id
    WHERE tm.team_id = ?";
$stmt = $conn->prepare($members_query);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$members_result = $stmt->get_result();

// Fetch available team members
$users_query = "
    SELECT u.user_id, u.username 
    FROM users u
    LEFT JOIN team_members tm ON u.user_id = tm.user_id
    WHERE u.role = 'TeamMember' 
    AND tm.team_id IS NULL";
$users_result = $conn->query($users_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Team Members</title>
    
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
        .btn-danger {
            background-color: #e74c3c;
            border-color: #c0392b;
        }
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
        .table th {
            background-color: #34495e;
            color: white;
            font-weight: 500;
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
                <i class="fas fa-users me-2"></i>Team Members
            </a>
            <a href="create_team.php" class="btn btn-outline-light">
                <i class="fas fa-arrow-left me-2"></i>Back
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
            <!-- Add Member Form -->
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-plus me-2"></i>Add Team Member
                        </h5>
                    </div>
                    <div class="card-body">
                        <h6 class="text-muted mb-3">Team: <?= htmlspecialchars($team_name) ?></h6>
                        <?php if ($users_result->num_rows > 0): ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <select class="form-select" id="user_id" name="user_id" required>
                                        <option value="">Select a Member</option>
                                        <?php while ($user = $users_result->fetch_assoc()): ?>
                                            <option value="<?= htmlspecialchars($user['user_id']) ?>">
                                                <?= htmlspecialchars($user['username']) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <button type="submit" name="add_member" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-2"></i>Add Member
                                </button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No available team members to add.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Team Members List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>Current Team Members
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($members_result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Username</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($member = $members_result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($member['member_id']) ?></td>
                                                <td>
                                                    <i class="fas fa-user me-2"></i>
                                                    <?= htmlspecialchars($member['username']) ?>
                                                </td>
                                                <td class="text-end">
                                                    <button class="btn btn-danger btn-sm" 
                                                            onclick="confirmRemove(<?= $member['member_id'] ?>)">
                                                        <i class="fas fa-user-minus me-1"></i>
                                                        Remove
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                No members in this team yet.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function confirmRemove(memberId) {
            if (confirm('Are you sure you want to remove this member?')) {
                window.location.href = `add_members.php?team_id=<?= $team_id ?>&remove_member_id=${memberId}`;
            }
        }
    </script>
</body>
</html>