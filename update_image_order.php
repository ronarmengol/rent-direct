<?php
include 'db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['property_id']) || !isset($data['images'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

$property_id = mysqli_real_escape_string($conn, $data['property_id']);
$images = $data['images'];

// Update each image's display_order
$success = true;
foreach ($images as $image) {
    $image_id = mysqli_real_escape_string($conn, $image['id']);
    $order = (int)$image['order'];
    
    $sql = "UPDATE property_images SET display_order = $order WHERE id = '$image_id' AND property_id = '$property_id'";
    
    if (!mysqli_query($conn, $sql)) {
        $success = false;
        break;
    }
}

if ($success) {
    echo json_encode(['success' => true, 'message' => 'Image order updated successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to update image order']);
}
?>
