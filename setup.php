<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$DB_FULL_NAME = 'ecandycom_ecandy_db';
$DB_FULL_USER = 'ecandycom_3e76nusq098u';
$DB_PASS      = '5a75273fcb5103e147620b0f2bf9e03d79c62848';

echo "<h2>Testing Connection...</h2>";

$conn = mysqli_connect('localhost', $DB_FULL_USER, $DB_PASS, $DB_FULL_NAME);

if (!$conn) {
    die("<p style='color:red'>❌ Error " . mysqli_connect_errno() . ": " . mysqli_connect_error() . "</p>");
}

echo "<p style='color:green'>✅ Connected successfully!</p>";

$result = mysqli_query($conn, "SHOW TABLES");
echo "<h2>Tables:</h2><ul>";
while ($row = mysqli_fetch_row($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";

mysqli_close($conn);
?>