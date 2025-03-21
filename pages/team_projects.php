<?php
session_start();
require '../includes/db_connect.php';


// Check if user is logged in and is a team lead
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

$message = '';
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$filter_start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$filter_end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $project_id = (int)$_POST['project_id'];
    $new_status = sanitizeInput($_POST['status']);
    
    $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE project_id = ? AND team_id = (SELECT team_id FROM teams WHERE team_lead_id = ?)");
    $stmt->bind_param("sii", $new_status, $project_id, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $message = "Status updated successfully!";
    } else {
        $message = "Error updating status: " . $conn->error;
    }
    $stmt->close();
}

// Handle extension requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_extension'])) {
    $project_id = (int)$_POST['project_id'];
    $reason = sanitizeInput($_POST['reason']);
    $new_due_date = $_POST['new_due_date'];
    
    $stmt = $conn->prepare("INSERT INTO project_extensions (project_id, requested_by, requested_date, reason, new_due_date) VALUES (?, ?, CURDATE(), ?, ?)");
    $stmt->bind_param("iiss", $project_id, $_SESSION['user_id'], $reason, $new_due_date);
    
    if ($stmt->execute()) {
        $message = "Extension request submitted successfully!";
    } else {
        $message = "Error submitting extension request: " . $conn->error;
    }
    $stmt->close();
}

// Build the query with filters
$query = "
    SELECT p.*, t.team_name,
    CASE 
        WHEN pe.status = 'pending' THEN 'Extension Pending'
        WHEN pe.status = 'approved' THEN pe.new_due_date
        ELSE p.due_date
    END as effective_due_date
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id AND pe.status = 'pending'
    WHERE t.team_lead_id = ?";

$params = array($_SESSION['user_id']);
$types = "i";

