<?php
session_start();
require 'includes/db_connect.php';

// Fetch stats from database
$totalTeamsQuery = "SELECT COUNT(*) as total_teams FROM teams";
$totalTeamsResult = $conn->query($totalTeamsQuery);
$totalTeams = $totalTeamsResult->fetch_assoc()['total_teams'];

$totalProjectsQuery = "SELECT COUNT(*) as total_projects FROM projects";
$totalProjectsResult = $conn->query($totalProjectsQuery);
$totalProjects = $totalProjectsResult->fetch_assoc()['total_projects'];

$totalTasksQuery = "SELECT COUNT(*) as total_tasks FROM tasks";
$totalTasksResult = $conn->query($totalTasksQuery);
$totalTasks = $totalTasksResult->fetch_assoc()['total_tasks'];

$totalUsersQuery = "SELECT COUNT(*) as total_users FROM users";
$totalUsersResult = $conn->query($totalUsersQuery);
$totalUsers = $totalUsersResult->fetch_assoc()['total_users'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Collaborative Work Community</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        /* Previous styles remain the same */
        /* Adding new styles for stats section */
        .stats-section {
            margin-top: -100px;
            position: relative;
            z-index: 10;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: #4299e1;
            margin-bottom: 1rem;
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .stat-card p {
            color: #4a5568;
            font-size: 1.1rem;
            margin: 0;
        }

        /* All previous styles remain here */
        :root {
            --primary: #2c3e50;
            --secondary: #34495e;
            --accent: #3498db;
            --background: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background);
        }

        .navbar {
            background: linear-gradient(135deg, #1a365d, #2c5282);
            padding: 1rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            color: white !important;
        }

        .hero-section {
            background: linear-gradient(135deg, #1a365d, #2c5282);
            color: white;
            padding: 8rem 0;
            text-align: center;
        }

        .hero-section h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
        }

        .hero-section p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-button {
            background: #4299e1;
            color: white;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            font-size: 1.1rem;
        }

        .cta-button:hover {
            background: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.3);
            color: white;
        }

        .timeline-section {
            padding: 6rem 0;
            background: white;
        }

        .timeline {
            position: relative;
            max-width: 1200px;
            margin: 0 auto;
        }

        .timeline::after {
            content: '';
            position: absolute;
            width: 6px;
            background: #4299e1;
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -3px;
            border-radius: 3px;
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            margin-bottom: 3rem;
        }

        .timeline-item::after {
            content: '';
            position: absolute;
            width: 25px;
            height: 25px;
            right: -17px;
            background: #4299e1;
            border: 4px solid #c3dafe;
            top: 15px;
            border-radius: 50%;
            z-index: 1;
        }

        .timeline-item.left {
            left: 0;
        }

        .timeline-item.right {
            left: 50%;
        }

        .timeline-item.right::after {
            left: -8px;
        }

        .timeline-content {
            padding: 20px 30px;
            background: white;
            position: relative;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .timeline-content h3 {
            color: #2d3748;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .timeline-content p {
            color: #4a5568;
            line-height: 1.6;
        }

        .features-section {
            padding: 6rem 0;
            background: var(--background);
        }

        .feature-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #4299e1;
            margin-bottom: 1rem;
        }

        footer {
            background: #1a365d;
            color: white;
            padding: 3rem 0;
            text-align: center;
        }

        @media screen and (max-width: 768px) {
            .timeline::after {
                left: 31px;
            }
            
            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }
            
            .timeline-item.right {
                left: 0;
            }
            
            .timeline-item.left::after,
            .timeline-item.right::after {
                left: 23px;
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
                WorkCollab
            </a>
            <a href="pages/login.php" class="cta-button ms-auto">
                Login
            </a>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1>Transform Your Team Collaboration</h1>
            <p class="mb-5">Experience the future of remote collaboration with our innovative platform</p>
            <a href="login.php" class="cta-button">Get Started</a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?php echo $totalTeams; ?></h3>
                        <p>Active Teams</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-project-diagram"></i>
                        <h3><?php echo $totalProjects; ?></h3>
                        <p>Total Projects</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-tasks"></i>
                        <h3><?php echo $totalTasks; ?></h3>
                        <p>Tasks Completed</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card">
                        <i class="fas fa-user-friends"></i>
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section">
        <div class="container text-center mb-5">
            <h2 class="display-4 mb-4">Our Journey</h2>
            <p class="lead text-muted">Growing together, achieving more</p>
        </div>
        <div class="timeline">
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>2025 - The Beginning</h3>
                    <p>Launched our platform with a vision to revolutionize remote collaboration. Started with core features for team communication and file sharing.</p>
                </div>
            </div>
            <div class="timeline-item right">
                <div class="timeline-content">
                    <h3>Project Management Evolution</h3>
                    <p>Introduced comprehensive project tracking systems and team-specific workspaces to enhance productivity.</p>
                </div>
            </div>
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>Enhanced Security</h3>
                    <p>Implemented advanced security measures with role-based access control and encrypted file sharing.</p>
                </div>
            </div>
            <div class="timeline-item right">
                <div class="timeline-content">
                    <h3>Virtual Meetings Integration</h3>
                    <p>Seamlessly integrated Google Meet with custom controls for team leads and managers.</p>
                </div>
            </div>
            <div class="timeline-item left">
                <div class="timeline-content">
                    <h3>Performance Analytics</h3>
                    <p>Launched comprehensive analytics tools for tracking team and individual performance.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="display-4 mb-4">Platform Features</h2>
                <p class="lead text-muted">Everything you need for effective collaboration</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Team Collaboration</h3>
                        <p>Secure team-specific discussions and file sharing with version control system. Isolated team spaces for focused collaboration.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <h3>Task Management</h3>
                        <p>Comprehensive task tracking with progress monitoring and point-based rewards. Manager oversight for project timeline control.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-video"></i>
                        </div>
                        <h3>Virtual Meetings</h3>
                        <p>Integrated Google Meet platform with customizable participation settings and manager notification system.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p class="mb-1">&copy; 2025 WorkCollab. All rights reserved.</p>
            <p class="mb-0">Building the future of work, together.</p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>