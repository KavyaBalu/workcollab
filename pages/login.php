<?php
session_start();
require '../includes/db_connect.php';

// Initialize variables
$login_response = ['success' => false, 'message' => '', 'redirect' => ''];

// Check if user is already logged in (return JSON for AJAX handling)
if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    $login_response['redirect'] = match($role) {
        'hr' => 'hr_dashboard.php',
        'manager' => 'manager_dashboard.php',
        'teamlead' => 'teamlead_dashboard.php',
        'teammember' => 'teammember_dashboard.php',
        default => 'dashboard.php'
    };
    $login_response['success'] = true;
    echo json_encode($login_response);
    exit();
}

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Set higher timeout for cloud database
        $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);
        $conn->options(MYSQLI_OPT_READ_TIMEOUT, 10);

        $stmt = $conn->prepare("SELECT user_id, username, role, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Database query failed");
        }

        $stmt->bind_result($user_id, $username, $role, $hashed_password);
        $fetched = $stmt->fetch();
        $stmt->close();

        if (!$fetched || !$role) {
            $login_response['message'] = "Invalid email or password!";
            echo json_encode($login_response);
            exit();
        }

        if (!password_verify($password, $hashed_password)) {
            $login_response['message'] = "Invalid email or password!";
            echo json_encode($login_response);
            exit();
        }

        // Get team information with timeout handling
        $team_id = null;
        if ($role !== 'TeamLead') {
            $stmt = $conn->prepare("SELECT team_id FROM team_members WHERE user_id = ?");
        } else {
            $stmt = $conn->prepare("SELECT team_id FROM teams WHERE team_lead_id = ?");
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($team_id);
        $stmt->fetch();
        $stmt->close();

        // Set session variables
        $_SESSION['user_id'] = $user_id;
        $_SESSION['role'] = $role;
        $_SESSION['username'] = $username;
        $_SESSION['team_id'] = $team_id;

        $login_response['success'] = true;
        $login_response['redirect'] = match(strtolower($role)) {
            'hr' => 'hr_dashboard.php',
            'manager' => 'manager_dashboard.php',
            'teamlead' => 'teamlead_dashboard.php',
            'teammember' => 'teammember_dashboard.php',
            default => 'dashboard.php'
        };

        echo json_encode($login_response);
        exit();

    } catch (Exception $e) {
        $login_response['message'] = "Server error. Please try again later.";
        echo json_encode($login_response);
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | WorkCollab</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
         :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #64748b;
            --background: #f1f5f9;
            --error: #ef4444;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #1e293b, #334155);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .login-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 440px;
            padding: 2rem;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo i {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .logo h1 {
            font-size: 1.5rem;
            color: #0f172a;
            font-weight: 700;
            margin: 0;
        }

        .logo p {
            color: var(--secondary);
            margin: 0;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #334155;
            font-weight: 500;
        }

        .form-group i {
            position: absolute;
            left: 1rem;
            top: 2.7rem;
            color: var(--secondary);
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .submit-btn {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 0.875rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .submit-btn:hover {
            background: var(--primary-dark);
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fee2e2;
            color: var(--error);
            padding: 0.75rem 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: none;
        }

        .error-message i {
            margin-right: 0.5rem;
        }

        .back-to-home {
            display: block;
            text-align: center;
            margin-top: 1.5rem;
            color: var(--secondary);
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-to-home:hover {
            color: var(--primary);
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }

        .shake {
            animation: shake 0.5s ease-in-out;
        }
        /* Your existing CSS styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            color: white;
            font-size: 1.5rem;
        }
        
        .spinner {
            width: 3rem;
            height: 3rem;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-users-gear"></i>
            <h1>WorkCollab</h1>
            <p>Welcome back!</p>
        </div>

        <div id="error-message" class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <span></span>
        </div>

        <form id="loginForm" method="POST">
            <div class="form-group">
                <label for="email">Email address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Enter your email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" class="form-control" id="password" name="password" required
                       placeholder="Enter your password">
            </div>

            <button type="submit" class="submit-btn" id="submitBtn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Sign In
            </button>
        </form>

        <a href="./index.php" class="back-to-home">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Home
        </a>
    </div>

    <script>
        // Check if already logged in when page loads
        document.addEventListener('DOMContentLoaded', function() {
            fetch('', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.redirect) {
                    showLoading();
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                }
            })
            .catch(() => { /* Ignore errors for this check */ });
        });

        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Signing In...';
            
            showLoading();
            
            fetch('', {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                if (data.success && data.redirect) {
                    // Success - redirect after short delay
                    setTimeout(() => {
                        window.location.href = data.redirect;
                    }, 500);
                } else {
                    // Show error message
                    hideLoading();
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Sign In';
                    
                    const errorDiv = document.getElementById('error-message');
                    errorDiv.style.display = 'block';
                    errorDiv.querySelector('span').textContent = data.message || 'Invalid email or password!';
                    
                    document.querySelector('.login-container').classList.add('shake');
                    setTimeout(() => {
                        document.querySelector('.login-container').classList.remove('shake');
                    }, 500);
                }
            })
            .catch(error => {
                hideLoading();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-sign-in-alt me-2"></i> Sign In';
                
                const errorDiv = document.getElementById('error-message');
                errorDiv.style.display = 'block';
                errorDiv.querySelector('span').textContent = 'Network error. Please try again.';
                
                document.querySelector('.login-container').classList.add('shake');
                setTimeout(() => {
                    document.querySelector('.login-container').classList.remove('shake');
                }, 500);
            });
        });

        function showLoading() {
            const overlay = document.createElement('div');
            overlay.className = 'loading-overlay';
            overlay.id = 'loadingOverlay';
            overlay.innerHTML = `
                <div class="spinner"></div>
                <div>Logging in...</div>
            `;
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
    </script>
</body>
</html>
