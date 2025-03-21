<?php
 session_start(); 
 require '../includes/db_connect.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Discussion</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: #3498db;
        }

        .discussion-card {
            background: #ffffff;
            border: none;
            border-radius: 0.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .discussion-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .form-control {
            border: 1px solid #e0e0e0;
            padding: 0.75rem;
            border-radius: 0.5rem;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .error-alert {
            background-color: #e74c3c;
            border: none;
            color: #ffffff;
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <?php 
   

    // Ensure only Team Members or Team Leads can access this page
    if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['TeamMember', 'TeamLead'])) {
        // header("Location: error.php");
        // exit;
        echo "<script>window.location.href = 'error.php';</script>";
    }

    // Initialize error variable
    $error = "";

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Collect form data
        $issue = $_POST['issue'];
        $description = $_POST['description'];
        $user_id = $_SESSION['user_id'];
        $username = $_SESSION['username'];
        $team_id = $_SESSION['team_id'];

        // Prepare SQL query
        $query = "
            INSERT INTO discussions (team_id, issue, description, user_id, username, started_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issis", $team_id, $issue, $description, $user_id, $username);

        if ($stmt->execute()) {
            // header("Location: discussion.php");
            // exit;
            echo "<script>window.location.href = 'discussion.php';</script>";
        } else {
            $error = "Failed to start the discussion. Please try again.";
        }
    }

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
        <div class="container">
            <a href="discussion.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Discussions</span>
            </a>
            <span class="navbar-brand ms-3">Start Discussion</span>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card discussion-card">
                    <div class="card-body p-4">
                        <?php if (!empty($error)): ?>
                            <div class="alert error-alert mb-4" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?= htmlspecialchars($error) ?>
                            </div>
                        <?php endif; ?>

                        <form action="start_discussion.php" method="POST">
                            <div class="mb-4">
                                <label for="issue" class="form-label">Issue Title</label>
                                <input type="text" class="form-control" id="issue" name="issue" 
                                    placeholder="Enter the issue title" required>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" 
                                    rows="6" placeholder="Describe the issue or topic" required></textarea>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-2"></i>Create Discussion
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>