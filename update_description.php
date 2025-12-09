<?php
include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get form data
$property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
$description = mysqli_real_escape_string($conn, $_POST['description']);

// Update description in database
$sql = "UPDATE properties SET description = '$description' WHERE id = '$property_id'";

if (mysqli_query($conn, $sql)) {
    $_SESSION['msg'] = "Description updated successfully!";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['msg'] = "Error updating description: " . mysqli_error($conn);
    $_SESSION['msg_type'] = "error";
}

// Redirect back to admin page
header("Location: admin.php?id=" . $property_id);
exit();
?>
