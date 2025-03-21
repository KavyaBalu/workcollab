<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamMember') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$team_id = $_SESSION['team_id'];

// Fetch unviewed tasks
$taskQuery = "SELECT t.*, u.username as assigned_by_name 
    FROM tasks t 
    JOIN users u ON t.assigned_by = u.user_id 
    WHERE t.assigned_to = ? AND t.status = 'not_viewed'
    ORDER BY t.created_at DESC";
$stmt = $conn->prepare($taskQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$taskResult = $stmt->get_result();

// Fetch upcoming meetings
$meetingQuery = "SELECT * FROM meetings 
    WHERE team_id = ? 
    AND (status = 'scheduled' OR status = 'in_progress')
    ORDER BY scheduled_time ASC";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $team_id);
$stmt->execute();
$meetingResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Member Notifications | Workspace</title>
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

        .notifications-container {
            max-width: 1200px;
            margin: 2rem auto;
        }

        .notification-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .notification-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }

        .notification-content {
            padding: 1.25rem;
        }

        .section-title {
            color: #2c3e50;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
        }

        .points-badge {
            background-color: #3498db;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
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

        .btn-view {
            background-color: #3498db;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-view:hover {
            background-color: #2980b9;
            color: #ffffff;
        }

        .btn-join {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-join:hover {
            background-color: #27ae60;
            color: #ffffff;
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

        .timer {
            color: #6c757d;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="teammember_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand">Notifications</span>
        </div>
    </nav>

    <div class="container notifications-container">
        <!-- Tasks Section -->
        <h2 class="section-title">
            <i class="fas fa-tasks me-2"></i>
            New Tasks Assigned
        </h2>
        
        <?php if ($taskResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No New Tasks</h3>
                <p class="text-muted">You're all caught up! There are no new tasks assigned to you.</p>
            </div>
        <?php else: ?>
            <?php while ($task = $taskResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($task['task_title']); ?></h5>
                    <span class="points-badge">
                        <i class="fas fa-star"></i>
                        <?php echo $task['points']; ?> Points
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-user"></i>
                        <span>Assigned by: <?php echo htmlspecialchars($task['assigned_by_name']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-align-left"></i>
                        <span><?php echo htmlspecialchars($task['task_description']); ?></span>
                    </div>
                    <?php if ($task['due_date']): ?>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Due: <?php echo date('F j, Y', strtotime($task['due_date'])); ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="text-end mt-3">
                        <a href="t_member_mytasks.php" class="btn-view">
                            <i class="fas fa-eye"></i>
                            View Task Details
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Meetings Section -->
        <h2 class="section-title">
            <i class="fas fa-calendar-check me-2"></i>
            Team Meetings
        </h2>

        <?php if ($meetingResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Meetings</h3>
                <p class="text-muted">There are currently no meetings scheduled for your team.</p>
            </div>
        <?php else: ?>
            <?php while ($meeting = $meetingResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                    <span class="meeting-status <?php echo $meeting['status'] === 'scheduled' ? 'status-scheduled' : 'status-in-progress' ?>">
                        <i class="fas <?php echo $meeting['status'] === 'scheduled' ? 'fa-clock' : 'fa-play-circle' ?>"></i>
                        <?php echo ucfirst(str_replace('_', ' ', $meeting['status'])); ?>
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-align-left"></i>
                        <span><?php echo htmlspecialchars($meeting['description']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('g:i A', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <?php if ($meeting['status'] === 'in_progress'): ?>
                    <div class="text-end mt-3">
                        <a href="<?php echo $meeting['meet_link']; ?>" class="btn-join" target="_blank">
                            <i class="fas fa-video"></i>
                            Join Meeting Now
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="timer" data-time="<?php echo $meeting['scheduled_time']; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Time remaining: Calculating...</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateTimers() {
            const timers = document.querySelectorAll('.timer');
            timers.forEach(timer => {
                const scheduledTime = new Date(timer.dataset.time);
                const now = new Date();
                const timeLeft = scheduledTime - now;

                if (timeLeft > 0) {
                    const hours = Math.floor(timeLeft / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    timer.querySelector('span').textContent = `Time remaining: ${hours}h ${minutes}m`;
                } else {
                    timer.querySelector('span').textContent = 'Meeting should have started';
                }
            });
        }

        setInterval(updateTimers, 60000);
        updateTimers();
    </script>
</body>
</html>