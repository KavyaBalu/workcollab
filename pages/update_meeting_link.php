<?php
session_start();
require '../includes/db_connect.php';

// Check if user is team lead
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TeamLead') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $meeting_id = $_POST['meeting_id'];
    $meet_link = $_POST['meet_link'];
    
    // Validate the meeting belongs to the team
    $stmt = $conn->prepare("UPDATE meetings SET meet_link = ? WHERE meeting_id = ? AND team_id = ?");
    $stmt->bind_param("sii", $meet_link, $meeting_id, $_SESSION['team_id']);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>