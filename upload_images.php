<?php
include 'db.php';

// Image compression function
function compressImage($source, $destination, $quality) {
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];

    switch($mime){
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            imagejpeg($image, $destination, $quality);
            imagedestroy($image);
            return true;
            
        case 'image/png':
            $image = imagecreatefrompng($source);
            imagealphablending($image, false);
            imagesavealpha($image, true);
            imagepng($image, $destination, 6);
            imagedestroy($image);
            return true;
            
        default:
            return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$property_id = mysqli_real_escape_string($conn, $_POST['property_id']);

// Check current image count
$countSql = "SELECT COUNT(*) as count FROM property_images WHERE property_id = '$property_id'";
$countResult = mysqli_query($conn, $countSql);
$countData = mysqli_fetch_assoc($countResult);
$currentCount = $countData['count'];

$maxImages = 10;
$remainingSlots = $maxImages - $currentCount;

if ($remainingSlots <= 0) {
    $_SESSION['msg'] = "Maximum number of images (10) reached!";
    $_SESSION['msg_type'] = "error";
    header("Location: admin.php?id=" . $property_id);
    exit();
}

// Handle image uploads
$total_files = count($_FILES['images']['name']);
$uploaded = 0;
$allowed_ext = array('jpg', 'jpeg', 'png');

for ($i = 0; $i < min($total_files, $remainingSlots); $i++) {
    $fileName = $_FILES['images']['name'][$i];
    $tmpName = $_FILES['images']['tmp_name'][$i];

    if ($fileName != "") {
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (in_array($fileExt, $allowed_ext)) {
            $check = getimagesize($tmpName);
            if($check !== false) {
                $newFileName = uniqid() . "." . $fileExt;
                $targetPath = "uploads/" . $newFileName;
                
                if (compressImage($tmpName, $targetPath, 60)) {
                    // Get the highest display_order for this property
                    $orderSql = "SELECT MAX(display_order) as max_order FROM property_images WHERE property_id = '$property_id'";
                    $orderResult = mysqli_query($conn, $orderSql);
                    $orderData = mysqli_fetch_assoc($orderResult);
                    $nextOrder = ($orderData['max_order'] ?? -1) + 1;
                    
                    $sqlImg = "INSERT INTO property_images (property_id, image_path, display_order) VALUES ('$property_id', '$newFileName', $nextOrder)";
                    if(mysqli_query($conn, $sqlImg)) {
                        $uploaded++;
                    }
                }
            }
        }
    }
}

if ($uploaded > 0) {
    $_SESSION['msg'] = "$uploaded image(s) uploaded successfully!";
    $_SESSION['msg_type'] = "success";
} else {
    $_SESSION['msg'] = "No images were uploaded. Please check file format (JPG/PNG only).";
    $_SESSION['msg_type'] = "error";
}

header("Location: admin.php?id=" . $property_id);
exit();
?>
