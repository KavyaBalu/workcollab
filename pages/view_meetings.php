<?php
session_start(); 
require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Meetings | Workspace</title>
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

        .meetings-container {
            max-width: 1200px;
            margin: 2rem auto;
        }

        .meeting-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .meeting-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .meeting-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .meeting-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .meeting-content {
            padding: 1.25rem;
        }

        .meeting-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 2rem;
            margin-bottom: 1rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .meeting-description {
            color: #2c3e50;
            margin-bottom: 1.25rem;
            line-height: 1.6;
        }

        .meeting-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-scheduled {
            background-color: #e9ecef;
            color: #2c3e50;
        }

        .status-in-progress {
            background-color: #d4edda;
            color: #155724;
        }

        .btn-meeting {
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-start {
            background-color: #3498db;
            border-color: #3498db;
            color: #ffffff;
        }

        .btn-start:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .btn-join {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: #ffffff;
        }

        .btn-join:hover {
            background-color: #27ae60;
            border-color: #27ae60;
        }

        .btn-waiting {
            background-color: #e9ecef;
            border-color: #dee2e6;
            color: #6c757d;
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
        }
    </style>
</head>
<body>
    <?php
    


    if (!isset($_SESSION['user_id'])) {
        // header('Location: login.php');
        echo "<script>window.location.href = 'error.php';</script>";
        exit();
    }

    // Determine dashboard URL based on role
    $dashboardUrl = ($_SESSION['role'] === 'TeamLead') ? 'teamlead_dashboard.php' : 'teammember_dashboard.php';

    // Fetch meetings based on team_id
    $stmt = $conn->prepare("
        SELECT m.*, u.username as creator_name 
        FROM meetings m 
        JOIN users u ON m.created_by = u.user_id 
        WHERE m.team_id = ? 
        ORDER BY m.scheduled_time DESC
    ");
    $stmt->bind_param("i", $_SESSION['team_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="<?php echo $dashboardUrl; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand">Team Meetings</span>
        </div>
    </nav>

    <div class="container meetings-container">
        <?php if ($result->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Meetings Scheduled</h3>
                <p class="text-muted">There are currently no meetings scheduled for your team.</p>
            </div>
        <?php else: ?>
            <div class="row">
                <?php while ($meeting = $result->fetch_assoc()): ?>
                    <div class="col-12">
                        <div class="meeting-card">
                            <div class="meeting-header">
                                <h5 class="meeting-title"><?= htmlspecialchars($meeting['title']) ?></h5>
                                <span class="meeting-status <?= $meeting['status'] === 'scheduled' ? 'status-scheduled' : 'status-in-progress' ?>">
                                    <i class="fas <?= $meeting['status'] === 'scheduled' ? 'fa-clock' : 'fa-play-circle' ?>"></i>
                                    <?= ucfirst(str_replace('_', ' ', $meeting['status'])) ?>
                                </span>
                            </div>
                            <div class="meeting-content">
                                <div class="meeting-meta">
                                    <div class="meta-item">
                                        <i class="far fa-calendar"></i>
                                        <?= date('F j, Y', strtotime($meeting['scheduled_time'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-clock"></i>
                                        <?= date('g:i A', strtotime($meeting['scheduled_time'])) ?>
                                    </div>
                                    <div class="meta-item">
                                        <i class="far fa-user"></i>
                                        Created by <?= htmlspecialchars($meeting['creator_name']) ?>
                                    </div>
                                </div>
                                
                                <p class="meeting-description"><?= htmlspecialchars($meeting['description']) ?></p>
                                
                                <div class="text-end">
                                    <?php if ($_SESSION['role'] === 'TeamLead'): ?>
                                        <?php if ($meeting['status'] === 'scheduled'): ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               onclick="startMeeting(<?= $meeting['meeting_id'] ?>)"
                                               class="btn btn-meeting btn-start">
                                                <i class="fas fa-play"></i>
                                                Start Meeting
                                            </a>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               class="btn btn-meeting btn-join">
                                                <i class="fas fa-video"></i>
                                                Join Meeting
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($meeting['status'] === 'in_progress'): ?>
                                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                               class="btn btn-meeting btn-join">
                                                <i class="fas fa-video"></i>
                                                Join Meeting
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-meeting btn-waiting" disabled>
                                                <i class="fas fa-clock"></i>
                                                Waiting to Start
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function startMeeting(meetingId) {
            fetch('update_meeting_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `meeting_id=${meetingId}&status=in_progress`
            });
        }
    </script>
</body>
</html>