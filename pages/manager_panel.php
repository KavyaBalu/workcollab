<?php
session_start();
require '../includes/db_connect.php';

if ($_SESSION['role'] != 'Manager') {
    // header("Location: error.php");
    // exit;
    echo "<script>window.location.href = 'error.php';</script>";
}

$success_message = '';
$error_message = '';

// Handle team creation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $team_name = $_POST['team_name'];
    $team_lead_id = $_POST['team_lead_id'];

    $query = "INSERT INTO teams (team_name, team_lead_id) VALUES (?, ?)";
    $stmt = $conn->prepare($query);
    if ($stmt) {
        $stmt->bind_param("si", $team_name, $team_lead_id);
        if ($stmt->execute()) {
            $success_message = "Team created successfully!";
        } else {
            $error_message = "Failed to create team: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Failed to prepare statement: " . $conn->error;
    }
}

// Fetch all team leads
$team_leads_query = "SELECT user_id, username FROM users WHERE role = 'TeamLead'";
$team_leads_result = $conn->query($team_leads_query);

// Fetch all teams
$teams_query = "
    SELECT t.team_id, t.team_name, u.username AS team_lead 
    FROM teams t 
    JOIN users u ON t.team_lead_id = u.user_id
";
$teams_result = $conn->query($teams_query);

// Fetch task statuses for the pie chart
$status_query = "SELECT status, COUNT(*) AS count FROM tasks GROUP BY status";
$status_result = $conn->query($status_query);

$task_statuses = [];
while ($row = $status_result->fetch_assoc()) {
    $task_statuses[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Panel</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(to bottom right, #f7f8fc, #d3d9e4);
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.85);
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        h1, h2 {
            color: #333;
            text-align: center;
        }
        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: rgba(72, 239, 128, 0.8);
            color: #155724;
        }
        .alert-danger {
            background-color: rgba(245, 104, 100, 0.8);
            color: #721c24;
        }
        form {
            margin: 20px 0;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        form .form-control, form .btn {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        form .btn {
            background: #007bff;
            color: white;
            transition: all 0.3s ease;
        }
        form .btn:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table thead th {
            background: rgba(0, 123, 255, 0.9);
            color: white;
            padding: 10px;
        }
        table tbody tr {
            transition: background 0.3s ease;
        }
        table tbody tr:hover {
            background: rgba(0, 123, 255, 0.1);
        }
        table tbody td {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }
        .btn-manage {
            display: inline-block;
            padding: 8px 15px;
            background: rgba(0, 123, 255, 0.9);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .btn-manage:hover {
            background: rgba(0, 123, 255, 0.7);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }
        .chart-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Manager Panel</h1>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?= $success_message ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= $error_message ?></div>
        <?php endif; ?>

        <!-- Form to Create Team -->
        <form method="POST">
            <label for="team_name">Team Name</label>
            <input type="text" class="form-control" id="team_name" name="team_name" required>
            
            <label for="team_lead_id">Team Lead</label>
            <select class="form-control" id="team_lead_id" name="team_lead_id" required>
                <option value="">Select a Team Lead</option>
                <?php while ($lead = $team_leads_result->fetch_assoc()): ?>
                    <option value="<?= $lead['user_id'] ?>"><?= $lead['username'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" class="btn">Create Team</button>
        </form>

        <!-- List of Teams -->
        <h2>Teams</h2>
        <table>
            <thead>
                <tr>
                    <th>Team ID</th>
                    <th>Team Name</th>
                    <th>Team Lead</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($team = $teams_result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $team['team_id'] ?></td>
                        <td><?= $team['team_name'] ?></td>
                        <td><?= $team['team_lead'] ?></td>
                        <td>
                            <a href="add_members.php?team_id=<?= $team['team_id'] ?>" class="btn-manage">Manage Members</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Pie Chart for Task Statuses -->
        <h2>Task Status Distribution</h2>
        <div class="chart-container">
            <canvas id="taskStatusChart"></canvas>
        </div>
    </div>

    <script>
        const taskStatuses = <?= json_encode($task_statuses); ?>;
        const labels = Object.keys(taskStatuses);
        const data = Object.values(taskStatuses);

        const ctx = document.getElementById('taskStatusChart').getContext('2d');
        new Chart(ctx, {
            type: 'pie',
            data: {
                labels: labels,
                datasets: [{
                    data: data,
                    backgroundColor: ['#28a745', '#ffc107', '#007bff'],
                    hoverBackgroundColor: ['#218838', '#e0a800', '#0056b3']
                }]
            },
            options: {
                responsive: true,
                onClick: (e, elements) => {
                    if (elements.length > 0) {
                        const clickedIndex = elements[0].index;
                        const clickedStatus = labels[clickedIndex];
                        window.location.href = `view_task.php?status=${encodeURIComponent(clickedStatus)}`;
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                return `${label}: ${value} tasks`;
                            }
                        }
                    }
                }
            }
        });
    </script>
</body>
</html>
