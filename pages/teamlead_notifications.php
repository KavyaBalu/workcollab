<?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
}

$user_id = $_SESSION['user_id'];

// Fetch extension responses from last 2 days
$extensionQuery = "SELECT pe.*, p.title as project_title 
    FROM project_extensions pe 
    JOIN projects p ON pe.project_id = p.project_id 
    WHERE pe.requested_by = ? 
    AND (pe.status = 'approved' OR pe.status = 'rejected')
    AND pe.responded_at >= DATE_SUB(NOW(), INTERVAL 2 DAY)
    ORDER BY pe.responded_at DESC";
$stmt = $conn->prepare($extensionQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$extensionResult = $stmt->get_result();

// Fetch meetings
$meetingQuery = "SELECT * FROM meetings WHERE created_by = ? AND status = 'scheduled' ORDER BY scheduled_time ASC";
$stmt = $conn->prepare($meetingQuery);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$meetingResult = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Lead Notifications | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background: linear-gradient(to right, #2c3e50, #3498db);
            padding: 1rem 0;
        }

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 0.5rem 1rem;
            border-radius: 6px;
        }

        .navbar .back-btn:hover {
            background-color: rgba(255, 255, 255, 0.1);
            color: #ffffff;
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
            display: flex;
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

        .status-approved {
            color: #2ecc71;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(46, 204, 113, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }

        .status-rejected {
            color: #e74c3c;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background-color: rgba(231, 76, 60, 0.1);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
        }

        .timer {
            color: #3498db;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .btn-join {
            background-color: #2ecc71;
            border: none;
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
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <span class="navbar-brand">
                <i class="fas fa-bell"></i>
                Notifications
            </span>
            <a href="teamlead_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
        </div>
    </nav>

    <div class="container notifications-container">
        <!-- Project Extensions Section -->
        <h2 class="section-title">
            <i class="fas fa-project-diagram"></i>
            Project Extension Responses
        </h2>
        
        <?php if ($extensionResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-check"></i>
                <h3>No Extension Responses</h3>
                <p class="text-muted">There are no recent project extension responses to display.</p>
            </div>
        <?php else: ?>
            <?php while ($extension = $extensionResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($extension['project_title']); ?></h5>
                    <span class="status-<?php echo strtolower($extension['status']); ?>">
                        <i class="fas fa-<?php echo $extension['status'] === 'approved' ? 'check-circle' : 'times-circle'; ?>"></i>
                        <?php echo ucfirst($extension['status']); ?>
                    </span>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-comment"></i>
                        <span><?php echo htmlspecialchars($extension['response_note']); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>New Due Date: <?php echo date('F j, Y', strtotime($extension['new_due_date'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span>Responded: <?php echo time_elapsed_string($extension['responded_at']); ?></span>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Meetings Section -->
        <h2 class="section-title">
            <i class="fas fa-calendar-check"></i>
            Upcoming Meetings
        </h2>

        <?php if ($meetingResult->num_rows === 0): ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Upcoming Meetings</h3>
                <p class="text-muted">You have no scheduled meetings at this time.</p>
            </div>
        <?php else: ?>
            <?php while ($meeting = $meetingResult->fetch_assoc()): ?>
            <div class="notification-card">
                <div class="notification-header">
                    <h5 class="notification-title"><?php echo htmlspecialchars($meeting['title']); ?></h5>
                </div>
                <div class="notification-content">
                    <div class="meta-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span><?php echo date('F j, Y', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <i class="fas fa-clock"></i>
                        <span><?php echo date('g:i A', strtotime($meeting['scheduled_time'])); ?></span>
                    </div>
                    <div class="timer" data-time="<?php echo $meeting['scheduled_time']; ?>">
                        <i class="fas fa-hourglass-half"></i>
                        <span>Time remaining: Calculating...</span>
                    </div>
                    <div class="text-end">
                        <a href="view_meetings.php" class="btn-join">
                            <i class="fas fa-video"></i>
                            Join Meeting
                        </a>
                    </div>
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

    <?php
    // Helper function to format time elapsed
    function time_elapsed_string($datetime) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        if ($diff->d == 1) {
            return '1 day ago';
        } elseif ($diff->h >= 1) {
            return $diff->h . ' hours ago';
        } elseif ($diff->i >= 1) {
            return $diff->i . ' minutes ago';
        } else {
            return 'Just now';
        }
    }
    ?>
</body>
</html>