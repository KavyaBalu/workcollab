<?php
 session_start();
 require '../includes/db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meeting Scheduler</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }

        .navbar {
            background-color: #2c3e50;
        }

        .back-btn {
            color: #ffffff;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.3s;
        }

        .back-btn:hover {
            color: #3498db;
        }

        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #3498db;
            border: none;
            padding: 0.5rem 1.5rem;
            transition: background-color 0.3s;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .form-control {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 0.75rem;
        }

        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }

        .meeting-table {
            background: white;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .meeting-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .copy-link {
            cursor: pointer;
            color: #3498db;
            transition: color 0.3s;
        }

        .copy-link:hover {
            color: #2980b9;
        }

        .toast-container {
            position: fixed;
            top: 1rem;
            right: 1rem;
            z-index: 1050;
        }

        .no-meetings {
            text-align: center;
            padding: 2rem;
            color: #6c757d;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            transition: background-color 0.3s;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <?php
   

    // Check if user is team lead
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'TeamLead') {
        // header('Location: login.php');
        // exit();
        echo "<script>window.location.href = 'login.php';</script>";
    }

    // Handle delete request
    if (isset($_POST['delete_meeting'])) {
        $meeting_id = $_POST['meeting_id'];
        $delete_stmt = $conn->prepare("DELETE FROM meetings WHERE meeting_id = ? AND team_id = ?");
        $delete_stmt->bind_param("ii", $meeting_id, $_SESSION['team_id']);
        
        if ($delete_stmt->execute()) {
            $success_message = "Meeting deleted successfully!";
        } else {
            $error_message = "Error deleting meeting: " . $conn->error;
        }
        $delete_stmt->close();
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'])) {
        $title = $_POST['title'];
        $description = $_POST['description'];
        $scheduled_time = $_POST['scheduled_time'];
        $meet_link = $_POST['meet_link'];
        $manager_required = isset($_POST['manager_required']) ? 1 : 0;
        $team_id = $_SESSION['team_id'];
        $created_by = $_SESSION['user_id'];

        // Validate scheduled time
        $scheduled_datetime = new DateTime($scheduled_time);
        $current_datetime = new DateTime();

        if ($scheduled_datetime <= $current_datetime) {
            $error_message = "Meeting time must be in the future!";
        } else {
            $stmt = $conn->prepare("INSERT INTO meetings (team_id, title, description, scheduled_time, meet_link, manager_required, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssii", $team_id, $title, $description, $scheduled_time, $meet_link, $manager_required, $created_by);
            
            if ($stmt->execute()) {
                $success_message = "Meeting scheduled successfully!";
            } else {
                $error_message = "Error scheduling meeting: " . $conn->error;
            }
            $stmt->close();
        }
    }

    // Fetch existing meetings
    $meetings = [];
    $fetch_stmt = $conn->prepare("SELECT * FROM meetings WHERE team_id = ? ORDER BY scheduled_time DESC");
    if ($fetch_stmt) {
        $fetch_stmt->bind_param("i", $_SESSION['team_id']);
        $fetch_stmt->execute();
        $result = $fetch_stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $meetings[] = $row;
        }
        $fetch_stmt->close();
    }
    ?>

    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top mb-4">
        <div class="container">
            <a href="teamlead_dashboard.php" class="back-btn">
                <i class="fas fa-arrow-left"></i>
                <span>Back to Dashboard</span>
            </a>
            <span class="navbar-brand ms-3">Meeting Scheduler</span>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-4">
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Schedule Meeting Card -->
        <div class="card mb-4">
            <div class="card-header bg-white border-0 pt-4 ps-4">
                <h4 class="mb-0">Schedule New Meeting</h4>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3" onsubmit="return validateDateTime()">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Schedule Time</label>
                        <input type="datetime-local" name="scheduled_time" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Google Meet Link</label>
                        <input type="url" name="meet_link" class="form-control" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="manager_required" class="form-check-input" id="managerRequired">
                            <label class="form-check-label" for="managerRequired">
                                Manager Presence Required
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-calendar-plus me-2"></i>Schedule Meeting
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Meetings List -->
        <div class="card">
            <div class="card-header bg-white border-0 pt-4 ps-4">
                <h4 class="mb-0">Scheduled Meetings</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive meeting-table">
                    <?php if (empty($meetings)): ?>
                        <div class="no-meetings">
                            <i class="fas fa-calendar-times fa-3x mb-3"></i>
                            <p class="mb-0">No meetings scheduled yet</p>
                        </div>
                    <?php else: ?>
                        <table class="table mb-0">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Description</th>
                                    <th>Scheduled Time</th>
                                    <th>Meet Link</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($meetings as $meeting): ?>
                                <tr>
                                    <td class="align-middle"><?= htmlspecialchars($meeting['title']) ?></td>
                                    <td class="align-middle"><?= htmlspecialchars($meeting['description']) ?></td>
                                    <td class="align-middle">
                                        <i class="far fa-calendar-alt me-2"></i>
                                        <?= htmlspecialchars($meeting['scheduled_time']) ?>
                                    </td>
                                    <td class="align-middle">
                                        <div class="input-group">
                                            <input type="text" class="form-control" value="<?= htmlspecialchars($meeting['meet_link']) ?>" 
                                                   id="link_<?= $meeting['meeting_id'] ?>">
                                            <button class="btn btn-outline-secondary copy-link" type="button"
                                                    onclick="copyLink(<?= $meeting['meeting_id'] ?>)">
                                                <i class="far fa-copy"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="align-middle">
                                        <span class="status-badge bg-primary bg-opacity-10 text-primary">
                                            <?= htmlspecialchars($meeting['status']) ?>
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <button class="btn btn-sm btn-primary me-2" 
                                                onclick="updateLink(<?= $meeting['meeting_id'] ?>)">
                                            <i class="fas fa-sync-alt me-1"></i>Update
                                        </button>
                                        <a href="delete_meeting.php?id=<?= $meeting['meeting_id'] ?>" 
   class="btn btn-sm btn-danger me-2"
   onclick="return confirm('Are you sure you want to delete this meeting?');">
    <i class="fas fa-trash-alt me-1"></i>Delete
