<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$DB_FULL_NAME = 'ecandycom_ecandy_db';
$DB_FULL_USER = 'ecandycom_nssi';
$DB_PASS      = 'NSSI@2026!';

echo "<h2>Testing Connection...</h2>";

$conn = mysqli_connect('localhost', $DB_FULL_USER, $DB_PASS, $DB_FULL_NAME);

if (!$conn) {
    die("<p style='color:red'>❌ Connection Error: " . mysqli_connect_errno() . " - " . mysqli_connect_error() . "</p>");
}

echo "<p style='color:green'>✅ Connected successfully!</p>";

// Show all tables
echo "<h2>Tables in database:</h2>";
$result = mysqli_query($conn, "SHOW TABLES");

if (mysqli_num_rows($result) === 0) {
    echo "<p style='color:orange'>⚠️ No tables found — database is empty.</p>";
} else {
    echo "<ul>";
    while ($row = mysqli_fetch_row($result)) {
        echo "<li>" . $row[0] . "</li>";
    }
    echo "</ul>";
}

mysqli_close($conn);