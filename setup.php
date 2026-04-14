<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$DB_FULL_NAME = 'ecandycom_ecandy_db';
$DB_FULL_USER = 'ecandycom_dbuser';
$DB_PASS      = 'NSSI@2026!';

echo "<h2>Testing Connection...</h2>";

// Connect WITHOUT database first
$conn = mysqli_connect('localhost', $DB_FULL_USER, $DB_PASS);

if (!$conn) {
    die("<p style='color:red'>❌ Error " . mysqli_connect_errno() . ": " . mysqli_connect_error() . "</p>");
}

echo "<p style='color:green'>✅ User connected!</p>";

// Show all databases this user can see
$result = mysqli_query($conn, "SHOW DATABASES");
echo "<h3>Databases visible to this user:</h3><ul>";
while ($row = mysqli_fetch_row($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

mysqli_close($conn);
?>