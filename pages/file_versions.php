<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead' && $_SESSION['role'] != 'Manager' ) {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

// Determine back button URL based on role
$backUrl = ($_SESSION['role'] === 'Manager') ? 'manager_file_sharing.php' : 'file_sharing.php';

// Get share_id from URL
if (!isset($_GET['share_id'])) {
    // header("Location: " . $backUrl);
    // exit;
    echo "<script>window.location.href = '" . $backUrl . "';</script>";

}

$share_id = $_GET['share_id'];
$error = "";
$success = "";

// Rest of your PHP logic remains the same until the HTML
// Fetch share details
$query = "SELECT * FROM file_shares WHERE share_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $share_id);
$stmt->execute();
$share = $stmt->get_result()->fetch_assoc();

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
    
    // Get file extension
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Create unique filename
    $original_filename = pathinfo($file['name'], PATHINFO_FILENAME);
    $stored_filename = $original_filename . 'v' . $next_version . '' . uniqid() . '.' . $file_extension;
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
    <title>File Versions | Workspace</title>
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
            max-width: 1000px;
            margin: 2rem auto;
        }

        .content-card {
            background: #ffffff;
            border-radius: 10px;
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .file-header {
            margin-bottom: 2rem;
        }

        .file-title {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .file-description {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .upload-section {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-label {
            color: #2c3e50;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 0.75rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
        }

        .btn-upload {
            background-color: #3498db;
            border-color: #3498db;
            color: #ffffff;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s;
        }

        .btn-upload:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }

        .version-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .version-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .version-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .version-number {
            font-weight: 600;
            color: #2c3e50;
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
        }

        .version-note {
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .btn-download {
            background-color: #2ecc71;
            border-color: #2ecc71;
            color: #ffffff;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }

        .btn-download:hover {
            background-color: #27ae60;
            border-color: #27ae60;
            color: #ffffff;
        }

        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top mb-4">
        <div class="container">
            <a href="<?php echo $backUrl; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Files</span>
            </a>
            <span class="navbar-brand">File Versions</span>
        </div>
    </nav>

    <div class="container main-container">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <div class="file-header">
                <h2 class="file-title"><?= htmlspecialchars($share['title']) ?></h2>
                <p class="file-description"><?= htmlspecialchars($share['description']) ?></p>
            </div>

            <div class="upload-section">
                <h3 class="mb-3">Upload New Version</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="file" class="form-label">Select File</label>
                        <input type="file" class="form-control" id="file" name="file" required>
                    </div>
                    <div class="mb-3">
                        <label for="version_note" class="form-label">Version Notes</label>
                        <textarea class="form-control" id="version_note" name="version_note" rows="3" 
                                  placeholder="Describe the changes in this version" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-upload">
                        <i class="fas fa-upload me-2"></i>
                        Upload Version
                    </button>
                </form>
            </div>

            <h3 class="mb-3">File Versions</h3>
            <?php foreach ($versions as $version): ?>
                <div class="version-item">
                    <div class="version-meta">
                        <span class="version-number">Version <?= htmlspecialchars($version['version_number']) ?></span>
                        <span><i class="far fa-user me-1"></i><?= htmlspecialchars($version['username']) ?></span>
                        <span><i class="far fa-clock me-1"></i><?= date('F j, Y g:i A', strtotime($version['upload_time'])) ?></span>
                    </div>
                    <div class="version-note">
                        <?= htmlspecialchars($version['version_note']) ?>
                    </div>
                    <div>
                        <a href="../uploads/fileshare/<?= urlencode($version['stored_name']) ?>" 
                           class="btn btn-download" 
                           download="<?= htmlspecialchars($version['file_name']) ?>">
                            <i class="fas fa-download"></i>
                            Download File
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>