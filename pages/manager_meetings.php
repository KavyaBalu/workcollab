<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    // header('Location: login.php');
    // exit();
    echo "<script>window.location.href = 'error.php';</script>";
}

// Handle delete meeting
if (isset($_POST['delete_meeting'])) {
    $meeting_id = $_POST['meeting_id'];
    $deleteStmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ?");
    $deleteStmt->bind_param("i", $meeting_id);
    $deleteStmt->execute();
}

// Fetch meetings where manager presence is required
$stmt = $conn->prepare("
    SELECT m.*, u.username as creator_name, t.team_name 
    FROM meetings m 
    JOIN users u ON m.created_by = u.user_id 
    JOIN teams t ON m.team_id = t.team_id 
    WHERE m.manager_required = 1 
    ORDER BY m.scheduled_time DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Meetings</title>
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
            padding-top: 20px;
        }

        .navbar {
            background-color: var(--primary);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .page-header {
            background-color: #ffffff;
            padding: 1.5rem 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            position: relative;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            color: var(--primary);
            font-weight: 600;
            margin: 0;
        }

        .btn-back {
            background-color: var(--secondary);
            color: #ffffff;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
        }

        .btn-back:hover {
            background-color: var(--primary);
            color: #ffffff;
            text-decoration: none;
        }

        .meetings-table {
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .meetings-table th {
            background-color: var(--primary);
            color: #ffffff;
            font-weight: 500;
            border: none;
        }

        .meetings-table td {
            vertical-align: middle;
        }

        .delete-btn {
            background-color: var(--danger);
            border: none;
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            transition: all 0.3s ease;
        }

        .delete-btn:hover {
            opacity: 0.9;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Page Header with Right-Aligned Back Button -->
        <div class="page-header">
            <div class="header-content">
                <h1 class="page-title">Meetings Requiring Manager Presence</h1>
                <a href="manager_dashboard.php" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i>
                    <span>Back to Dashboard</span>
                </a>
            </div>
        </div>

        <!-- Meetings Table -->
        <div class="meetings-table">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Team</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Scheduled Time</th>
                        <th>Created By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($meeting = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($meeting['team_name']) ?></td>
                        <td><?= htmlspecialchars($meeting['title']) ?></td>
                        <td><?= htmlspecialchars($meeting['description']) ?></td>
                        <td><?= htmlspecialchars(date('M d, Y H:i', strtotime($meeting['scheduled_time']))) ?></td>
                        <td><?= htmlspecialchars($meeting['creator_name']) ?></td>
                        <td>
                            <span class="badge bg-<?= $meeting['status'] === 'in_progress' ? 'success' : 'warning' ?>">
                                <?= ucfirst(str_replace('_', ' ', $meeting['status'])) ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-2">
                                <?php if ($meeting['status'] === 'in_progress'): ?>
                                    <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                       class="btn btn-success btn-sm">
                                        <i class="fas fa-video me-1"></i>Join
                                    </a>
                                <?php endif; ?>
                                
                                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this meeting?');">
                                    <input type="hidden" name="meeting_id" value="<?= $meeting['meeting_id'] ?>">
                                    <button type="submit" name="delete_meeting" class="btn delete-btn btn-sm">
                                        <i class="fas fa-trash-alt text-white"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
</body>
</html>