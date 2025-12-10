<?php
include 'db.php';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$property_id = mysqli_real_escape_string($conn, $_POST['property_id']);
$update_section = isset($_POST['update_section']) ? $_POST['update_section'] : 'all'; // basic, amenities, contact, or all

$updates = [];

// --- 1. BASIC INFO SECTION ---
if ($update_section === 'basic' || $update_section === 'all') {
    if (isset($_POST['city'])) {
        $city = mysqli_real_escape_string($conn, $_POST['city']);
        $updates[] = "city = '$city'";
    }
    if (isset($_POST['address'])) {
        $address = mysqli_real_escape_string($conn, $_POST['address']);
        $updates[] = "address = '$address'";
    }
    if (isset($_POST['bedrooms'])) {
        $bedrooms = (int)$_POST['bedrooms'];
        $updates[] = "bedrooms = $bedrooms";
    }
    if (isset($_POST['bathrooms'])) {
        $bathrooms = (int)$_POST['bathrooms'];
        $updates[] = "bathrooms = $bathrooms";
    }
    if (isset($_POST['toilets'])) {
        $toilets = (int)$_POST['toilets'];
        $updates[] = "toilets = $toilets";
    }
    if (isset($_POST['carpark'])) {
        $carpark = (int)$_POST['carpark'];
        $updates[] = "carpark = $carpark";
    }
    if (isset($_POST['geyser_type'])) {
        $geyser_type = mysqli_real_escape_string($conn, $_POST['geyser_type']);
        $updates[] = "geyser_type = '$geyser_type'";
    }
    if (isset($_POST['available_from'])) {
        $date_val = !empty($_POST['available_from']) ? "'" . mysqli_real_escape_string($conn, $_POST['available_from']) . "'" : "NULL";
        $updates[] = "available_from = $date_val";
    }
}

// --- 2. AMENITIES SECTION ---
if ($update_section === 'amenities' || $update_section === 'all') {
    // For amenities, we MUST assume missing = 0 (unchecked)
    // BUT only if we are specifically updating amenities or all
    
    $has_pool = isset($_POST['has_pool']) ? 1 : 0;
    $has_water_tank = isset($_POST['has_water_tank']) ? 1 : 0;
    $has_ac = isset($_POST['has_ac']) ? 1 : 0;
    $has_solar = isset($_POST['has_solar']) ? 1 : 0;
    $has_remote_gate = isset($_POST['has_remote_gate']) ? 1 : 0;
    $has_cctv = isset($_POST['has_cctv']) ? 1 : 0;
    $has_wall_fence = isset($_POST['has_wall_fence']) ? 1 : 0;
    $has_electric_fence = isset($_POST['has_electric_fence']) ? 1 : 0;

    $updates[] = "has_pool = $has_pool";
    $updates[] = "has_water_tank = $has_water_tank";
    $updates[] = "has_ac = $has_ac";
    $updates[] = "has_solar = $has_solar";
    $updates[] = "has_remote_gate = $has_remote_gate";
    $updates[] = "has_cctv = $has_cctv";
    $updates[] = "has_wall_fence = $has_wall_fence";
    $updates[] = "has_electric_fence = $has_electric_fence";
}

// --- 3. CONTACT & PRICING SECTION ---
if ($update_section === 'contact' || $update_section === 'all') {
    if (isset($_POST['price'])) {
        $price = (float)$_POST['price'];
        $updates[] = "price = $price";
    }
    if (isset($_POST['contact_person'])) {
        $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
        $updates[] = "contact_person = '$contact_person'";
    }
    if (isset($_POST['contact_number'])) {
        $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
        $updates[] = "contact_number = '$contact_number'";
    }
}

// Check if there's anything to update
if (empty($updates)) {
    // Nothing to update
    $success = true; // Technically handled correctly
    $msg = "No changes detected.";
} else {
    $sql = "UPDATE properties SET " . implode(', ', $updates) . " WHERE id = '$property_id'";
    if (mysqli_query($conn, $sql)) {
        $success = true;
        $msg = "Property details updated successfully!";
    } else {
        $success = false;
        $msg = "Error updating property: " . mysqli_error($conn);
    }
}

// Detect AJAX request
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'message' => $msg]);
} else {
    // Normal storage for non-AJAX fallback
    $_SESSION['msg'] = $msg;
    $_SESSION['msg_type'] = $success ? 'success' : 'error';
    header("Location: admin.php?id=" . $property_id);
}

exit();
?>
