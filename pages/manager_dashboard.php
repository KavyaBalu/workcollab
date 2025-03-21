<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Manager') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}

// Get current user's id
$manager_id = $_SESSION['user_id'];

// Count active notifications
// 1. Pending project extensions less than 2 days old
$extensionQuery = "SELECT COUNT(*) as extension_count 
    FROM project_extensions pe 
    WHERE pe.status = 'pending' 
    AND pe.requested_date >= DATE_SUB(CURRENT_DATE, INTERVAL 2 DAY)";
$extensionResult = $conn->query($extensionQuery);
$extensionCount = $extensionResult->fetch_assoc()['extension_count'];

// 2. Meetings requiring manager
$meetingQuery = "SELECT COUNT(*) as meeting_count 
    FROM meetings 
    WHERE manager_required = 1 
    AND (status = 'scheduled' OR status = 'in_progress')";
$meetingResult = $conn->query($meetingQuery);
$meetingCount = $meetingResult->fetch_assoc()['meeting_count'];

// Total notification count
$totalNotifications = $extensionCount + $meetingCount;

// Get teams count
$teamsQuery = "SELECT COUNT(*) as team_count FROM teams";
$teamsResult = $conn->query($teamsQuery);
$teamCount = $teamsResult->fetch_assoc()['team_count'];

// Get active projects count
$projectsQuery = "SELECT COUNT(*) as project_count FROM projects WHERE status != 'completed' AND status != 'verified'";
$projectsResult = $conn->query($projectsQuery);
$projectCount = $projectsResult->fetch_assoc()['project_count'];

