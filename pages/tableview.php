<?php
require '../includes/db_connect.php';

// Get all tables in the database
$tables_query = "SHOW TABLES";
$tables_result = $conn->query($tables_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Tables Viewer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .table-container {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .table-title {
            background-color: #2c3e50;
            color: white;
            padding: 15px;
            margin: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .table-title button {
            background: none;
            border: none;
            color: white;
            cursor: pointer;
        }
        .table-content {
            overflow-x: auto;
            padding: 15px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .collapse {
            transition: height 0.3s ease-out;
        }
        .record-count {
            font-size: 0.9rem;
            color: #ddd;
        }
        .timestamp {
            white-space: nowrap;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4">Database Tables</h1>
        
        <?php
        if ($tables_result->num_rows > 0) {
            while ($table_row = $tables_result->fetch_row()) {
                $table_name = $table_row[0];
                
                // Get record count
                $count_query = "SELECT COUNT(*) as count FROM `$table_name`";
                $count_result = $conn->query($count_query);
                $count = $count_result->fetch_assoc()['count'];
                
                // Get table data
                $data_query = "SELECT * FROM `$table_name`";
                $data_result = $conn->query($data_query);
                ?>
                
                <div class="table-container">
                    <h3 class="table-title">
                        <?= htmlspecialchars($table_name) ?>
                        <span class="d-flex align-items-center">
                            <span class="record-count me-3"><?= $count ?> records</span>
                            <button type="button" data-bs-toggle="collapse" 
                                    data-bs-target="#table-<?= htmlspecialchars($table_name) ?>" 
                                    aria-expanded="false">
                                <i class="bi bi-chevron-down"></i>
                            </button>
                        </span>
                    </h3>
                    
                    <div class="collapse" id="table-<?= htmlspecialchars($table_name) ?>">
                        <div class="table-content">
                            <?php if ($data_result->num_rows > 0): ?>
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <?php
                                            $fields = $data_result->fetch_fields();
                                            foreach ($fields as $field) {
                                                echo "<th>" . htmlspecialchars($field->name) . "</th>";
                                            }
                                            ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $data_result->data_seek(0);
                                        while ($row = $data_result->fetch_assoc()) {
                                            echo "<tr>";
                                            foreach ($row as $key => $value) {
                                                $cell_class = '';
                                                // Format timestamp fields
                                                if (strpos(strtolower($key), 'time') !== false || 
                                                    strpos(strtolower($key), 'date') !== false) {
                                                    $cell_class = 'timestamp';
                                                }
                                                
                                                echo "<td class='$cell_class'>" . 
                                                     ($value === null ? '<em>NULL</em>' : htmlspecialchars($value)) . 
                                                     "</td>";
                                            }
                                            echo "</tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            <?php else: ?>
                                <div class="p-3 text-muted">No data in this table</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php
            }
        } else {
            echo "<div class='alert alert-info'>No tables found in the database.</div>";
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add click handler for the chevron icons
        document.querySelectorAll('[data-bs-toggle="collapse"]').forEach(button => {
            button.addEventListener('click', () => {
                const icon = button.querySelector('i');
                icon.classList.toggle('bi-chevron-down');
                icon.classList.toggle('bi-chevron-up');
            });
        });
    </script>
</body>
</html>