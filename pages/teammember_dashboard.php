<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamMember') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];
$notificationCount = 0;

// Count unviewed tasks - matching the notifications page query
$taskQuery = "SELECT COUNT(*) as count 
    FROM tasks t 
    JOIN users u ON t.assigned_by = u.user_id 
    WHERE t.assigned_to = ? AND t.status = 'not_viewed'";
$stmt = $conn->prepare($taskQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$notificationCount += $row['count'];

// Count upcoming meetings - matching the notifications page query
$meetingQuery = "SELECT COUNT(*) as count 
    FROM meetings 
    WHERE team_id = ? 
    AND (status = 'scheduled' OR status = 'in_progress')";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$notificationCount += $row['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Member Dashboard</title>
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

        .nav-link {
            color: rgba(255,255,255,0.9) !important;
            padding: 0.5rem 1rem;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: #ffffff !important;
            transform: translateY(-1px);
        }

        /* Mobile Navigation Styles */
        @media (max-width: 991px) {
            .navbar-collapse {
                background-color: var(--primary);
                padding: 1rem;
                border-radius: 0 0 10px 10px;
                margin-top: 0.5rem;
            }

            .navbar-nav {
                padding: 0.5rem 0;
            }

            .nav-item {
                margin: 0.5rem 0;
            }

            .notification-link {
                display: inline-block;
                margin: 0.5rem 0;
            }

            .dropdown {
                display: block;
                margin: 0.5rem 0;
            }

            .user-menu {
                position: static !important;
                width: 100%;
                margin-top: 0.5rem;
                transform: none !important;
            }
        }

        .notification-link {
            position: relative;
            padding: 0.5rem 1rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            background-color: var(--danger);
            color: white;
            border-radius: 50%;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            min-width: 20px;
            text-align: center;
        }

        /* Rest of your existing styles remain the same */
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

        .menu-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        }

        .menu-icon {
            color: var(--accent);
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .menu-title {
            color: var(--primary);
            font-weight: 500;
            font-size: 1.1rem;
            margin: 0;
        }

        .btn-icon {
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
        }

        .btn-icon:hover {
            background-color: rgba(255, 255, 255, 0.1);
        }

        .user-menu {
            padding: 0.5rem;
            border: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            border-radius: 8px;
            min-width: 200px;
            background: white;
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
            color: #3498db;
        }

        .user-menu-item i {
            width: 20px;
            margin-right: 0.75rem;
        }

        .user-menu-item.text-danger:hover {
            color: #dc3545;
        }

        @media (max-width: 768px) {
            .dashboard-header {
                padding: 1.5rem;
            }
            
            .menu-card {
                margin-bottom: 1rem;
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
                Team Member Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <li class="nav-item">
                        <a class="notification-link" href="teammember_notifications.php">
                            <i class="fas fa-bell fa-lg"></i>
                            <?php if ($notificationCount > 0): ?>
                                <span class="notification-badge"><?php echo $notificationCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="nav-item">
                        <span class="nav-link">
                            <i class="fas fa-user me-2"></i>Team Member
                        </span>
                    </li>
                    <li class="nav-item">
                        <div class="dropdown">
                            <button class="btn btn-icon" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle fa-lg text-white"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end user-menu">
                                <a href="change_password.php" class="user-menu-item">
                                    <i class="fas fa-key"></i>
                                    Change Password
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="../logout.php" class="user-menu-item text-danger">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Logout
                                </a>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Rest of your existing content remains the same -->
    <div class="container" style="margin-top: 100px;">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">Welcome to Your Dashboard</h1>
            <p class="dashboard-subtitle">Manage your tasks and team activities</p>
        </div>

        <!-- Dashboard Menu Grid -->
        <div class="row g-4">
            <div class="col-md-3 col-sm-6">
                <a href="t_member_mytasks.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-tasks menu-icon"></i>
                            <h5 class="menu-title">My Tasks</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="view_meetings.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-video menu-icon"></i>
                            <h5 class="menu-title">View Meetings</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="discussion.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-comments menu-icon"></i>
                            <h5 class="menu-title">View Discussions</h5>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3 col-sm-6">
                <a href="file_sharing.php" class="text-decoration-none">
                    <div class="menu-card card">
                        <div class="card-body text-center p-4">
                            <i class="fas fa-file-alt menu-icon"></i>
                            <h5 class="menu-title">File Share</h5>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>