// Get team leads count
$teamLeadsQuery = "SELECT COUNT(*) as lead_count FROM users WHERE role = 'TeamLead'";
$teamLeadsResult = $conn->query($teamLeadsQuery);
$teamLeadCount = $teamLeadsResult->fetch_assoc()['lead_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Management Console | Enterprise Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            color: #1a1f36;
        }
        
        .top-nav {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            padding: 0.75rem 0;
        }
        
        .notification-dropdown {
            min-width: 360px;
            padding: 0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border: none;
            border-radius: 8px;
        }
        
        .notification-item {
            padding: 1rem;
            border-bottom: 1px solid #f0f0f0;
            transition: background-color 0.2s ease;
        }
        
        .notification-item:hover {
            background-color: #f8f9fa;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .stat-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 12px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .action-card {
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        
        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #1a237e, #1565c0);
            color: white;
            border-radius: 16px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .nav-btn {
            padding: 0.5rem;
            border-radius: 8px;
            margin-left: 0.5rem;
            transition: all 0.2s ease;
        }

        .nav-btn:hover {
            background-color: #f8f9fa;
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .btn-icon:hover {
            background-color: #f8f9fa;
        }

        .user-menu {
            padding: 0.5rem;
        }

        .user-menu-item {
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            color: #1a1f36;
            text-decoration: none;
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .user-menu-item:hover {
            background-color: #f8f9fa;
            color: #1565c0;
        }

        .user-menu-item i {
            width: 20px;
            margin-right: 0.75rem;
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg top-nav sticky-top">
        <div class="container-fluid px-4">
            <a class="navbar-brand fw-bold text-primary" href="#">
                <i class="fas fa-shield-alt me-2"></i>Enterprise Console
            </a>
            
            <div class="d-flex align-items-center">
                <!-- Notifications -->
                <div class="dropdown me-3">
                    <button class="btn btn-icon position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <?php if ($totalNotifications > 0): ?>
                        <span class="position-absolute bg-danger text-white notification-badge rounded-circle">
                            <?php echo $totalNotifications; ?>
                        </span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <h6 class="dropdown-header">Notifications</h6>
                        <?php if ($extensionCount > 0): ?>
                        <div class="notification-item">
                            <i class="fas fa-clock text-warning me-2"></i>
                            <span><?php echo $extensionCount; ?> pending project extensions</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($meetingCount > 0): ?>
                        <div class="notification-item">
                            <i class="fas fa-calendar-check text-info me-2"></i>
                            <span><?php echo $meetingCount; ?> meetings require attention</span>
                        </div>
                        <?php endif; ?>
                        <?php if ($totalNotifications === 0): ?>
                        <div class="notification-item text-muted">
                            <i class="fas fa-check-circle me-2"></i>
                            No new notifications
                        </div>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="manager_notifications.php" class="dropdown-item text-primary text-center">
                            View All Notifications
                        </a>
                    </div>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end user-menu">
                        <a href="change_password.php" class="user-menu-item">
                            <i class="fas fa-key"></i>
                            Change Password
                        </a>
                        <a href="profile_settings.php" class="user-menu-item">
                            <i class="fas fa-gear"></i>
                            Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="../logout.php" class="user-menu-item text-danger">
                            <i class="fas fa-sign-out-alt"></i>
                            Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid px-4 py-4">
        <!-- Welcome Banner -->
        <div class="welcome-banner shadow">
            <h2 class="mb-2 fw-bold">Welcome to Enterprise Management</h2>
            <p class="mb-0 opacity-75">Monitor organizational performance and optimize team efficiency</p>
        </div>

        <!-- Statistics Grid -->
        <div class="row g-4 mb-4">
            <!-- [Previous statistics cards remain the same] -->
            <div class="col-md-3">
                <div class="card stat-card bg-primary bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title text-primary mb-3">
                            <i class="fas fa-users me-2"></i>Total Teams
                        </h6>
                        <h3 class="card-text mb-0"><?php echo $teamCount; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title text-success mb-3">
                            <i class="fas fa-project-diagram me-2"></i>Active Projects
                        </h6>
                        <h3 class="card-text mb-0"><?php echo $projectCount; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title text-info mb-3">
                            <i class="fas fa-user-tie me-2"></i>Team Leads
                        </h6>
                        <h3 class="card-text mb-0"><?php echo $teamLeadCount; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning bg-opacity-10">
                    <div class="card-body">
                        <h6 class="card-title text-warning mb-3">
                            <i class="fas fa-bell me-2"></i>Pending Actions
                        </h6>
                        <h3 class="card-text mb-0"><?php echo $totalNotifications; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Cards -->
        <div class="row g-4">
            <!-- [Previous action cards remain the same] -->
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-users-gear fa-2x text-primary"></i>
                        </div>
                        <h5 class="card-title">Team Management</h5>
                        <p class="card-text text-muted mb-4">Configure and oversee team structures</p>
                        <a href="manage_teams.php" class="btn btn-primary">Manage Teams</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-tasks fa-2x text-success"></i>
                        </div>
                        <h5 class="card-title">Project Oversight</h5>
                        <p class="card-text text-muted mb-4">Monitor and manage project progress</p>
                        <a href="manage_projects.php" class="btn btn-success">View Projects</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-comments fa-2x text-info"></i>
                        </div>
                        <h5 class="card-title">Team Discussions</h5>
                        <p class="card-text text-muted mb-4">View and participate in discussions</p>
                        <a href="manager_discussion.php" class="btn btn-info text-white">Open Discussions</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-calendar-alt fa-2x text-warning"></i>
                        </div>
                        <h5 class="card-title">Meeting Hub</h5>
                        <p class="card-text text-muted mb-4">Schedule and coordinate team meetings</p>
                        <a href="manager_meetings.php" class="btn btn-warning text-white">View Meetings</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-folder-tree fa-2x text-purple"></i>
                        </div>
                        <h5 class="card-title">Document Center</h5>
                        <p class="card-text text-muted mb-4">Manage and organize team documents</p>
                        <a href="manager_file_sharing.php" class="btn btn-purple" style="background: #6b46c1; color: white;">Manage Documents</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-list-check fa-2x text-danger"></i>
                        </div>
                        <h5 class="card-title">Project Assignment</h5>
                        <p class="card-text text-muted mb-4">Delegate and track project assignments</p>
                        <a href="assign_project.php" class="btn btn-danger">Assign Projects</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card action-card">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-chart-line fa-2x text-success"></i>
                        </div>
                        <h5 class="card-title">Analytics Dashboard</h5>
                        <p class="card-text text-muted mb-4">View performance metrics and reports</p>
                        <a href="project_performance.php" class="btn btn-success">View Analytics</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Refresh notifications every 5 minutes
        setInterval(() => {
            window.location.reload();
        }, 300000);

        // Add smooth scroll behavior for cards
        document.querySelectorAll('.action-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-4px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>