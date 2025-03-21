<!-- <?php
session_start();
require '../includes/db_connect.php';

if (!isset($_SESSION['user_id'])) {
    // header("Location: login.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$task_id = $_GET['task_id'] ?? null;

if (!$task_id) {
    echo "Task not found!";
    exit;
}

$task_query = "SELECT * FROM tasks WHERE task_id = $task_id";
$task_result = $conn->query($task_query);
$task = $task_result->fetch_assoc();

$discussion_query = "SELECT d.*, u.username FROM discussions d 
                     JOIN users u ON d.user_id = u.user_id 
                     WHERE task_id = $task_id ORDER BY posted_at DESC";
$discussion_result = $conn->query($discussion_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Details</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Task: <?= $task['task_title'] ?></h1>
        <p><?= $task['task_description'] ?></p>
        <h2>Discussions</h2>
        <ul>
            <?php while ($discussion = $discussion_result->fetch_assoc()): ?>
                <li><strong><?= $discussion['username'] ?>:</strong> <?= $discussion['message'] ?></li>
            <?php endwhile; ?>
        </ul>
        <form method="POST" action="../actions/community_action.php">
            <textarea name="message" required></textarea>
            <input type="hidden" name="task_id" value="<?= $task_id ?>">
            <button type="submit" class="btn btn-primary">Post</button>
        </form>
    </div>
</body>
</html> -->
