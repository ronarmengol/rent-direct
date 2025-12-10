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
$has_pool = isset($_POST['has_pool']) ? 1 : 0;
$has_water_tank = isset($_POST['has_water_tank']) ? 1 : 0;
$has_ac = isset($_POST['has_ac']) ? 1 : 0;
$has_solar = isset($_POST['has_solar']) ? 1 : 0;
$has_remote_gate = isset($_POST['has_remote_gate']) ? 1 : 0;
$has_cctv = isset($_POST['has_cctv']) ? 1 : 0;
$has_wall_fence = isset($_POST['has_wall_fence']) ? 1 : 0;
$has_electric_fence = isset($_POST['has_electric_fence']) ? 1 : 0;
$bathrooms = (int)$_POST['bathrooms'];
$toilets = (int)$_POST['toilets'];
$carpark = (int)$_POST['carpark'];
$geyser_type = mysqli_real_escape_string($conn, $_POST['geyser_type']);
$available_from = !empty($_POST['available_from']) ? "'" . mysqli_real_escape_string($conn, $_POST['available_from']) . "'" : "NULL";
$price = (float)$_POST['price'];
$contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
$contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);

// Update property in database
$sql = "UPDATE properties SET 
        city = '$city',
        address = '$address',
        bedrooms = $bedrooms,
        has_pool = $has_pool,
        has_water_tank = $has_water_tank,
        has_ac = $has_ac,
        has_solar = $has_solar,
        has_remote_gate = $has_remote_gate,
        has_cctv = $has_cctv,
        has_wall_fence = $has_wall_fence,
        has_electric_fence = $has_electric_fence,
        bathrooms = $bathrooms,
        toilets = $toilets,
        carpark = $carpark,
        geyser_type = '$geyser_type',
        available_from = $available_from,
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
