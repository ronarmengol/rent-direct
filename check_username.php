<?php
include 'db.php';

header('Content-Type: application/json');

if (!isset($_POST['username'])) {
    echo json_encode(['error' => 'No username provided']);
    exit();
}

$username = mysqli_real_escape_string($conn, trim($_POST['username']));

// Check if username exists
$sql = "SELECT COUNT(*) as count FROM properties WHERE owner_name = '$username'";
$result = mysqli_query($conn, $sql);
$data = mysqli_fetch_assoc($result);

if ($data['count'] > 0) {
    echo json_encode([
        'available' => false,
        'message' => 'This username is already taken. Please choose another.'
    ]);
} else {
    echo json_encode([
        'available' => true,
        'message' => 'Username is available!'
    ]);
}
?>
