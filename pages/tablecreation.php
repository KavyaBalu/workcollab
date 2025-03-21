<?php
require '../includes/db_connect.php';

// Function to check if table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to run query safely
function runQuery($conn, $sql, $errorMsg) {
    try {
        if (!$conn->query($sql)) {
            throw new Exception($conn->error);
        }
        return true;
    } catch (Exception $e) {
        echo $errorMsg . ": " . $e->getMessage() . "<br>";
        return false;
    }
}

// Array of create table queries
$createTableQueries = [
    'chat' => "CREATE TABLE IF NOT EXISTS chat (
        chat_id int NOT NULL AUTO_INCREMENT,
        discussion_id int DEFAULT NULL,
        chat_msg text,
        time timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        username varchar(255) DEFAULT NULL,
        PRIMARY KEY (chat_id),
        KEY discussion_id (discussion_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'discussions' => "CREATE TABLE IF NOT EXISTS discussions (
        discussion_id int NOT NULL AUTO_INCREMENT,
        team_id int NOT NULL,
        issue varchar(255) NOT NULL,
        description text NOT NULL,
        user_id int NOT NULL,
        username varchar(100) NOT NULL,
        started_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (discussion_id),
        KEY team_id (team_id),
        KEY user_id (user_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'file_shares' => "CREATE TABLE IF NOT EXISTS file_shares (
        share_id int NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        team_id int DEFAULT NULL,
        user_id int NOT NULL,
        username varchar(100) NOT NULL,
        is_project_file tinyint(1) DEFAULT '0',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (share_id),
        KEY team_id (team_id),
        KEY user_id (user_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'file_versions' => "CREATE TABLE IF NOT EXISTS file_versions (
        version_id int NOT NULL AUTO_INCREMENT,
        share_id int NOT NULL,
        version_number int NOT NULL,
        file_name varchar(255) NOT NULL,
        stored_name varchar(255) NOT NULL,
        uploaded_by int NOT NULL,
        username varchar(100) NOT NULL,
        upload_time datetime DEFAULT CURRENT_TIMESTAMP,
        version_note text,
        PRIMARY KEY (version_id),
        KEY share_id (share_id),
        KEY uploaded_by (uploaded_by)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'meetings' => "CREATE TABLE IF NOT EXISTS meetings (
        meeting_id int NOT NULL AUTO_INCREMENT,
        team_id int NOT NULL,
        title varchar(255) NOT NULL,
        description text,
        scheduled_time datetime NOT NULL,
        meet_link varchar(255) DEFAULT NULL,
        created_by int NOT NULL,
        manager_required tinyint(1) DEFAULT '0',
        status enum('scheduled','in_progress','completed') DEFAULT 'scheduled',
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (meeting_id),
        KEY team_id (team_id),
        KEY created_by (created_by)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'projects' => "CREATE TABLE IF NOT EXISTS projects (
        project_id int NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        description text,
        team_id int NOT NULL,
        status enum('notviewed','instudy','inprogress','completed','verified') DEFAULT 'notviewed',
        start_date date NOT NULL,
        due_date date NOT NULL,
        actual_end_date date DEFAULT NULL,
        created_by int NOT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        points int NOT NULL DEFAULT '1',
        PRIMARY KEY (project_id),
        KEY team_id (team_id),
        KEY created_by (created_by)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'project_extensions' => "CREATE TABLE IF NOT EXISTS project_extensions (
        extension_id int NOT NULL AUTO_INCREMENT,
        project_id int NOT NULL,
        requested_by int NOT NULL,
        requested_date date NOT NULL,
        reason text NOT NULL,
        status enum('pending','approved','rejected') DEFAULT 'pending',
        new_due_date date NOT NULL,
        response_note text,
        responded_at timestamp NULL DEFAULT NULL,
        PRIMARY KEY (extension_id),
        KEY project_id (project_id),
        KEY requested_by (requested_by)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'tasks' => "CREATE TABLE IF NOT EXISTS tasks (
        task_id int NOT NULL AUTO_INCREMENT,
        task_title varchar(255) NOT NULL,
        task_description text,
        assigned_to int DEFAULT NULL,
        assigned_by int DEFAULT NULL,
        due_date date DEFAULT NULL,
        status enum('not_viewed','in_study','in_progress','completed','verified') DEFAULT 'not_viewed',
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        team_id int DEFAULT NULL,
        project_id int DEFAULT NULL,
        points int NOT NULL DEFAULT '1',
        PRIMARY KEY (task_id),
        KEY assigned_to (assigned_to),
        KEY assigned_by (assigned_by),
        KEY project_id (project_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'task_status_history' => "CREATE TABLE IF NOT EXISTS task_status_history (
        history_id int NOT NULL AUTO_INCREMENT,
        task_id int NOT NULL,
        old_status varchar(20) DEFAULT NULL,
        new_status varchar(20) DEFAULT NULL,
        changed_by int NOT NULL,
        changed_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        notes text,
        PRIMARY KEY (history_id),
        KEY task_id (task_id),
        KEY changed_by (changed_by)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'teams' => "CREATE TABLE IF NOT EXISTS teams (
        team_id int NOT NULL AUTO_INCREMENT,
        team_name varchar(255) NOT NULL,
        team_lead_id int NOT NULL,
        PRIMARY KEY (team_id),
        KEY team_lead_id (team_lead_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'team_members' => "CREATE TABLE IF NOT EXISTS team_members (
        member_id int NOT NULL AUTO_INCREMENT,
        team_id int NOT NULL,
        user_id int NOT NULL,
        PRIMARY KEY (member_id),
        KEY team_id (team_id),
        KEY user_id (user_id)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci",

    'users' => "CREATE TABLE IF NOT EXISTS users (
        user_id int NOT NULL AUTO_INCREMENT,
        username varchar(50) NOT NULL,
        email varchar(100) NOT NULL,
        password varchar(255) NOT NULL,
        role enum('Manager','HR','TeamLead','TeamMember') NOT NULL,
        created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (user_id),
        UNIQUE KEY username (username),
        UNIQUE KEY email (email)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
];

// Initial users data
$users = [
    ['wildr', 'w@gmail.com', 'TeamMember'],
    ['Sheetal R', 'sheetal@gmail.com', 'TeamMember'],
    ['Abhi', 'abhi@gmail.com', 'TeamMember'],
    ['tl1', 'tl1@gmail.com', 'TeamLead'],
    ['Manager', 'm@gmail.com', 'Manager'],
    ['Archana', 'archana@gmail.com', 'TeamLead'],
    ['Kavya', 'kavya@gmail.com', 'TeamLead'],
    ['Shigil', 'shigil@gmail.com', 'TeamMember'],
    ['Sulfi', 'sulfi@gmail.com', 'TeamMember'],
    ['Ajay', 'ajay@gmail.com', 'TeamMember'],
    ['HRRR', 'h@gmail.com', 'HR'],
    ['Amala', 'amala@gmail.com', 'TeamMember']
];

// Create tables
echo "<h2>Creating Tables...</h2>";
foreach ($createTableQueries as $tableName => $query) {
    if (!tableExists($conn, $tableName)) {
        if (runQuery($conn, $query, "Error creating table $tableName")) {
            echo "Table '$tableName' created successfully<br>";
        }
    } else {
        echo "Table '$tableName' already exists<br>";
    }
}

// Insert users
echo "<h2>Inserting Users...</h2>";
$password = password_hash('ajai', PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");

foreach ($users as $user) {
    try {
        $stmt->bind_param("ssss", $user[0], $user[1], $password, $user[2]);
        if ($stmt->execute()) {
            echo "User '{$user[0]}' inserted successfully<br>";
        } else {
            echo "Error inserting user '{$user[0]}': " . $stmt->error . "<br>";
        }
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            echo "User '{$user[0]}' already exists<br>";
        } else {
            echo "Error inserting user '{$user[0]}': " . $e->getMessage() . "<br>";
        }
    }
}

$stmt->close();
echo "<h2>Database setup completed!</h2>";