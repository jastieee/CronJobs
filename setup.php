<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$conn = mysqli_connect('localhost', 'root', '', 'ecandycom_ecandy_db');

if (!$conn) {
    die("<p style='color:red'>❌ Error: " . mysqli_connect_errno() . " - " . mysqli_connect_error() . "</p>");
}

echo "<p style='color:green'>✅ Root connected!</p>";

// Now grant privileges manually
$grant = mysqli_query($conn, "GRANT ALL PRIVILEGES ON ecandycom_ecandy_db.* TO 'ecandycom_nssi'@'localhost' IDENTIFIED BY 'NSSI@2026!';");
$flush = mysqli_query($conn, "FLUSH PRIVILEGES;");

if ($grant && $flush) {
    echo "<p style='color:green'>✅ Privileges granted and flushed!</p>";
} else {
    echo "<p style='color:red'>❌ Failed: " . mysqli_error($conn) . "</p>";
}

mysqli_close($conn);
?>