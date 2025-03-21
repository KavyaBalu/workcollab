<?php
session_start();
require '../includes/db_connect.php';

// Ensure only managers can access this page
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Manager') {
    echo "<script>window.location.href = 'login.php';</script>";
}

// Initialize variables
$discussion_id = null;
$chats = [];
$error_message = '';

// Get the discussion_id from the URL
if (isset($_GET['discussion_id'])) {
    $discussion_id = filter_var($_GET['discussion_id'], FILTER_VALIDATE_INT);
    
    if ($discussion_id === false || $discussion_id === null) {
        echo "<script>window.location.href = 'error.php';</script>";
    }

    // Fetch all chat messages for the discussion_id along with the username
    try {
        $query = "SELECT chat_id, username, chat_msg, time 
                 FROM chat 
                 WHERE discussion_id = ? 
                 ORDER BY time ASC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $discussion_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $chats[] = $row;
        }
        $stmt->close();
    } catch (Exception $e) {
        $error_message = "Error loading chat messages.";
        error_log("Database error: " . $e->getMessage());
    }
}

// Handle new chat message submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_msg']) && $discussion_id) {
    $chat_msg = trim($_POST['chat_msg']);
    $username = $_SESSION['username'];

    if (!empty($chat_msg)) {
        try {
            $query = "INSERT INTO chat (discussion_id, chat_msg, username, time) 
                     VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $discussion_id, $chat_msg, $username);
            
            if ($stmt->execute()) {
                echo "<script>window.location.href = 'manager_chat.php?discussion_id=" . $discussion_id . "';</script>";
                exit;
            } else {
                $error_message = "Failed to send message.";
            }
            $stmt->close();
        } catch (Exception $e) {
            $error_message = "Error sending message.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Discussion</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #3b82f6;
            --background-color: #f3f4f6;
            --chat-bg-sent: #2563eb;
            --chat-bg-received: #f3f4f6;
            --text-primary: #1f2937;
            --text-secondary: #6b7280;
            --border-color: #e5e7eb;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--background-color);
            color: var(--text-primary);
            line-height: 1.5;
        }

        .chat-container {
            max-width: 1000px;
            margin: 2rem auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            height: calc(100vh - 4rem);
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            background-color: white;
            border-bottom: 1px solid var(--border-color);
        }

        .chat-header h1 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--secondary-color);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s ease;
        }

        .back-button:hover {
            color: var(--primary-color);
        }

        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            max-width: 70%;
            padding: 1rem;
            border-radius: 12px;
            position: relative;
            font-size: 0.95rem;
        }

        .message.sent {
            background-color: var(--chat-bg-sent);
            color: white;
            margin-left: auto;
            border-bottom-right-radius: 4px;
        }

        .message.received {
            background-color: var(--chat-bg-received);
            color: var(--text-primary);
            margin-right: auto;
            border-bottom-left-radius: 4px;
        }

        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .message.sent .message-header {
            color: rgba(255, 255, 255, 0.9);
        }

        .message.received .message-header {
            color: var(--text-secondary);
        }

        .message-content {
            line-height: 1.5;
            word-wrap: break-word;
        }

        .chat-input {
            padding: 1.5rem;
            background-color: white;
            border-top: 1px solid var(--border-color);
        }

        .chat-form {
            display: flex;
            gap: 1rem;
        }

        .chat-form textarea {
            flex: 1;
            padding: 0.875rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            resize: none;
            height: 100px;
            font-family: inherit;
            font-size: 0.95rem;
            transition: border-color 0.2s ease;
        }

        .chat-form textarea:focus {
            outline: none;
            border-color: var(--secondary-color);
        }

        .send-button {
            padding: 0 1.5rem;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .send-button:hover {
            background-color: var(--secondary-color);
        }

        .send-button i {
            font-size: 1.1rem;
        }

        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 1rem;
            margin: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .chat-container {
                margin: 0;
                height: 100vh;
                border-radius: 0;
            }

            .message {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1>Manager Discussion</h1>
            <a href="manager_discussion.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Discussions</span>
            </a>
        </div>

        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="chat-messages">
            <?php foreach ($chats as $chat): ?>
                <div class="message <?= ($chat['username'] == $_SESSION['username']) ? 'sent' : 'received' ?>">
                    <div class="message-header">
                        <strong><?= htmlspecialchars($chat['username']) ?></strong>
                        <span><?= date('g:i A', strtotime($chat['time'])) ?></span>
                    </div>
                    <div class="message-content">
                        <?= nl2br(htmlspecialchars($chat['chat_msg'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="chat-input">
            <form class="chat-form" method="POST" action="manager_chat.php?discussion_id=<?= urlencode($discussion_id) ?>">
                <textarea name="chat_msg" placeholder="Type your message..." required></textarea>
                <button type="submit" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send</span>
                </button>
            </form>
        </div>
    </div>
</body>
</html>