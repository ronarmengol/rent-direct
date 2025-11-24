
<?php
// Start the session at the very beginning
session_start();

$servername = "localhost";
$username = "root";
$password = "12345";
$dbname = "house";

$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>