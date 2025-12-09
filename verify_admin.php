<?php
include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get form data
$id = mysqli_real_escape_string($conn, $_POST['id']);
$username = $_POST['username'];
$password_check = $_POST['password_check'];

// Fetch property data
$sql = "SELECT * FROM properties WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$property = mysqli_fetch_assoc($result);

// Check if property exists
if (!$property) {
    $_SESSION['msg'] = "Property not found.";
    $_SESSION['msg_type'] = "error";
    header("Location: index.php");
    exit();
}

// Verify both username and password
if ($username === $property['owner_name'] && $password_check === $property['password']) {
    // Credentials correct - redirect to admin page
    header("Location: admin.php?id=" . $property['id']);
    exit();
} else {
    // Credentials incorrect - redirect back with error
    $_SESSION['msg'] = "Incorrect username or password. Access denied.";
    $_SESSION['msg_type'] = "error";
    header("Location: property_details.php?id=" . $property['id']);
    exit();
}
?>
