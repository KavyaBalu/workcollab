<?php
// function getDBConnection() {
//     $host = "workcollab.mysql.database.azure.com";
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
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "collaborative_work";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
