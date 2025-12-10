<?php
include 'db.php';

// Set header for JSON response
header('Content-Type: application/json');

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['image_id']) || !isset($data['property_id']) || !isset($data['password'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
    exit();
}

$image_id = mysqli_real_escape_string($conn, $data['image_id']);
$property_id = mysqli_real_escape_string($conn, $data['property_id']);
$password = $data['password'];

// Verify password
$propSql = "SELECT password FROM properties WHERE id = '$property_id'";
$propResult = mysqli_query($conn, $propSql);
$propData = mysqli_fetch_assoc($propResult);

if (!$propData || $propData['password'] !== $password) {
    echo json_encode(['success' => false, 'error' => 'Incorrect password']);
    exit();
}

// Get image path before deleting
$sql = "SELECT image_path FROM property_images WHERE id = '$image_id' AND property_id = '$property_id'";
$result = mysqli_query($conn, $sql);
$imageData = mysqli_fetch_assoc($result);

if (!$imageData) {
    echo json_encode(['success' => false, 'error' => 'Image not found']);
    exit();
}

$imagePath = "uploads/" . $imageData['image_path'];

// Delete from database
$deleteSql = "DELETE FROM property_images WHERE id = '$image_id' AND property_id = '$property_id'";

if (mysqli_query($conn, $deleteSql)) {
    // Delete physical file
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }
    
    echo json_encode(['success' => true, 'message' => 'Image deleted successfully']);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to delete image from database']);
}
?>
