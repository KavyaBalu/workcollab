<?php
session_start();
require '../includes/db_connect.php';

// Check if user is team lead
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TeamLead') {
    // header('Location: login.php');
    // exit();
    echo "<script>window.location.href = 'login.php';</script>";
}

// Check if ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error_message'] = "Meeting ID not provided!";
    // header('Location: meeting_scheduler.php');
    // exit();
    echo "<script>window.location.href = 'meeting_scheduler.php';</script>";
}

$meeting_id = $_GET['id'];

// Delete the meeting
$delete_stmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ? AND team_id = ?");
$delete_stmt->bind_param("ii", $meeting_id, $_SESSION['team_id']);

if ($delete_stmt->execute()) {
    $_SESSION['success_message'] = "Meeting deleted successfully!";
} else {
    $_SESSION['error_message'] = "Error deleting meeting: " . $conn->error;
}

$delete_stmt->close();

// Redirect back to meeting scheduler
?>
<script>
    window.location.href = 'create_meeting.php';
</script>