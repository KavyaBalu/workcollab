<?php
// $servername = "localhost";
// $username = "root";
// $password = "";
// $dbname = "collaborative_work";

// $conn = new mysqli($servername, $username, $password, $dbname);
// if ($conn->connect_error) {
//     die("Connection failed: " . $conn->connect_error);
// }
?>
<?php
// function getDBConnection() {
    //     $host = "stranger.mysql.database.azure.com";
    //     $username = "zxwyielvwx";
    //     $password = "IrnGhs5sDjaT4KT$";
    //     $database = "collaborative_work";
        
    //     $conn = mysqli_connect($host, $username, $password, $database);
        
    //     if (!$conn) {
    //         die("Connection failed: " . mysqli_connect_error());
    //     }
        
    //     return $conn;
    // }
    
    // // Create a global connection object
    // $conn = getDBConnection();
    
?>

<?php
$host = "workcommunity.mysql.database.azure.com";
$username = "vidya";
$password = "Kavyabalu@2000";
$database = "workdata";
$port = 3306;

// Initialize connection
$conn = mysqli_init();

// Set SSL (if required, otherwise remove this part)
mysqli_ssl_set($conn, NULL, NULL, "{path to CA cert}", NULL, NULL);

// Establish a secure connection
if (!mysqli_real_connect($conn, $host, $username, $password, $database, $port, MYSQLI_CLIENT_SSL)) {
    die("Connection failed: " . mysqli_connect_error());
}


?>
