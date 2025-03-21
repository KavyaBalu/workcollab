<?php
 session_start();
 require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Sharing</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #2c3e50;
            padding: 1rem 0;
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

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
        }

        .share-container {
            max-width: 1000px;
            margin: 2rem auto;
        }

        .share-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            display: block;
            color: inherit;
        }

        .share-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            color: inherit;
        }

        .share-card.project-file {
            border-left: 4px solid #2ecc71;
        }

        .share-content {
            padding: 1.25rem;
        }

        .share-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .share-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .share-description {
            color: #2c3e50;
            margin-bottom: 0;
            line-height: 1.6;
        }

        .project-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem;
            background-color: #e8f6ef;
            color: #2ecc71;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .btn-share {
            background-color: #3498db;
            border: none;
            padding: 0.75rem 1.5rem;
            color: white;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }

        .btn-share:hover {
            background-color: #2980b9;
            color: white;
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

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';

    $team_id = $_SESSION['team_id'];
    $query = "SELECT share_id, title, description, username, created_at, is_project_file 
              FROM file_shares WHERE team_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $team_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $shares = [];
    while ($row = $result->fetch_assoc()) {
        $shares[] = $row;
    }
    $stmt->close();
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="<?php echo $dashboardUrl; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand">File Sharing</span>
        </div>
    </nav>

    <div class="container share-container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">Team Files</h1>
            <a href="start_file_share.php" class="btn btn-share">
                <i class="fas fa-plus"></i>
                Share New File
            </a>
        </div>

        <?php foreach ($shares as $share): ?>
            <a href="file_versions.php?share_id=<?= urlencode($share['share_id']) ?>" 
               class="share-card <?= $share['is_project_file'] ? 'project-file' : '' ?>">
                <div class="share-content">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="share-title"><?= htmlspecialchars($share['title']) ?></h3>
                        <?php if ($share['is_project_file']): ?>
                            <span class="project-badge">
                                <i class="fas fa-project-diagram"></i>
                                Project File
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="share-meta">
                        <span class="meta-item">
                            <i class="far fa-user"></i>
                            <?= htmlspecialchars($share['username']) ?>
                        </span>
                        <span class="meta-item">
                            <i class="far fa-calendar"></i>
                            <?= date('F j, Y', strtotime($share['created_at'])) ?>
                        </span>
                        <span class="meta-item">
                            <i class="far fa-clock"></i>
                            <?= date('g:i A', strtotime($share['created_at'])) ?>
                        </span>
                    </div>
                    
                    <p class="share-description">
                        <?= htmlspecialchars($share['description']) ?>
                    </p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>