<?php 
session_start(); 
require '../includes/db_connect.php';

// Ensure only team members can access this page 
if ($_SESSION['role'] != 'Manager' ) {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

// Get the team_id from session
$team_id = $_SESSION['team_id'];

// Prepare SQL query to fetch discussions for the current team
$query = "SELECT discussion_id, issue, username, started_at, description FROM discussions";
$stmt = $conn->prepare($query);
// $stmt->bind_param("i", $team_id);
$stmt->execute();
$result = $stmt->get_result();

// Fetch discussions from the database
$discussions = [];
while ($row = $result->fetch_assoc()) {
    $discussions[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussions</title>
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
            padding-top: 2rem;
        }

        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .page-header {
            background-color: #ffffff;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
            font-size: 1.75rem;
        }

        .back-btn {
            background-color: var(--primary);
            color: #ffffff;
            border: none;
            padding: 0.5rem 1.25rem;
            border-radius: 5px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }

        .back-btn:hover {
            background-color: var(--secondary);
            color: #ffffff;
            transform: translateY(-1px);
        }

        .discussion-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-decoration: none;
            color: var(--primary);
            display: block;
        }

        .discussion-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.1);
            color: var(--primary);
        }

        .discussion-body {
            padding: 1.5rem;
        }

        .discussion-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--primary);
        }

        .discussion-meta {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .discussion-description {
            color: #495057;
            margin: 0;
            line-height: 1.5;
        }

        @media (max-width: 768px) {
            .page-header {
                padding: 1.5rem;
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .discussion-body {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <div class="page-header">
            <h1 class="page-title">Team Discussions</h1>
            <a href="manager_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>

        <div class="discussions-list">
            <?php foreach ($discussions as $discussion): ?>
                <a href="manager_chat.php?discussion_id=<?= urlencode($discussion['discussion_id']) ?>" 
                   class="discussion-card">
                    <div class="discussion-body">
                        <h2 class="discussion-title">
                            <?= htmlspecialchars($discussion['issue']) ?>
                        </h2>
                        <div class="discussion-meta">
                            <i class="fas fa-user"></i>
                            <span><?= htmlspecialchars($discussion['username']) ?></span>
                            <i class="fas fa-clock ms-2"></i>
                            <span><?= htmlspecialchars($discussion['started_at']) ?></span>
                        </div>
                        <p class="discussion-description">
                            <?= htmlspecialchars($discussion['description']) ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>