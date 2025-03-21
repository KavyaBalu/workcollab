<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'Manager') {
    header("Location: error.php");
    exit;
}

// Get share_id from URL
if (!isset($_GET['share_id'])) {
    header("Location: manager_file_sharing.php");
    exit;
}

$share_id = $_GET['share_id'];
$error = "";
$success = "";

// Verify this share belongs to the manager
$query = "SELECT * FROM file_shares WHERE share_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $share_id, $_SESSION['user_id']);
$stmt->execute();
$share = $stmt->get_result()->fetch_assoc();

if (!$share) {
    header("Location: error.php");
    exit;
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $version_note = $_POST['version_note'];
    $file = $_FILES['file'];
    
    // Get the latest version number
    $query = "SELECT MAX(version_number) as max_version FROM file_versions WHERE share_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $share_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $next_version = ($result['max_version'] ?? 0) + 1;
    
    // Get file extension and create unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $stored_filename = $original_filename . '_v' . $next_version . '_' . uniqid() . '.' . $file_extension;
    $upload_path = '../uploads/fileshare/' . $stored_filename;
    
    // Create directory if it doesn't exist
    if (!file_exists('../uploads/fileshare')) {
        mkdir('../uploads/fileshare', 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Insert file version into database
        $query = "INSERT INTO file_versions (share_id, version_number, file_name, stored_name, 
                  uploaded_by, username, version_note) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iississ", $share_id, $next_version, $file['name'], 
                         $stored_filename, $_SESSION['user_id'], $_SESSION['username'], $version_note);
        
        if ($stmt->execute()) {
            $success = "File uploaded successfully as version " . $next_version;
        } else {
            $error = "Database error while saving file version";
        }
    } else {
        $error = "Failed to upload file";
    }
}

// Fetch all versions of this share
$query = "SELECT * FROM file_versions WHERE share_id = ? ORDER BY version_number DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $share_id);
$stmt->execute();
$versions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Versions - <?= htmlspecialchars($share['title']) ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .version-list {
            margin-top: 20px;
        }
        .version-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }
        .version-item:hover {
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .version-meta {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }
        .version-note {
            margin: 10px 0;
            color: #333;
        }
        .upload-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }