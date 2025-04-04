<?php
// function getDBConnection() {
//     $host = "workcollabb.mysql.database.azure.com";
//     $username = "balu";
//     $password = "vidya@123";
//     $database = "collabdata";
//     $port = 3306;

//     // Enable MySQLi error reporting
//     mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

//     // Initialize connection
//     $conn = mysqli_init();

//     // Uncomment if SSL is required (use correct CA path)
//     // mysqli_ssl_set($conn, NULL, NULL, "/path/to/ca.pem", NULL, NULL);

//     // Establish connection
//     if (!mysqli_real_connect($conn, $host, $username, $password, $database, $port)) {
//         die("Connection failed: " . mysqli_connect_error());
//     }

//     return $conn;
// }

// Create a global connection object
// $conn = getDBConnection();

// // Check connection
// if ($conn) {
//     //echo "Connected successfully!";
// }
?>
<?php
function getDBConnection() {
    $host = "workcollabb.mysql.database.azure.com";
    $username = "balu"; // Always use full username
    $password = "vidya@123";
    $database = "collabdata";
    $port = 3306;

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = mysqli_init();

    // No SSL setup needed if disabled
    mysqli_real_connect($conn, $host, $username, $password, $database, $port);

    return $conn;
}

$conn = getDBConnection();

if ($conn) {
    echo "Connected successfully!";
}
?>



