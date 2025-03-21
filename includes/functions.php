<?php
function sanitizeInput($input) {
    global $conn;
    return htmlspecialchars(mysqli_real_escape_string($conn, trim($input)));
}

function redirect($url) {
    echo "<script>window.location.href = '$url';</script>";

}
?>
