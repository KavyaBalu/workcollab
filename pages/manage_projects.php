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
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'table';

// Handle project verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_project'])) {
    $project_id = (int)$_POST['project_id'];
    
    // Check if project exists and is in completed status
    $check_stmt = $conn->prepare("SELECT status FROM projects WHERE project_id = ?");
    $check_stmt->bind_param("i", $project_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $project_status = $result->fetch_assoc();
    $check_stmt->close();

    if ($project_status && $project_status['status'] === 'completed') {
        $stmt = $conn->prepare("UPDATE projects SET status = 'verified', actual_end_date = CURDATE() WHERE project_id = ?");
        $stmt->bind_param("i", $project_id);
        
        if ($stmt->execute()) {
            $message = "Project verified successfully!";
        } else {
            $message = "Error verifying project: " . $conn->error;
        }
        $stmt->close();
    } else {
        $message = "Project must be in 'completed' status to verify.";
    }
}

// Handle extension requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['respond_extension'])) {
    $extension_id = (int)$_POST['extension_id'];
    $response_type = $_POST['response_type'];
    $response_note = sanitizeInput($_POST['response_note']);
    
    if ($response_type !== 'approved' && $response_type !== 'rejected') {
        $message = "Invalid response type";
    } else {
        $conn->begin_transaction();
        
        try {
            $stmt = $conn->prepare("
                UPDATE project_extensions 
                SET status = ?, response_note = ?, responded_at = CURRENT_TIMESTAMP 
                WHERE extension_id = ? AND status = 'pending'");
            $stmt->bind_param("ssi", $response_type, $response_note, $extension_id);
            
            if ($stmt->execute()) {
                if ($response_type === 'approved') {
                    $stmt2 = $conn->prepare("
                        UPDATE projects p
                        JOIN project_extensions pe ON p.project_id = pe.project_id
                        SET p.due_date = pe.new_due_date
                        WHERE pe.extension_id = ?");
                    $stmt2->bind_param("i", $extension_id);
                    $stmt2->execute();
                    $stmt2->close();
                }
                
                $conn->commit();
                $message = "Extension request " . $response_type . " successfully!";
            } else {
                throw new Exception("Error updating extension request");
            }
            
        } catch (Exception $e) {
            $conn->rollback();
            $message = "Error processing extension request: " . $e->getMessage();
        }
        
        if (isset($stmt)) {
            $stmt->close();
        }
    }
}

function sanitizeInput($input) {
    global $conn;
    return mysqli_real_escape_string($conn, htmlspecialchars(trim($input)));
}

function truncateDescription($description, $length = 100) {
    return strlen($description) > $length 
        ? substr($description, 0, $length) . '...' 
        : $description;
}

// Fetch all projects with team and extension request details
$query = "
    SELECT 
        p.*,
        t.team_name,
        u.username as team_lead,
        pe.extension_id,
        pe.new_due_date as requested_due_date,
        pe.reason as extension_reason,
        pe.status as extension_status,
        DATEDIFF(p.due_date, CURDATE()) as days_remaining
    FROM projects p
    JOIN teams t ON p.team_id = t.team_id
    JOIN users u ON t.team_lead_id = u.user_id
    LEFT JOIN project_extensions pe ON p.project_id = pe.project_id 
        AND pe.status = 'pending'
    ORDER BY 
        CASE p.status
            WHEN 'completed' THEN 1
            WHEN 'inprogress' THEN 2
            WHEN 'instudy' THEN 3
            WHEN 'notviewed' THEN 4
            ELSE 5
        END,
        p.due_date ASC";

$result = $conn->query($query);

if (!$result) {
    die("Error fetching projects: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Management Dashboard</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Inter Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --success-color: #2ecc71;
            --warning-color: #f1c40f;
            --danger-color: #e74c3c;
            --background-color: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
        }

        .navbar {
            background-color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .back-btn {
            transition: all 0.3s ease;
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .back-btn:hover {
            transform: translateX(-5px);
            background-color: var(--primary-color);
            color: white;
        }

        .project-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 12px;
        }

        .project-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
        }

        .table-container {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 1rem;
        }

        .custom-table {
            margin: 0;
        }

        .custom-table th {
            background-color: var(--primary-color);
            color: white;
            font-weight: 500;
        }

        .view-toggle-btn {
            background-color: var(--accent-color);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .view-toggle-btn:hover {
            background-color: var(--primary-color);
            transform: translateY(-2px);
        }

        .status-verified { background-color: rgba(46, 204, 113, 0.1); }
        .status-completed { background-color: rgba(52, 152, 219, 0.1); }
        .status-inprogress { background-color: rgba(241, 196, 15, 0.1); }
        .extension-pending { 
            background-color: rgba(241, 196, 15, 0.1);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
        }

        @media (max-width: 768px) {
            .project-card {
                margin-bottom: 1rem;
            }
            
            .custom-table {
                display: block;
                overflow-x: auto;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-project-diagram me-2"></i>
                Project Management
            </a>
            <div class="d-flex">
                <a href="manager_dashboard.php" class="btn btn-outline-light">
                    <i class="fas fa-home me-2"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
               
            </div>
            <div class="d-flex gap-2">
                <a href="?view=<?php echo $view_mode === 'table' ? 'grid' : 'table'; ?>" 
                   class="view-toggle-btn">
                    <i class="fas fa-<?php echo $view_mode === 'table' ? 'th' : 'list'; ?> me-2"></i>
                    Switch to <?php echo $view_mode === 'table' ? 'Grid' : 'Table'; ?> View
                </a>
                <a href="assign_project.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Assign New Project
                </a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo strpos($message, 'Error') !== false ? 'danger' : 'success'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($view_mode === 'grid'): ?>
            <div class="row g-4">
                <?php while ($project = $result->fetch_assoc()): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card project-card">
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
                                        <strong><?php echo htmlspecialchars($project['team_lead']); ?></strong>
                                    </div>
                                </div>
                                
                                <?php if ($project['extension_id']): ?>
                                    <div class="extension-pending mt-3">
                                        <p class="mb-2"><strong>Extension Requested</strong></p>
                                        <p class="mb-1">New Due Date: <?php echo $project['requested_due_date']; ?></p>
                                        <p class="mb-2">Reason: <?php echo htmlspecialchars($project['extension_reason']); ?></p>
                                        <form method="POST" class="mt-2">
                                            <input type="hidden" name="extension_id" value="<?php echo $project['extension_id']; ?>">
                                            <div class="mb-2">
                                                <textarea name="response_note" class="form-control" placeholder="Response note" required></textarea>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <select name="response_type" class="form-select" required>
                                                    <option value="">Select Response</option>
                                                    <option value="approved">Approve</option>
                                                    <option value="rejected">Reject</option>
                                                </select>
                                                <button type="submit" name="respond_extension" class="btn btn-primary">
                                                    Submit
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3 d-flex gap-2 justify-content-center">
                                    <?php if ($project['status'] === 'completed'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                            <button type="submit" name="verify_project" class="btn btn-success btn-sm">
                                                <i class="fas fa-check me-1"></i>Verify
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <a href="project_details.php?id=<?php echo $project['project_id']; ?>" 
                                       class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php $result->data_seek(0); // Reset result pointer for table view ?>
            </div>
        <?php else: ?>
            <div class="table-container">
                <table class="table custom-table">
                    <thead>
                        <tr>
                            <th>Project Title</th>
                            <th>Team</th>
                            <th>Team Lead</th>
                            <th>Status</th>
                            <th>Timeline</th>
                            <th>Extension Request</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($project = $result->fetch_assoc()): ?>
                            <tr class="status-<?php echo $project['status']; ?>">
                                <td>
                                    <strong><?php echo htmlspecialchars($project['title']); ?></strong>
                                    <br>
                                    <small class="text-muted"><?php echo htmlspecialchars(truncateDescription($project['description'])); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($project['team_name']); ?></td>
                                <td><?php echo htmlspecialchars($project['team_lead']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo match($project['status']) {
                                            'completed' => 'success',
                                            'verified' => 'primary',
                                            'inprogress' => 'warning',
                                            'instudy' => 'info',
                                            default => 'secondary'
                                        };
                                    ?>">
                                        <?php echo ucfirst($project['status']); ?>
                                    </span>
                                    <?php if ($project['status'] !== 'verified' && $project['days_remaining'] < 0): ?>
                                        <br>
                                        <small class="text-danger">
                                            <i class="fas fa-exclamation-circle"></i>
                                            Overdue by <?php echo abs($project['days_remaining']); ?> days
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted d-block">Start: <?php echo date('M d, Y', strtotime($project['start_date'])); ?></small>
                                    <small class="text-muted d-block">Due: <?php echo date('M d, Y', strtotime($project['due_date'])); ?></small>
                                    <?php if ($project['actual_end_date']): ?>
                                        <small class="text-success d-block">
                                            Completed: <?php echo date('M d, Y', strtotime($project['actual_end_date'])); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($project['extension_id']): ?>
                                        <div class="extension-pending">
                                            <small class="d-block"><strong>Extension Requested</strong></small>
                                            <small class="d-block">New Due: <?php echo $project['requested_due_date']; ?></small>
                                            <small class="d-block mb-2">Reason: <?php echo htmlspecialchars(truncateDescription($project['extension_reason'], 50)); ?></small>
                                            <form method="POST">
                                                <input type="hidden" name="extension_id" value="<?php echo $project['extension_id']; ?>">
                                                <div class="mb-2">
                                                    <textarea name="response_note" class="form-control form-control-sm" placeholder="Response note" required></textarea>
                                                </div>
                                                <div class="d-flex gap-1">
                                                    <select name="response_type" class="form-select form-select-sm" required>
                                                        <option value="">Response</option>
                                                        <option value="approved">Approve</option>
                                                        <option value="rejected">Reject</option>
                                                    </select>
                                                    <button type="submit" name="respond_extension" class="btn btn-primary btn-sm">
                                                        Submit
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-1">
                                        <?php if ($project['status'] === 'completed'): ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                                                <button type="submit" name="verify_project" class="btn btn-success btn-sm">
                                                    <i class="fas fa-check me-1"></i>Verify
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        <a href="project_details.php?id=<?php echo $project['project_id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            <i class="fas fa-eye me-1"></i>Details
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>