if ($filter_status) {
    $query .= " AND p.status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

if ($filter_start_date) {
    $query .= " AND p.start_date >= ?";
    $params[] = $filter_start_date;
    $types .= "s";
}

if ($filter_end_date) {
    $query .= " AND p.due_date <= ?";
    $params[] = $filter_end_date;
    $types .= "s";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

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
    <title>Team Projects | TeamLead Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --background: #f8f9fa;
            --accent: #2980b9;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #c0392b;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
            min-height: 100vh;
            padding-top: 70px;
        }

        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-button {
            color: #fff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
        }

        .back-button:hover {
            color: var(--accent);
            transform: translateX(-3px);
        }

        .project-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .project-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-notviewed { background-color: #e9ecef; color: var(--secondary); }
        .status-instudy { background-color: var(--warning); color: #000; }
        .status-inprogress { background-color: var(--accent); color: #fff; }
        .status-completed { background-color: var(--success); color: #fff; }
        .status-verified { background-color: var(--primary); color: #fff; }

        .project-header {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .project-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
        }

        .extension-form {
            background: rgba(41, 128, 185, 0.05);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: var(--accent);
        }

        .date-badge {
            background: var(--accent);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.875rem;
        }

        .action-button {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            font-weight: 500;
            transition: all 0.3s ease;
            background-color: var(--accent);
            color: white;
        }

        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            background-color: #2573a7;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid var(--accent);
            border-top: 5px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        .btn-primary {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .btn-primary:hover {
            background-color: #2573a7;
            border-color: #2573a7;
        }

        .btn-outline-primary {
            color: var(--accent);
            border-color: var(--accent);
        }

        .btn-outline-primary:hover {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .alert {
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .project-header {
                padding: 1.5rem;
            }
            
            .project-card {
                margin-bottom: 1rem;
            }
        }

        .active-filters {
            background: white;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .filter-tag {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            background: var(--accent);
            color: white;
            border-radius: 15px;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .filter-tag .remove-filter {
            margin-left: 0.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a href="teamlead_dashboard.php" class="back-button me-4">
                <i class="fas fa-arrow-left me-2"></i>
                Back to Dashboard
            </a>
            <span class="navbar-brand">
                <i class="fas fa-project-diagram me-2"></i>
                Team Projects
            </span>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- Project Header -->
        <div class="project-header">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <h1 class="project-title">Project Overview</h1>
                    <p class="text-muted mb-0">Track and manage your team's projects</p>
                </div>
                <div class="col-md-6 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filterModal">
                        <i class="fas fa-filter me-2"></i>Filter Projects
                    </button>
                </div>
            </div>
        </div>

        <!-- Active Filters -->
        <?php if ($filter_status || $filter_start_date || $filter_end_date): ?>
        <div class="active-filters">
            <h6 class="mb-2">Active Filters:</h6>
            <?php if ($filter_status): ?>
            <span class="filter-tag">
                Status: <?php echo ucfirst($filter_status); ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['status' => null])); ?>" class="remove-filter text-white">×</a>
            </span>
            <?php endif; ?>
            
            <?php if ($filter_start_date): ?>
            <span class="filter-tag">
                From: <?php echo $filter_start_date; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['start_date' => null])); ?>" class="remove-filter text-white">×</a>
            </span>
            <?php endif; ?>

            <?php if ($filter_end_date): ?>
            <span class="filter-tag">
                To: <?php echo $filter_end_date; ?>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['end_date' => null])); ?>" class="remove-filter text-white">×</a>
            </span>
            <?php endif; ?>

            <a href="team_projects.php" class="btn btn-sm btn-outline-secondary ms-2">Clear All Filters</a>
        </div>
        <?php endif; ?>

        <!-- Project Cards -->
        <?php while ($project = $result->fetch_assoc()): ?>
        <div class="project-card">
            <div class="card-body p-4">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-3"><?php echo htmlspecialchars($project['title']); ?></h5>
                        <p class="text-muted mb-3"><?php echo htmlspecialchars($project['description']); ?></p>
                        
                        <div class="d-flex align-items-center mb-3">
                            <span class="status-badge status-<?php echo $project['status']; ?>">
                                <?php echo ucfirst($project['status']); ?>
                            </span>
                            <span class="ms-3 date-badge">
                                <i class="far fa-calendar-alt me-2"></i>
                                Due: <?php echo $project['effective_due_date']; ?>
                            </span>
                        </div>

                        <?php if ($project['status'] !== 'verified'): ?>
                        <form method="POST" class="mb-3">
                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                            <div class="row g-2">
                                <div class="col-auto">
                                    <select name="status" class="form-select">
                                    <option value="notviewed" <?php echo $project['status'] == 'notviewed' ? 'selected' : ''; ?>>Not Viewed</option>
                                        <option value="instudy" <?php echo $project['status'] == 'instudy' ? 'selected' : ''; ?>>In Study</option>
                                        <option value="inprogress" <?php echo $project['status'] == 'inprogress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="completed" <?php echo $project['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    </select>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" name="update_status" class="btn btn-primary action-button">
                                        Update Status
                                    </button>
                                </div>
                            </div>
                        </form>

                        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" 
                                data-bs-target="#extension<?php echo $project['project_id']; ?>">
                            <i class="fas fa-clock me-2"></i>Request Extension
                        </button>

                        <div class="collapse mt-3" id="extension<?php echo $project['project_id']; ?>">
                            <form method="POST" class="extension-form">
                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">New Due Date</label>
                                        <input type="date" name="new_due_date" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Reason for Extension</label>
                                        <textarea name="reason" class="form-control" rows="2" required></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" name="request_extension" class="btn btn-primary action-button">
                                            Submit Extension Request
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="progress mb-2">
                            <div class="progress-bar" role="progressbar" style="width: 
                                <?php
                                    switch($project['status']) {
                                        case 'notviewed': echo "0%"; break;
                                        case 'instudy': echo "25%"; break;
                                        case 'inprogress': echo "50%"; break;
                                        case 'completed': echo "75%"; break;
                                        case 'verified': echo "100%"; break;
                                    }
                                ?>" 
                                aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <div class="d-flex justify-content-between">
                            <small>Start: <?php echo $project['start_date']; ?></small>
                            <small>Due: <?php echo $project['effective_due_date']; ?></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>

        <?php if ($result->num_rows === 0): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h3 class="text-muted">No projects found</h3>
            <p class="text-muted">Try adjusting your filters or check back later</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filter Modal -->
    <div class="modal fade" id="filterModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Filter Projects</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="filterForm" action="" method="GET">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                <option value="notviewed" <?php echo $filter_status == 'notviewed' ? 'selected' : ''; ?>>Not Viewed</option>
                                <option value="instudy" <?php echo $filter_status == 'instudy' ? 'selected' : ''; ?>>In Study</option>
                                <option value="inprogress" <?php echo $filter_status == 'inprogress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $filter_status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="verified" <?php echo $filter_status == 'verified' ? 'selected' : ''; ?>>Verified</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Due Date Range</label>
                            <div class="row g-2">
                                <div class="col-6">
                                    <label class="form-label small">From</label>
                                    <input type="date" class="form-control" name="start_date" 
                                           value="<?php echo $filter_start_date; ?>">
                                </div>
                                <div class="col-6">
                                    <label class="form-label small">To</label>
                                    <input type="date" class="form-control" name="end_date"
                                           value="<?php echo $filter_end_date; ?>">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Show loading overlay during form submissions
        document.addEventListener('submit', function(e) {
            document.getElementById('loadingOverlay').style.display = 'flex';
        });

        // Filter functionality
        function applyFilters() {
            document.getElementById('loadingOverlay').style.display = 'flex';
            document.getElementById('filterForm').submit();
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Handle date validation
        document.querySelector('input[name="start_date"]').addEventListener('change', function(e) {
            document.querySelector('input[name="end_date"]').min = e.target.value;
        });

        document.querySelector('input[name="end_date"]').addEventListener('change', function(e) {
            document.querySelector('input[name="start_date"]').max = e.target.value;
        });

        // Alert auto-dismiss
        window.setTimeout(function() {
            const alert = document.querySelector('.alert');
            if (alert) {
                bootstrap.Alert.getOrCreateInstance(alert).close();
            }
        }, 5000);
    </script>
</body>
</html>