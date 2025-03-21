<?php
session_start();
require '../includes/db_connect.php';


// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}

$message = '';
$message_type = '';

// Fetch all teams
$teams_query = "SELECT team_id, team_name FROM teams";
$teams_result = $conn->query($teams_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $team_id = (int)$_POST['team_id'];
    $start_date = $_POST['start_date'];
    $due_date = $_POST['due_date'];
    $points = (int)$_POST['points'];
    
    $current_date = date('Y-m-d');
    
    // Validate points
    if ($points < 1 || $points > 100) {
        $message = "Points must be between 1 and 100!";
        $message_type = "danger";
    }
    // Validate start date
    else if (strtotime($start_date) < strtotime($current_date)) {
        $message = "Start date cannot be before current date!";
        $message_type = "danger";
    }
    // Validate dates
    else if (strtotime($start_date) > strtotime($due_date)) {
        $message = "Due date must be after start date!";
        $message_type = "danger";
    } else {
        $stmt = $conn->prepare("INSERT INTO projects (title, description, team_id, start_date, due_date, created_by, points) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissii", $title, $description, $team_id, $start_date, $due_date, $_SESSION['user_id'], $points);
        
        if ($stmt->execute()) {
            $message = "Project assigned successfully!";
            $message_type = "success";
        } else {
            $message = "Error assigning project: " . $conn->error;
            $message_type = "danger";
        }
        $stmt->close();
    }
}

function sanitizeInput($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Project | Team Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            padding-top: 60px;
        }

        .navbar {
            background-color: #2c3e50;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .back-button:hover {
            color: #3498db;
        }

        .page-header {
            background: #ffffff;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.2s;
        }

        .form-label {
            font-weight: 500;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #dee2e6;
            padding: 0.625rem;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            padding: 0.625rem 1.25rem;
            font-weight: 500;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            transform: translateY(-1px);
        }

        .alert {
            border-radius: 8px;
            border: none;
        }

        .view-projects-btn {
            background-color: #34495e;
            border-color: #34495e;
            color: white;
        }

        .view-projects-btn:hover {
            background-color: #2c3e50;
            border-color: #2c3e50;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-dark fixed-top">
        <div class="container">
            <a href="manager_dashboard.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1 class="h3 mb-0">Assign New Project</h1>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-body p-4">
                        <?php if ($message): ?>
                            <div class="alert alert-<?php echo $message_type; ?>" role="alert">
                                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                                <?php echo $message; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="projectForm" novalidate>
                            <div class="mb-3">
                                <label for="title" class="form-label">Project Title</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="points" class="form-label">Points (1-100)</label>
                                <input type="number" class="form-control" id="points" name="points" min="1" max="100" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="team_id" class="form-label">Assign to Team</label>
                                <select class="form-select" id="team_id" name="team_id" required>
                                    <option value="">Select a team</option>
                                    <?php while ($team = $teams_result->fetch_assoc()): ?>
                                        <option value="<?php echo $team['team_id']; ?>">
                                            <?php echo htmlspecialchars($team['team_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="due_date" class="form-label">Due Date</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                                </div>
                            </div>
                            
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Assign Project
                                </button>
                                <a href="manage_projects.php" class="btn view-projects-btn">
                                    <i class="fas fa-list me-2"></i>View All Projects
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date for start date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('start_date').setAttribute('min', today);

        // Update due date minimum when start date changes
        document.getElementById('start_date').addEventListener('change', function() {
            document.getElementById('due_date').setAttribute('min', this.value);
            // Clear due date if it's before start date
            if (document.getElementById('due_date').value < this.value) {
                document.getElementById('due_date').value = '';
            }
        });

        // Form validation
        document.getElementById('projectForm').addEventListener('submit', function(event) {
            let isValid = true;
            const startDate = document.getElementById('start_date').value;
            const dueDate = document.getElementById('due_date').value;

            if (startDate < today) {
                alert('Start date cannot be before today');
                isValid = false;
            }

            if (dueDate < startDate) {
                alert('Due date must be after start date');
                isValid = false;
            }

            if (!isValid) {
                event.preventDefault();
            }
        });
    </script>
</body>
</html>