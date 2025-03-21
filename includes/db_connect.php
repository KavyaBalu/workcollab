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