</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container"></div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function validateDateTime() {
        const scheduledTime = new Date(document.querySelector('input[name="scheduled_time"]').value);
        const currentTime = new Date();
        
        if (scheduledTime <= currentTime) {
            alert('Meeting time must be in the future!');
            return false;
        }
        return true;
    }

    function showToast(message, type = 'success') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        `;
        
        toastContainer.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
        
        toast.addEventListener('hidden.bs.toast', () => {
            toast.remove();
        });
    }

    function copyLink(meetingId) {
        const linkInput = document.getElementById('link_' + meetingId);
        linkInput.select();
        document.execCommand('copy');
        showToast('Meeting link copied to clipboard!');
    }

    function updateLink(meetingId) {
        const newLink = document.getElementById('link_' + meetingId).value;
        fetch('update_meeting_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `meeting_id=${meetingId}&meet_link=${encodeURIComponent(newLink)}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Link updated successfully!');
            } else {
                showToast('Error updating link', 'danger');
            }
        })
        .catch(error => {
            showToast('Error updating link: ' + error.message, 'danger');
        });
    }

    function deleteMeeting(meetingId) {
        if (confirm('Are you sure you want to delete this meeting?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.style.display = 'none';
            form.innerHTML = `
                <input type="hidden" name="delete_meeting" value="1">
                <input type="hidden" name="meeting_id" value="${meetingId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Initialize all tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Set minimum date for datetime-local input
    window.addEventListener('load', function() {
        const dateInput = document.querySelector('input[name="scheduled_time"]');
        if (dateInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            dateInput.min = now.toISOString().slice(0,16);
        }
    });

    // Refresh page after successful operations
    function refreshPage() {
        window.location.reload();
    }

    // Format datetime for display
    function formatDateTime(dateTimeStr) {
        const options = { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric', 
            hour: '2-digit', 
            minute: '2-digit' 
        };
        return new Date(dateTimeStr).toLocaleDateString('en-US', options);
    }

    // Add event listeners for dynamic content
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submission
        const meetingForm = document.querySelector('form');
        if (meetingForm) {
            meetingForm.addEventListener('submit', function(e) {
                if (!validateDateTime()) {
                    e.preventDefault();
                }
            });
        }

        // Initialize all copy buttons
        const copyButtons = document.querySelectorAll('.copy-link');
        copyButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const meetingId = this.getAttribute('data-meeting-id');
                copyLink(meetingId);
            });
        });

        // Handle link updates
        const updateButtons = document.querySelectorAll('.btn-primary[onclick^="updateLink"]');
        updateButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const meetingId = this.getAttribute('data-meeting-id');
                updateLink(meetingId);
            });
        });

        // Handle meeting deletions
        const deleteButtons = document.querySelectorAll('.btn-danger[onclick^="deleteMeeting"]');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const meetingId = this.getAttribute('data-meeting-id');
                deleteMeeting(meetingId);
            });
        });
    });

    // Handle errors globally
    // window.addEventListener('error', function(e) {
    //     showToast('An error occurred: ' + e.message, 'danger');
    // });
    </script>
</body>
</html>