<?php
session_start();
require '../includes/db_connect.php';

// Ensure only managers can access this page
if ($_SESSION['role'] != 'Manager') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

// Prepare SQL query to fetch manager's file shares
$query = "SELECT share_id, title, description, username, created_at, is_project_file, team_id 
          FROM file_shares 
          WHERE (team_id IS NULL AND user_id = ?) 
             OR is_project_file = 1;
";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$shares = [];
while ($row = $result->fetch_assoc()) {
    $shares[] = $row;
}

$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager File Repository | Workspace</title>
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

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .navbar .back-btn:hover {
            color: #3498db;
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
        }

        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            color: #2c3e50;
            font-weight: 600;
            margin: 0;
        }

        .btn-upload {
            background-color: #3498db;
            border-color: #3498db;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-upload:hover {
            background-color: #2980b9;
            border-color: #2980b9;
            color: #ffffff;
        }

        .share-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .share-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .share-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .share-card.manager-file {
            border-left: 4px solid #e74c3c;
        }

        .share-card.project-file {
            border-left: 4px solid #2ecc71;
        }

        .share-link {
            text-decoration: none;
            color: inherit;
        }

        .share-content {
            padding: 1.5rem;
        }

        .share-title {
            color: #2c3e50;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .share-description {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .share-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: #95a5a6;
            font-size: 0.85rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .badge-custom {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-manager {
            background-color: #fde8e8;
            color: #e74c3c;
        }

        .badge-project {
            background-color: #e8f8f0;
            color: #2ecc71;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .empty-state i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 1.5rem;
        }

        .empty-state h3 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="manager_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand">File Repository</span>
        </div>
    </nav>

    <div class="container main-container">
        <div class="header-actions">
            <h1 class="page-title">Manager File Repository</h1>
            <a href="manager_start_file_share.php" class="btn btn-upload">
                <i class="fas fa-upload"></i>
                Upload New File
            </a>
        </div>

        <?php if (empty($shares)): ?>
            <div class="empty-state">
                <i class="fas fa-file-upload"></i>
                <h3>No Files Shared Yet</h3>
                <p>Start sharing files by clicking the "Upload New File" button above.</p>
                <a href="manager_start_file_share.php" class="btn btn-upload">
                    <i class="fas fa-upload me-2"></i>
                    Upload Your First File
                </a>
            </div>
        <?php else: ?>
            <div class="share-grid">
                <?php foreach ($shares as $share): ?>
                    <div class="share-card <?= $share['team_id'] === null ? 'manager-file' : ($share['is_project_file'] ? 'project-file' : '') ?>">
                        <a href="file_versions.php?share_id=<?= urlencode($share['share_id']) ?>" class="share-link">
                            <div class="share-content">
                                <h2 class="share-title">
                                    <?= htmlspecialchars($share['title']) ?>
                                </h2>
                                <p class="share-description">
                                    <?= htmlspecialchars($share['description']) ?>
                                </p>
                                <div class="share-meta">
                                    <span class="meta-item">
                                        <i class="far fa-user"></i>
                                        <?= htmlspecialchars($share['username']) ?>
                                    </span>
                                    <span class="meta-item">
                                        <i class="far fa-calendar"></i>
                                        <?= date('M j, Y', strtotime($share['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="mt-3">
                                    <?php if ($share['team_id'] === null): ?>
                                        <span class="badge-custom badge-manager">
                                            <i class="fas fa-lock me-1"></i>
                                            Manager File
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($share['is_project_file']): ?>
                                        <span class="badge-custom badge-project">
                                            <i class="fas fa-project-diagram me-1"></i>
                                            Project File
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>