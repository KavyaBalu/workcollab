<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'Manager') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $is_project_file = isset($_POST['is_project_file']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];
    $username = $_SESSION['username'];

    $query = "INSERT INTO file_shares (title, description, user_id, username, is_project_file) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssi", $title, $description, $user_id, $username, $is_project_file);

    if ($stmt->execute()) {
        // header("Location: manager_file_sharing.php");
        // exit;
        echo "<script>window.location.href = 'manager_file_sharing.php';</script>";
    } else {
        $error = "Failed to create file share. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Manager File | Workspace</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
            min-height: 100vh;
        }

        .navbar {
            background-color: #2c3e50;
            padding: 1rem 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .navbar .back-btn:hover {
            color: #3498db;
            transform: translateX(-4px);
        }

        .navbar-brand {
            color: #ffffff;
            font-weight: 600;
            margin-left: 1rem;
        }

        .main-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .card {
            background: #ffffff;
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.1);
        }

        .card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 1.5rem;
        }

        .card-title {
            color: #2c3e50;
            font-weight: 600;
            font-size: 1.5rem;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .form-label {
            color: #34495e;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            border: 1px solid rgba(0,0,0,0.1);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52,152,219,0.1);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .form-check {
            padding-left: 2rem;
        }

        .form-check-input {
            border: 2px solid rgba(0,0,0,0.2);
            width: 1.25rem;
            height: 1.25rem;
            margin-left: -2rem;
            cursor: pointer;
        }

        .form-check-input:checked {
            background-color: #2ecc71;
            border-color: #2ecc71;
        }

        .form-check-label {
            cursor: pointer;
            padding-top: 0.2rem;
            color: #34495e;
        }

        .btn-submit {
            background-color: #3498db;
            border: none;
            padding: 0.875rem 2rem;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-submit:hover {
            background-color: #2980b9;
            transform: translateY(-1px);
        }

        .error-alert {
            background-color: #fde8e8;
            border-left: 4px solid #e74c3c;
            color: #c0392b;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        @media (max-width: 768px) {
            .main-container {
                margin: 1rem auto;
            }
            
            .card {
                border-radius: 8px;
            }
            
            .card-header {
                padding: 1.25rem;
            }
            
            .card-body {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a href="manager_file_sharing.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Repository</span>
            </a>
            <span class="navbar-brand">File Upload</span>
        </div>
    </nav>

    <div class="container main-container">
        <div class="card">
            <div class="card-header">
                <h1 class="card-title">Upload New File</h1>
            </div>
            
            <div class="card-body">
                <?php if (!empty($error)): ?>
                    <div class="error-alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" class="needs-validation" novalidate>
                    <div class="mb-4">
                        <label for="title" class="form-label">File Title</label>
                        <input type="text" 
                               class="form-control" 
                               id="title" 
                               name="title" 
                               required 
                               placeholder="Enter a descriptive title for your file"
                               autocomplete="off">
                    </div>

                    <div class="mb-4">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" 
                                  id="description" 
                                  name="description" 
                                  required
                                  placeholder="Provide details about the file and its purpose"></textarea>
                    </div>

                    <div class="mb-4">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="is_project_file" 
                                   name="is_project_file">
                            <label class="form-check-label" for="is_project_file">
                                Mark as Project File
                            </label>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-submit">
                            <i class="fas fa-upload"></i>
                            Create File Share
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms).forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!form.checkValidity()) {
                        event.preventDefault()
                        event.stopPropagation()
                    }
                    form.classList.add('was-validated')
                }, false)
            })
        })()
    </script>
</body>
</html>