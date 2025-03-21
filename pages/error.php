<?php
session_start();

// Destroy the session completely
$_SESSION = array();
session_destroy();

// Clear the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Error Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background-color: #f0f0f0;
        }
        .error-container {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }
        .error-container h1 {
            color: #d9534f;
        }
        .login-btn {
            background-color: #5cb85c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <h1>Oops! Something Went Wrong</h1>
        <p>We encountered an issue with your session. Please try to log in again.</p>
        <button class="login-btn" onclick="redirectToLogin()">Return to Login</button>
    </div>

    <script>
    function redirectToLogin() {
        // Redirect to login page
        window.location.href = 'login.php';
    }
    </script>
</body>
</html>