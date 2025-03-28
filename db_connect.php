<?php
$con = mysqli_connect("workcommunity.mysql.database.azure.com", "balu", "vidya@123", "collabdata", 3306);

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

?>
