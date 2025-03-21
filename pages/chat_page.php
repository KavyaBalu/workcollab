<?php
session_start();
require '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['username']) || !isset($_SESSION['role'])) {
    echo "<script>window.location.href = 'login.php';</script>";
    exit;
}

// Ensure only team members can access this page
if ($_SESSION['role'] != 'TeamMember' && $_SESSION['role'] != 'TeamLead') {
    echo "<script>window.location.href = 'error.php';</script>";
    exit;
}

// Get the discussion_id from the URL
if (!isset($_GET['discussion_id'])) {
    echo "<script>window.location.href = 'discussion.php';</script>";
    exit;
}

$discussion_id = $_GET['discussion_id'];
$query = "SELECT chat_id, username, chat_msg, time FROM chat WHERE discussion_id = ? ORDER BY time ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $discussion_id);
$stmt->execute();
$result = $stmt->get_result();
$chats = [];
while ($row = $result->fetch_assoc()) {
    $chats[] = $row;
}
$stmt->close();

// Handle new chat message submission via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_msg'])) {
    $chat_msg = $_POST['chat_msg'];
    $username = $_SESSION['username'];
    $query = "INSERT INTO chat (discussion_id, chat_msg, username, time) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iss", $discussion_id, $chat_msg, $username);
    
    if ($stmt->execute()) {
        $last_id = $stmt->insert_id;
        $stmt->close();
        
        // For AJAX requests
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $response = [
                'success' => true,
                'message' => [
                    'chat_id' => $last_id,
                    'username' => $username,
                    'chat_msg' => $chat_msg,
                    'time' => date('g:i A')
                ]
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        } else {
            // For regular form submissions
            echo "<script>window.location.href = 'chat_page.php?discussion_id=" . $discussion_id . "';</script>";
            exit;
        }
    }
}

// Handle AJAX request for new messages
if (isset($_GET['action']) && $_GET['action'] === 'fetch_new_messages') {
    $last_message_id = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
    
    $query = "SELECT chat_id, username, chat_msg, time FROM chat 
              WHERE discussion_id = ? AND chat_id > ? 
              ORDER BY time ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $discussion_id, $last_message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $new_messages = [];
    while ($row = $result->fetch_assoc()) {
        $row['time'] = date('g:i A', strtotime($row['time']));
        $new_messages[] = $row;
    }
    
    header('Content-Type: application/json');
    echo json_encode($new_messages);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team Discussion</title>
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

        .message.new {
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            <h1>Team Discussion</h1>
            <a href="discussion.php" class="back-button">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Discussions</span>
            </a>
        </div>

        <div class="chat-messages" id="chat-messages">
            <?php foreach ($chats as $chat): ?>
                <div class="message <?= ($chat['username'] == $_SESSION['username']) ? 'sent' : 'received' ?>" 
                     data-message-id="<?= $chat['chat_id'] ?>">
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
            <form class="chat-form" id="chat-form" method="POST">
                <textarea name="chat_msg" placeholder="Type your message..." required></textarea>
                <button type="submit" class="send-button">
                    <i class="fas fa-paper-plane"></i>
                    <span>Send</span>
                </button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const chatMessages = document.getElementById('chat-messages');
            const chatForm = document.getElementById('chat-form');
            const currentUsername = '<?= $_SESSION['username'] ?>';
            const discussionId = '<?= $discussion_id ?>';
            let lastMessageId = getLastMessageId();

            // Scroll to bottom on load
            scrollToBottom();

            // Handle form submission
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(chatForm);
                
                fetch(`chat_page.php?discussion_id=${discussionId}`, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addMessageToChat(data.message, true);
                        chatForm.reset();
                        scrollToBottom();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Fallback to traditional form submission
                    chatForm.submit();
                });
            });

            // Fetch new messages periodically
            setInterval(fetchNewMessages, 1000);

            function fetchNewMessages() {
                fetch(`chat_page.php?discussion_id=${discussionId}&action=fetch_new_messages&last_id=${lastMessageId}`)
                    .then(response => response.json())
                    .then(messages => {
                        messages.forEach(message => {
                            if (message.username !== currentUsername) {
                                addMessageToChat(message);
                            }
                        });
                        if (messages.length > 0) {
                            scrollToBottom();
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }

            function addMessageToChat(message, isNew = false) {
                const messageDiv = document.createElement('div');
                messageDiv.className = `message ${message.username === currentUsername ? 'sent' : 'received'}`;
                if (isNew) messageDiv.classList.add('new');
                messageDiv.dataset.messageId = message.chat_id;

                messageDiv.innerHTML = `
                    <div class="message-header">
                        <strong>${escapeHtml(message.username)}</strong>
                        <span>${message.time}</span>
                    </div>
                    <div class="message-content">
                        ${escapeHtml(message.chat_msg).replace(/\n/g, '<br>')}
                    </div>
                `;

                chatMessages.appendChild(messageDiv);
                lastMessageId = Math.max(lastMessageId, message.chat_id);
            }

            function getLastMessageId() {
                const messages = document.querySelectorAll('.message');
                if (messages.length === 0) return 0;
                const lastMessage = messages[messages.length - 1];
                return parseInt(lastMessage.dataset.messageId) || 0;
            }

            function scrollToBottom() {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }

            function escapeHtml(unsafe) {
                return unsafe
                    .replace(/&/g, "&amp;")
                    .replace(/</g, "&lt;")
                    .replace(/>/g, "&gt;")
                    .replace(/"/g, "&quot;")
                    .replace(/'/g, "&#039;");
            }
        });
    </script>
</body>
</html>