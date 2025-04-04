<?php
require 'includes/db_connect.php';

// Set character set to handle special characters
$conn->set_charset("utf8mb4");

function restoreDatabase($conn, $sqlFile) {
    // Disable foreign key checks temporarily
    $conn->query('SET FOREIGN_KEY_CHECKS=0');
    $conn->query('SET NAMES utf8mb4');
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    // Read the entire file
    $sql = file_get_contents($sqlFile);
    
    // Remove comments
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    $sql = preg_replace('/--.*?[\r\n]/', '', $sql);
    
    // Split into individual queries
    $queries = explode(";\n", $sql);
    
    foreach ($queries as $query) {
        $query = trim($query);
        
        if (!empty($query)) {
            try {
                // Remove any remaining newlines that might break the query
                $query = str_replace(["\r", "\n"], ' ', $query);
                
                // Fix escaped quotes if they exist
                $query = stripslashes($query);
                
                if ($conn->query($query)) {
                    $success_count++;
                } else {
                    $error_count++;
                    $errors[] = [
                        'query' => substr($query, 0, 100) . (strlen($query) > 100 ? '...' : ''),
                        'error' => $conn->error
                    ];
                }
            } catch (Exception $e) {
                $error_count++;
                $errors[] = [
                    'query' => substr($query, 0, 100) . (strlen($query) > 100 ? '...' : ''),
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
    
    return [
        'success' => $success_count,
        'errors' => $error_count,
        'details' => $errors
    ];
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dump_file'])) {
    // Check for errors
    if ($_FILES['dump_file']['error'] !== UPLOAD_ERR_OK) {
        die("<div class='error'>Error uploading file: " . getUploadError($_FILES['dump_file']['error']) . "</div>");
    }

    // Check file type (should be .sql)
    $file_ext = pathinfo($_FILES['dump_file']['name'], PATHINFO_EXTENSION);
    if (strtolower($file_ext) !== 'sql') {
        die("<div class='error'>Only .sql files are allowed.</div>");
    }

    // Check file size (limit to 10MB)
    if ($_FILES['dump_file']['size'] > 10 * 1024 * 1024) {
        die("<div class='error'>File size exceeds 10MB limit.</div>");
    }

    // Execute the SQL queries
    echo "<h2>Initializing Database...</h2>";
    
    $result = restoreDatabase($conn, $_FILES['dump_file']['tmp_name']);
    
    // Display results
    echo "<div class='results'>";
    echo "<h3>Database initialization complete!</h3>";
    echo "<p>Successful queries: <span class='success'>" . $result['success'] . "</span></p>";
    echo "<p>Failed queries: <span class='error'>" . $result['errors'] . "</span></p>";
    
    if ($result['errors'] > 0) {
        echo "<h4>Error Details:</h4>";
        echo "<table class='error-table'>";
        echo "<tr><th>Query (partial)</th><th>Error</th></tr>";
        foreach ($result['details'] as $error) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($error['query']) . "</td>";
            echo "<td>" . htmlspecialchars($error['error']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    exit;
}

function getUploadError($code) {
    $errors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension',
    ];
    return $errors[$code] ?? 'Unknown upload error';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Initialization</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #fff;
            border: 1px solid #ddd;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .error {
            background-color: #f8d7da;
            border-left: 5px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: #721c24;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error-count {
            color: #dc3545;
            font-weight: bold;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            font-size: 16px;
        }
        input[type="file"] {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
            font-size: 16px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #45a049;
        }
        .results {
            margin-top: 30px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        .error-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        .error-table th, .error-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .error-table th {
            background-color: #f8d7da;
            color: #721c24;
        }
        .error-table tr:hover {
            background-color: #f5f5f5;
        }
        h1, h2, h3, h4 {
            color: #343a40;
        }
        h1 {
            margin-top: 0;
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Initialization</h1>
        
        <div class="warning">
            <strong>Warning:</strong> This will overwrite your current database. 
            Make sure you have a backup before proceeding. Maximum file size: 10MB.
        </div>
        
        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="dump_file">Select SQL Dump File:</label>
                <input type="file" name="dump_file" id="dump_file" accept=".sql" required>
            </div>
            
            <button type="submit">Initialize Database</button>
        </form>
    </div>
</body>
</html>