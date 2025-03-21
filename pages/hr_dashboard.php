<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'HR') {
    // header("Location: login.php");
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Command Center</title>
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
        
        .navbar-brand {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .stat-card {
            transition: transform 0.3s ease;
            border: none;
            border-radius: 10px;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-card {
            background: white;
            border: none;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            margin-bottom: 1rem;
        }
        
        .logout-btn {
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .logout-btn:hover {
            background-color: #e74c3c;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-shield-alt me-2"></i>
                HR Command Center
            </a>
            <div class="ms-auto">
                <a href="../logout.php" class="btn logout-btn">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <!-- Statistics Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card bg-primary bg-opacity-10 h-100">
                    <div class="card-body">
                        <h6 class="card-title text-primary">
                            <i class="fas fa-users me-2"></i>Total Users
                        </h6>
                        <h2 class="card-text mb-0">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM users";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-success bg-opacity-10 h-100">
                    <div class="card-body">
                        <h6 class="card-title text-success">
                            <i class="fas fa-project-diagram me-2"></i>Active Teams
                        </h6>
                        <h2 class="card-text mb-0">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM teams";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-warning bg-opacity-10 h-100">
                    <div class="card-body">
                        <h6 class="card-title text-warning">
                            <i class="fas fa-tasks me-2"></i>Total Projects
                        </h6>
                        <h2 class="card-text mb-0">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM projects";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card bg-info bg-opacity-10 h-100">
                    <div class="card-body">
                        <h6 class="card-title text-info">
                            <i class="fas fa-check-circle me-2"></i>Active Tasks
                        </h6>
                        <h2 class="card-text mb-0">
                            <?php
                            $query = "SELECT COUNT(*) as count FROM tasks WHERE status != 'verified'";
                            $result = $conn->query($query);
                            $row = $result->fetch_assoc();
                            echo $row['count'];
                            ?>
                        </h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Features Grid -->
        <div class="row g-4">
            <div class="col-md-4">
                <a href="hr_panel.php" class="text-decoration-none">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h5 class="card-title text-dark">Workforce Management</h5>
                            <p class="card-text text-muted">Add, modify, and manage user accounts across the organization.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="create_team.php" class="text-decoration-none">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-people-group"></i>
                            </div>
                            <h5 class="card-title text-dark">Team Configuration</h5>
                            <p class="card-text text-muted">Create and structure teams for optimal collaboration.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4">
                <a href="project_performance.php" class="text-decoration-none">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h5 class="card-title text-dark">Project Analytics</h5>
                            <p class="card-text text-muted">Track and analyze project metrics and performance indicators.</p>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mt-4">
                <a href="performance_analysis.php" class="text-decoration-none">
                    <div class="card feature-card h-100">
                        <div class="card-body">
                            <div class="feature-icon bg-info bg-opacity-10 text-info">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <h5 class="card-title text-dark">Performance Insights</h5>
                            <p class="card-text text-muted">Review and assess team member contributions and achievements.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>