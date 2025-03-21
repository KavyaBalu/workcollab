<?php
session_start();
require '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['role']) && $_SESSION['role'] === 'TeamLead') {
    $meeting_id = $_POST['meeting_id'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE meetings SET status = ? WHERE meeting_id = ? AND team_id = ?");
    $stmt->bind_param("sii", $status, $meeting_id, $_SESSION['team_id']);
    $success = $stmt->execute();

    header('Content-Type: application/json');
    echo json_encode(['success' => $success]);
}
?>