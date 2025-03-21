-- Create meetings table
CREATE TABLE meetings (
    meeting_id INT NOT NULL AUTO_INCREMENT,
    team_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    scheduled_time DATETIME NOT NULL,
    meet_link VARCHAR(255),
    created_by INT NOT NULL,
    manager_required BOOLEAN DEFAULT 0,
    status ENUM('scheduled', 'in_progress', 'completed') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (meeting_id),
    FOREIGN KEY (team_id) REFERENCES teams(team_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- create_meeting.php (Team Lead's page to create/manage meetings)
<?php
session_start();
require 'db_connect.php';

// Check if user is team lead
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TeamLead') {
    // header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $scheduled_time = $_POST['scheduled_time'];
    $meet_link = $_POST['meet_link'];
    $manager_required = isset($_POST['manager_required']) ? 1 : 0;
    $team_id = $_SESSION['team_id'];
    $created_by = $_SESSION['user_id'];

    $stmt = $conn->prepare("INSERT INTO meetings (team_id, title, description, scheduled_time, meet_link, manager_required, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("issssii", $team_id, $title, $description, $scheduled_time, $meet_link, $manager_required, $created_by);
    $stmt->execute();
}

// Fetch existing meetings for this team lead
$stmt = $conn->prepare("SELECT * FROM meetings WHERE team_id = ? ORDER BY scheduled_time DESC");
$stmt->bind_param("i", $_SESSION['team_id']);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Create Meeting</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h2>Schedule New Meeting</h2>
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label class="form-label">Title:</label>
            <input type="text" name="title" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description:</label>
            <textarea name="description" class="form-control"></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Schedule Time:</label>
            <input type="datetime-local" name="scheduled_time" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Google Meet Link:</label>
            <input type="url" name="meet_link" class="form-control" required>
        </div>
        <div class="mb-3">
            <label class="form-check-label">
                <input type="checkbox" name="manager_required" class="form-check-input">
                Manager Presence Required
            </label>
        </div>
        <button type="submit" class="btn btn-primary">Schedule Meeting</button>
    </form>

    <h3>Scheduled Meetings</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Scheduled Time</th>
                <th>Meet Link</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($meeting = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($meeting['title']) ?></td>
                <td><?= htmlspecialchars($meeting['description']) ?></td>
                <td><?= htmlspecialchars($meeting['scheduled_time']) ?></td>
                <td>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                           id="link_<?= $meeting['meeting_id'] ?>">
                </td>
                <td><?= htmlspecialchars($meeting['status']) ?></td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                            onclick="updateLink(<?= $meeting['meeting_id'] ?>)">Update Link</button>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

    <script>
    function updateLink(meetingId) {
        const newLink = document.getElementById('link_' + meetingId).value;
        fetch('update_meeting_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `meeting_id=${meetingId}&meet_link=${encodeURIComponent(newLink)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Link updated successfully!');
            } else {
                alert('Error updating link');
            }
        });
    }
    </script>
</body>
</html>

-- view_meetings.php (Common page for team members and team lead)
<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

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

<!DOCTYPE html>
<html>
<head>
    <title>View Meetings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h2>Team Meetings</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Title</th>
                <th>Description</th>
                <th>Scheduled Time</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($meeting = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($meeting['title']) ?></td>
                <td><?= htmlspecialchars($meeting['description']) ?></td>
                <td><?= htmlspecialchars($meeting['scheduled_time']) ?></td>
                <td><?= htmlspecialchars($meeting['creator_name']) ?></td>
                <td><?= htmlspecialchars($meeting['status']) ?></td>
                <td>
                    <?php if ($_SESSION['role'] === 'TeamLead'): ?>
                        <?php if ($meeting['status'] === 'scheduled'): ?>
                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                               onclick="startMeeting(<?= $meeting['meeting_id'] ?>)"
                               class="btn btn-primary btn-sm">Start Meeting</a>
                        <?php else: ?>
                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                               class="btn btn-success btn-sm">Join Meeting</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <?php if ($meeting['status'] === 'in_progress'): ?>
                            <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                               class="btn btn-success btn-sm">Join Meeting</a>
                        <?php else: ?>
                            <button class="btn btn-secondary btn-sm" disabled>Waiting to Start</button>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>

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

-- manager_meetings.php (Manager's view of required meetings)
<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Manager') {
    header('Location: login.php');
    exit();
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
<html>
<head>
    <title>Manager Meetings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container mt-4">
    <h2>Meetings Requiring Manager Presence</h2>
    <table class="table">
        <thead>
            <tr>
                <th>Team</th>
                <th>Title</th>
                <th>Description</th>
                <th>Scheduled Time</th>
                <th>Created By</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($meeting = $result->fetch_assoc()): ?>
            <tr>
                <td><?= htmlspecialchars($meeting['team_name']) ?></td>
                <td><?= htmlspecialchars($meeting['title']) ?></td>
                <td><?= htmlspecialchars($meeting['description']) ?></td>
                <td><?= htmlspecialchars($meeting['scheduled_time']) ?></td>
                <td><?= htmlspecialchars($meeting['creator_name']) ?></td>
                <td><?= htmlspecialchars($meeting['status']) ?></td>
                <td>
                    <?php if ($meeting['status'] === 'in_progress'): ?>
                        <a href="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                           class="btn btn-success btn-sm">Join Meeting</a>
                    <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>Waiting to Start</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>
</html>

-- update_meeting_status.php (Handle meeting status updates)
<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'TeamLead') {
    $meeting_id = $_POST['meeting_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE meetings SET status = ? WHERE meeting_id = ? AND team_id = ?");
    $stmt->bind_param("sii", $status, $meeting_id, $_SESSION['team_id']);
    $success = $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
}

-- update_meeting_link.php (Handle meeting link updates)
<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'TeamLead') {
    $meeting_id = $_POST['meeting_id'];
    $meet_link = $_POST['meet_link'];

    $stmt = $conn->prepare("UPDATE meetings SET meet_link = ? WHERE meeting_id = ? AND team_id = ?");
    $stmt->bind_param("sii", $meet_link, $meeting_id, $_SESSION['team_id']);
    $success = $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
}