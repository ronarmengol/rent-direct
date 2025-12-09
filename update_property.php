<?php
include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get form data
$property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
$city = mysqli_real_escape_string($conn, $_POST['city']);
$address = mysqli_real_escape_string($conn, $_POST['address']);
$bedrooms = (int)$_POST['bedrooms'];
$price = (float)$_POST['price'];
$contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
$contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);

// Update property in database
$sql = "UPDATE properties SET 
        city = '$city',
        address = '$address',
        bedrooms = $bedrooms,
        price = $price,
        contact_person = '$contact_person',
        contact_number = '$contact_number'
        WHERE id = '$property_id'";

if (mysqli_query($conn, $sql)) {
    $_SESSION['msg'] = "Property details updated successfully!";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['msg'] = "Error updating property: " . mysqli_error($conn);
    $_SESSION['msg_type'] = "error";
}

// Redirect back to admin page
header("Location: admin.php?id=" . $property_id);
exit();
?>
