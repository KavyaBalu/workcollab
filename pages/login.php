<?php
session_start();
require '../includes/db_connect.php';

// Check if the user is already logged in and redirect based on role
if (isset($_SESSION['role'])) {
    $role = strtolower($_SESSION['role']);
    switch($role) {
        case 'hr':
            echo "<script>window.location.href = 'hr_dashboard.php';</script>";
            break;
        case 'manager':
            echo "<script>window.location.href = 'manager_dashboard.php';</script>";
            break;
        case 'teamlead':
            echo "<script>window.location.href = 'teamlead_dashboard.php';</script>";
            break;
        case 'teammember':
            echo "<script>window.location.href = 'teammember_dashboard.php';</script>";
            break;
        default:
            echo "<script>window.location.href = 'dashboard.php';</script>";
    }
    exit;
    
}

$error = "";
$redirect_url = "";

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.role, u.password
        FROM users u
        WHERE u.email = ?
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->bind_result($user_id, $username, $role, $hashed_password);
    $stmt->fetch();
    $stmt->close();

    if ($role) {
        if ($role !== 'TeamLead') {
            $stmt = $conn->prepare("
                SELECT tm.team_id
                FROM team_members tm
                WHERE tm.user_id = ?
            ");
        } else {
            $stmt = $conn->prepare("
                SELECT t.team_id
                FROM teams t
                WHERE t.team_lead_id = ?
            ");
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($team_id);
        $stmt->fetch();
        $stmt->close();

        if (password_verify($password, $hashed_password)) {
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;
            $_SESSION['username'] = $username;
            $_SESSION['team_id'] = !empty($team_id) ? $team_id : null;

            switch(strtolower($role)) {
                case 'hr':
                    $redirect_url = 'hr_dashboard.php';
                    break;
                case 'manager':
                    $redirect_url = 'manager_dashboard.php';
                    break;
                case 'teamlead':
                    $redirect_url = 'teamlead_dashboard.php';
                    break;
                case 'teammember':
                    $redirect_url = 'teammember_dashboard.php';
                    break;
                default:
                    $redirect_url = 'dashboard.php';
            }
            
            echo json_encode(['success' => true, 'redirect' => $redirect_url]);
            exit();
        } else {
            $error = "Invalid email or password!";
        }
    } else {
        $error = "No such user found with that email!";
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

            <button type="submit" class="submit-btn">
                <i class="fas fa-sign-in-alt me-2"></i>
                Sign In
            </button>
        </form>

        <a href="index.php" class="back-to-home">
            <i class="fas fa-arrow-left me-1"></i>
            Back to Home
        </a>
    </div>

    <script>
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const errorDiv = document.getElementById('error-message');
            const loginContainer = document.querySelector('.login-container');
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                try {
                    const result = JSON.parse(data);
                    if (result.success && result.redirect) {
                        window.location.href = result.redirect;
                    }
                } catch(e) {
                    errorDiv.style.display = 'block';
                    errorDiv.querySelector('span').textContent = 
                        data.includes("Invalid email or password!") ? "Invalid email or password!" :
                        data.includes("No such user") ? "No such user found with that email!" :
                        "An error occurred. Please try again.";
                    
                    loginContainer.classList.add('shake');
                    setTimeout(() => loginContainer.classList.remove('shake'), 500);
                }
            })
            .catch(error => {
                errorDiv.style.display = 'block';
                errorDiv.querySelector('span').textContent = "An error occurred. Please try again.";
                loginContainer.classList.add('shake');
                setTimeout(() => loginContainer.classList.remove('shake'), 500);
            });
        });
    </script>
</body>
</html>