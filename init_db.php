<?php
require '../includes/db_connect.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['dump_file'])) {
    // Check for errors
    if ($_FILES['dump_file']['error'] !== UPLOAD_ERR_OK) {
        die("Error uploading file: " . $_FILES['dump_file']['error']);
    }

    // Check file type (should be .sql)
    $file_ext = pathinfo($_FILES['dump_file']['name'], PATHINFO_EXTENSION);
    if (strtolower($file_ext) !== 'sql') {
        die("Only .sql files are allowed.");
    }

    // Read the file content
    $sql = file_get_contents($_FILES['dump_file']['tmp_name']);
    if ($sql === false) {
        die("Could not read the uploaded file.");
    }

    // Execute the SQL queries
    echo "<h2>Initializing Database...</h2>";
    
    // Disable foreign key checks temporarily
    $conn->query('SET FOREIGN_KEY_CHECKS=0');
    
    // Split the SQL file into individual queries
    $queries = explode(';', $sql);
    $success_count = 0;
    $error_count = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
            try {
                if ($conn->query($query)) {
                    $success_count++;
                } else {
                    $error_count++;
                    echo "Error executing query: " . $conn->error . "<br>";
                }
            } catch (Exception $e) {
                $error_count++;
                echo "Error: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // Re-enable foreign key checks
    $conn->query('SET FOREIGN_KEY_CHECKS=1');
    
    echo "<h3>Database initialization complete!</h3>";
    echo "<p>Successful queries: $success_count</p>";
    echo "<p>Failed queries: $error_count</p>";
    exit;
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .container {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 20px;
            border-radius: 5px;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 5px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Initialization</h1>
        
        <div class="warning">
            <strong>Warning:</strong> This will overwrite your current database. 
            Make sure you have a backup before proceeding.
        </div>
        
        <form action="init_db.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="dump_file">Select SQL Dump File:</label>
                <input type="file" name="dump_file" id="dump_file" accept=".sql" required>
            </div>
            
            <button type="submit">Initialize Database</button>
        </form>
    </div>
</body>
</html>