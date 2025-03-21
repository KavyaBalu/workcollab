<?php
 session_start(); 
 require '../includes/db_connect.php';
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussions</title>
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

        .start-discussion-btn {
            background-color: #3498db;
            border: none;
        }

        .start-discussion-btn:hover {
            background-color: #2980b9;
        }

        .discussion-meta {
            color: #6c757d;
            font-size: 0.875rem;
        }

        .discussion-title {
            color: #2c3e50;
            font-weight: 600;
        }

        .loading-spinner {
            display: none;
            width: 3rem;
            height: 3rem;
        }
    </style>
</head>
<body>
    <?php 
   
    
    if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead') {
        // header("Location: error.php");
        // exit;
        echo "<script>window.location.href = 'error.php';</script>";
    }
    
    $team_id = $_SESSION['team_id'];
    $query = "SELECT discussion_id, issue, username, started_at, description FROM discussions WHERE team_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $discussions = [];
    while ($row = $result->fetch_assoc()) {
        $discussions[] = $row;
    }
    $stmt->close();
    
    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
        <div class="container">
            <a href="<?php echo $dashboardUrl; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand ms-3">Team Discussions</span>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Recent Discussions</h1>
            <a href="start_discussion.php" class="btn btn-primary start-discussion-btn">
                <i class="fas fa-plus me-2"></i>Start Discussion
            </a>
        </div>

        <!-- Loading Spinner -->
        <div class="text-center loading-spinner">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>

        <!-- Discussions List -->
        <div class="row">
            <?php foreach ($discussions as $discussion): ?>
            <div class="col-12 mb-3">
                <a href="chat_page.php?discussion_id=<?= urlencode($discussion['discussion_id']) ?>" 
                   class="text-decoration-none">
                    <div class="card discussion-card">
                        <div class="card-body">
                            <h5 class="discussion-title mb-2">
                                <?= htmlspecialchars($discussion['issue']) ?>
                            </h5>
                            <div class="discussion-meta mb-2">
                                <i class="fas fa-user-circle me-1"></i>
                                <?= htmlspecialchars($discussion['username']) ?>
                                <i class="fas fa-clock ms-3 me-1"></i>
                                <?= htmlspecialchars($discussion['started_at']) ?>
                            </div>
                            <p class="card-text text-muted mb-0">
                                <?= htmlspecialchars($discussion['description']) ?>
                            </p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>