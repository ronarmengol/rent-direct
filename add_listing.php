<?php
include 'db.php';

// --- COMPRESSION & VALIDATION FUNCTION ---
function compressImage($source, $destination, $quality) {
    $imgInfo = getimagesize($source);
    $mime = $imgInfo['mime'];

    // We only create the image resource if it matches our allowed types
    switch($mime){
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($source);
            // Save JPG (Quality: 0-100). 60 is a good balance.
            imagejpeg($image, $destination, $quality); 
            imagedestroy($image);
            return true;
            
        case 'image/png':
            $image = imagecreatefrompng($source);
            // Preserve PNG transparency
            imagealphablending($image, false);
            imagesavealpha($image, true);
            // Save PNG (Compression level 0-9). 6 is standard.
            // Note: PNG doesn't use the 0-100 quality scale like JPG.
            imagepng($image, $destination, 6); 
            imagedestroy($image);
            return true;
            
        default:
            // If it's NOT jpg/png, return false (fail)
            return false;
    }
}
// ------------------------------------------

$error = "";

if (isset($_POST['submit'])) {
    // 1. Capture Form Data
    $owner = mysqli_real_escape_string($conn, $_POST['owner_name']);
    $pass  = $_POST['password'];
    
    $contact_person = mysqli_real_escape_string($conn, $_POST['contact_person']);
    $contact_number = mysqli_real_escape_string($conn, $_POST['contact_number']);
    
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $bedrooms = (int)$_POST['bedrooms'];
    $price = (float)$_POST['price'];
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    // 2. Hash the password
    $hashed_password = password_hash($pass, PASSWORD_DEFAULT);

    // 3. Insert into Database
    $sql = "INSERT INTO properties (owner_name, password, contact_person, contact_number, city, address, bedrooms, price, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_stmt_init($conn);
    
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssssiis", $owner, $hashed_password, $contact_person, $contact_number, $city, $address, $bedrooms, $price, $desc);
        mysqli_stmt_execute($stmt);
        $property_id = mysqli_insert_id($conn);

        // 4. Handle Image Uploads
        $total_files = count($_FILES['images']['name']);
        if ($total_files > 10) $total_files = 10;

        // Allowed extensions array
        $allowed_ext = array('jpg', 'jpeg', 'png');

        for ($i = 0; $i < $total_files; $i++) {
            $fileName = $_FILES['images']['name'][$i];
            $tmpName = $_FILES['images']['tmp_name'][$i];

            if ($fileName != "") {
                // Get file extension
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                // CHECK 1: Is extension allowed?
                if (in_array($fileExt, $allowed_ext)) {
                    
                    // CHECK 2: Is it actually an image? (MIME check)
                    $check = getimagesize($tmpName);
                    if($check !== false) {
                        
                        $newFileName = uniqid() . "." . $fileExt; // Keep original extension type
                        $targetPath = "uploads/" . $newFileName;
                        
                        // CHECK 3 & ACTION: Compress and Save
                        // We use 60% quality for JPGs
                        if (compressImage($tmpName, $targetPath, 60)) {
                            $sqlImg = "INSERT INTO property_images (property_id, image_path) VALUES ('$property_id', '$newFileName')";
                            mysqli_query($conn, $sqlImg);
                        }
                    }
                }
                // If file is not JPG/PNG, it is simply skipped here.
            }
        }
        
        // Redirect to home page
        header("Location: index.php");
        exit();
    } else {
        $error = "Database error: " . mysqli_error($conn);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Rental Listing - Housing App</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <nav class="navbar">
        <div class="brand"><a href="index.php">Housing App</a></div>
        <div><a href="index.php">Home</a></div>
    </nav>

    <div class="container">
        <div class="form-card">
            <h2>List Your Property</h2>
            
            <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

            <form action="add_listing.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Owner Username</label>
                    <input type="text" name="owner_name" required placeholder="Your Name or Company">
                </div>
                <div class="form-group">
                    <label>Set a Delete Password</label>
                    <input type="password" name="password" required placeholder="Password for this specific listing">
                </div>
                
                <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
                <h3>Contact Details</h3>
                <div class="form-group">
                    <label>Contact Person Name</label>
                    <input type="text" name="contact_person" required placeholder="e.g. John Doe">
                </div>
                <div class="form-group">
                    <label>Contact Phone Number</label>
                    <input type="text" name="contact_number" required placeholder="e.g. +260 97 1234567">
                </div>
                <hr style="margin: 15px 0; border:0; border-top:1px solid #eee;">
                
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" required>
                </div>
                <div class="form-group">
                    <label>Full Address</label>
                    <input type="text" name="address" required>
                </div>
                <div class="form-group">
                    <label>Price per Month (K)</label>
                    <input type="number" name="price" required>
                </div>
                <div class="form-group">
                    <label>Bedrooms</label>
                    <select name="bedrooms">
                        <option value="1">1 Bedroom</option>
                        <option value="2">2 Bedrooms</option>
                        <option value="3">3 Bedrooms</option>
                        <option value="4">4 Bedrooms</option>
                        <option value="5">5 Bedrooms</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Photos (Max 10 - JPG/PNG Only)</label>
                    <input type="file" name="images[]" multiple accept="image/png, image/jpeg" required>
                </div>
                <button type="submit" name="submit" class="btn-submit">Publish Listing</button>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>