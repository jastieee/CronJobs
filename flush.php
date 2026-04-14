<?php
$conn = new mysqli('localhost', 'root', '', '');
$conn->query("FLUSH PRIVILEGES");
echo "Flushed";