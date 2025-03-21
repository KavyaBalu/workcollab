<?php
// Password to be hashed
$password = "ajai";

// Generate a bcrypt hash of the password
$hashed_password = password_hash($password, PASSWORD_BCRYPT);

// Output the hashed password
echo "Hashed password: " . $hashed_password;
?>
