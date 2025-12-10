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
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    
    // 2. Hash the password
    // 2. No Hashing (DEV MODE)
    $hashed_password = $pass;

    // 3. Insert into Database
    $sql = "INSERT INTO properties (owner_name, password, contact_person, contact_number, city, address, bedrooms, has_pool, has_water_tank, has_ac, has_solar, has_remote_gate, has_cctv, has_wall_fence, has_electric_fence, bathrooms, toilets, carpark, geyser_type, available_from, price, description) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, $available_from, ?, ?)";
    $stmt = mysqli_stmt_init($conn);
    
    if (mysqli_stmt_prepare($stmt, $sql)) {
        mysqli_stmt_bind_param($stmt, "ssssssiiiiiiiiiiisss", $owner, $hashed_password, $contact_person, $contact_number, $city, $address, $bedrooms, $has_pool, $has_water_tank, $has_ac, $has_solar, $has_remote_gate, $has_cctv, $has_wall_fence, $has_electric_fence, $bathrooms, $toilets, $carpark, $geyser_type, $price, $desc);
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
    <style>
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            margin-top: 12px;
        }
        
        .checkbox-card {
            display: flex;
            align-items: center;
            padding: 16px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.2s ease;
            position: relative;
        }
        
        .checkbox-card:hover {
            background: #f1f5f9;
            border-color: #cbd5e1;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .checkbox-card input[type="checkbox"] {
            position: absolute;
            opacity: 0;
            cursor: pointer;
        }
        
        .checkbox-card input[type="checkbox"]:checked + .checkbox-label {
            color: #0f172a;
            font-weight: 600;
        }
        
        .checkbox-card input[type="checkbox"]:checked ~ .checkbox-label::before {
            content: "✓";
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #10b981;
            font-weight: bold;
            font-size: 20px;
        }
        
        .checkbox-card:has(input:checked) {
            background: #ecfdf5;
            border-color: #10b981;
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
            color: #475569;
            font-size: 15px;
            position: relative;
        }
        
        .checkbox-icon {
            font-size: 24px;
            line-height: 1;
        }
        
        @media (max-width: 768px) {
            .checkbox-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="brand"><a href="index.php">Housing App</a></div>
        <div><a href="index.php">Home</a></div>
    </nav>

    <div class="container" style="max-width: 1200px;">
        <div class="page-header">
            <h1>List Your Property</h1>
            <p>Fill in the details below to create your rental listing</p>
        </div>
        
        <!-- Why List Section -->
        <div class="benefits-summary" style="background: white; padding: 24px; border-radius: 12px; margin-bottom: 32px; border: 1px solid #e2e8f0; display: flex; gap: 20px; align-items: center; justify-content: space-around; flex-wrap: wrap;">
            <div style="text-align: center;">
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                    <svg width="70px" height="70px" version="1.1" viewBox="0 0 210 297" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><g transform="matrix(8.819 0 0 8.819 2.11 38.19)" fill="none"><path d="m9.301 17.39h1.357v-1.06l0.7754-0.8658 1.098 1.926h1.499l-1.654-2.817 1.525-2.223h-1.57l-1.674 2.455v-2.455h-1.357z" fill="#1a1a1a" stroke-width=".109" style="font-variant-ligatures:none" aria-label="K"/><path d="m9.558 5h4.222c1.081 0 0.4875 1.664 0.0497 2.679l-0.4982 1.164-0.0673 0.157c0.4719-0.02349 0.9386 0.1105 1.33 0.382 1.464 1.374 2.652 3.028 3.497 4.869 0.3294 0.6808 0.4601 1.445 0.3763 2.2-0.1006 1.925-1.606 3.459-3.484 3.549h-6.63c-1.878-0.0875-3.386-1.62-3.489-3.545-0.08381-0.7552 0.04688-1.519 0.3764-2.2 0.8464-1.843 2.036-3.498 3.502-4.873 0.3914-0.2715 0.858-0.4055 1.33-0.382l-0.078-0.181-0.4875-1.14c-0.4358-1.015-1.033-2.679 0.04972-2.679z" clip-rule="evenodd" fill-rule="evenodd" stroke="#000" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/><path d="m13.3 9.75c0.4142 0 0.75-0.3358 0.75-0.75s-0.3358-0.75-0.75-0.75zm-3.19-1.5c-0.4142 0-0.75 0.3358-0.75 0.75s0.3358 0.75 0.75 0.75zm5.87 0.3999c0.3589-0.2068 0.4823-0.6653 0.2755-1.024s-0.6653-0.4823-1.024-0.2755zm-2.612 0.1931 0.1447 0.7359 0.0022-4.3e-4zm-3.336-0.024 0.1553-0.7338-0.0068-0.00138zm-1.864-1.475c-0.3625-0.2004-0.8188-0.06888-1.019 0.2936-0.2004 0.3625-0.06889 0.8188 0.2936 1.019zm5.132 0.9064h-3.19v1.5h3.19zm1.931-0.8999c-0.627 0.3612-1.306 0.6167-2.01 0.7574l0.2938 1.471c0.8653-0.1728 1.698-0.4864 2.465-0.9286zm-2.008 0.757c-1.003 0.1973-2.035 0.1899-3.036-0.02184l-0.3105 1.468c1.198 0.2536 2.434 0.2625 3.635 0.02616zm-3.042-0.02326c-0.7027-0.1419-1.381-0.3913-2.013-0.7403l-0.7256 1.313c0.7648 0.4227 1.588 0.7254 2.441 0.8977z" fill="#000"/></g></svg>
                </div>
                <div style="font-weight: 600;">Zero Fees</div>
                <div style="font-size: 0.8rem; color: #64748b;">Keep 100% of rent</div>
            </div>
            <div style="text-align: center;">
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                    <svg width="50px" height="50px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.94601 5.59492C3.92853 4.15983 1 5.48359 1 7.83062V16.1694C1 18.5164 3.92853 19.8402 5.94601 18.4051L11 14.81V16.1694C11 18.5164 13.9285 19.8402 15.946 18.4051L21.8074 14.2357C23.3975 13.1046 23.3975 10.8954 21.8074 9.76429L15.946 5.59492C13.9285 4.15983 11 5.48359 11 7.83062V9.18996L5.94601 5.59492ZM3.0462 7.83062C3.0462 7.04828 4.02237 6.60703 4.69487 7.08539L10.5563 11.2548C11.0863 11.6318 11.0863 12.3682 10.5563 12.7452L4.69487 16.9146C4.02237 17.393 3.0462 16.9517 3.0462 16.1694V7.83062ZM13.0462 7.83062C13.0462 7.04828 14.0224 6.60703 14.6949 7.08539L20.5563 11.2548C21.0863 11.6318 21.0863 12.3682 20.5563 12.7452L14.6949 16.9146C14.0224 17.393 13.0462 16.9517 13.0462 16.1694V7.83062Z" fill="#0F0F0F"/>
</svg>
                </div>
                <div style="font-weight: 600;">Fast Renting</div>
                <div style="font-size: 0.8rem; color: #64748b;">Direct connection</div>
            </div>
            <div style="text-align: center;">
                <div style="height: 60px; display: flex; align-items: center; justify-content: center; margin-bottom: 8px;">
                    <svg width="50px" height="50px" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg" fill="none">
                    <path fill="#000000" fill-rule="evenodd" d="M15.198 3.52a1.612 1.612 0 012.223 2.336L6.346 16.421l-2.854.375 1.17-3.272L15.197 3.521zm3.725-1.322a3.612 3.612 0 00-5.102-.128L3.11 12.238a1 1 0 00-.253.388l-1.8 5.037a1 1 0 001.072 1.328l4.8-.63a1 1 0 00.56-.267L18.8 7.304a3.612 3.612 0 00.122-5.106zM12 17a1 1 0 100 2h6a1 1 0 100-2h-6z"/>
                    </svg>
                </div>
                <div style="font-weight: 600;">Easy Management</div>
                <div style="font-size: 0.8rem; color: #64748b;">Edit anytime</div>
            </div>
        </div>

        <?php if($error): ?><div class="error-msg"><?php echo $error; ?></div><?php endif; ?>

        <form action="add_listing.php" method="POST" enctype="multipart/form-data">
            
            <!-- Grid Layout -->
            <div class="form-grid">
                
                <!-- Left Column -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>Account Information</h3>
                        <p>Your credentials for managing this listing</p>
                    </div>
                    <div class="form-group">
                        <label>Owner Username</label>
                        <input type="text" id="username-input" name="owner_name" required placeholder="Your Name or Company">
                        <div id="username-feedback" class="username-feedback"></div>
                    </div>
                    <div class="form-group">
                        <label>Delete Password</label>
                        <input type="password" name="password" required placeholder="Set a password for this listing">
                    </div>
                </div>

                <!-- Right Column - Contact -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>Contact Details</h3>
                        <p>How tenants can reach you</p>
                    </div>
                    <div class="form-group">
                        <label>Contact Person</label>
                        <input type="text" name="contact_person" required placeholder="e.g. John Doe">
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="contact_number" required placeholder="e.g. +260 97 1234567">
                    </div>
                </div>

                <!-- Property Details - Split into organized sections -->
                
                <!-- Basic Info -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>📍 Basic Information</h3>
                        <p>Location and pricing</p>
                    </div>
                    <div class="form-group">
                        <label>City</label>
                        <input type="text" name="city" required placeholder="e.g. Lusaka">
                    </div>
                    <div class="form-group">
                        <label>Full Address</label>
                        <input type="text" name="address" required placeholder="Street address">
                    </div>
                    <div class="form-group">
                        <label>Monthly Rent (K)</label>
                        <input type="number" name="price" required placeholder="0.00" step="0.01">
                    </div>
                    <div class="form-group">
                        <label>Available From</label>
                        <input type="date" name="available_from">
                    </div>
                </div>

                <!-- Room Details -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>🏠 Room Details</h3>
                        <p>Bedrooms, bathrooms & parking</p>
                    </div>
                    <div class="form-group">
                        <label>Bedrooms</label>
                        <select name="bedrooms">
                            <option value="1">1 Bedroom</option>
                            <option value="2">2 Bedrooms</option>
                            <option value="3">3 Bedrooms</option>
                            <option value="4">4 Bedrooms</option>
                            <option value="5">5+ Bedrooms</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Bathrooms</label>
                        <select name="bathrooms">
                            <option value="1">1 Bathroom</option>
                            <option value="2">2 Bathrooms</option>
                            <option value="3">3 Bathrooms</option>
                            <option value="4">4 Bathrooms</option>
                            <option value="5">5+ Bathrooms</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Toilets</label>
                        <select name="toilets">
                            <option value="1">1 Toilet</option>
                            <option value="2">2 Toilets</option>
                            <option value="3">3 Toilets</option>
                            <option value="4">4 Toilets</option>
                            <option value="5">5+ Toilets</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Parking Spaces</label>
                        <select name="carpark">
                            <option value="0">No Parking</option>
                            <option value="1">1 Space</option>
                            <option value="2">2 Spaces</option>
                            <option value="3">3 Spaces</option>
                            <option value="4">4 Spaces</option>
                            <option value="5">5 Spaces</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Geyser Type</label>
                        <select name="geyser_type">
                            <option value="none">None</option>
                            <option value="electric">Electric</option>
                            <option value="solar">Solar</option>
                        </select>
                    </div>
                </div>

                <!-- Features & Amenities -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>✨ Features & Amenities</h3>
                        <p>Select available features</p>
                    </div>
                    <div class="checkbox-grid">
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_pool" value="1">
                            <span class="checkbox-label">
                                <span>Swimming Pool</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_water_tank" value="1">
                            <span class="checkbox-label">
                                <span>Water Tank</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_ac" value="1">
                            <span class="checkbox-label">
                                <span>Air Conditioning</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_solar" value="1">
                            <span class="checkbox-label">
                                <span>Solar Power</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Security Features -->
                <div class="form-section">
                    <div class="section-title">
                        <h3>🔒 Security Features</h3>
                        <p>Safety and security amenities</p>
                    </div>
                    <div class="checkbox-grid">
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_remote_gate" value="1">
                            <span class="checkbox-label">
                                <span>Remote Gate</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_cctv" value="1">
                            <span class="checkbox-label">
                                <span>CCTV</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_wall_fence" value="1">
                            <span class="checkbox-label">
                                <span>Wall Fence</span>
                            </span>
                        </label>
                        <label class="checkbox-card">
                            <input type="checkbox" name="has_electric_fence" value="1">
                            <span class="checkbox-label">
                                <span>Electric Fence</span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Description - Tall Section -->
                <div class="form-section description-section">
                    <div class="section-title">
                        <h3>Description</h3>
                        <p>Describe your property in detail</p>
                    </div>
                    <div class="form-group">
                        <textarea name="description" rows="8" placeholder="Tell potential tenants about the property, amenities, nearby facilities, etc."></textarea>
                    </div>
                </div>

                <!-- Photos - Tall Section -->
                <div class="form-section photos-section">
                    <div class="section-title">
                        <h3>Photos</h3>
                        <p>Upload up to 10 images</p>
                    </div>
                    <label>Photos (Max 10 - JPG/PNG Only)</label>
                    <div id="drop-zone" class="drop-zone">
                        <div class="drop-zone-content">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="17 8 12 3 7 8"></polyline>
                                <line x1="12" y1="3" x2="12" y2="15"></line>
                            </svg>
                            <p class="drop-zone-text">Drag & drop images here or <span class="browse-link">browse</span></p>
                            <p class="drop-zone-hint">Maximum 10 images • JPG, PNG</p>
                        </div>
                        <input type="file" id="file-input" name="images[]" multiple accept="image/png, image/jpeg" style="display: none;">
                    </div>
                    <div id="preview-container" class="preview-container"></div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="submit-container">
                <button type="submit" name="submit" class="btn-submit">Publish Listing</button>
            </div>
        </form>
    </div>

    <style>
        /* Page Header */
        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .page-header h1 {
            font-size: 2rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .page-header p {
            font-size: 1rem;
            color: #64748b;
        }

        /* Form Grid - Masonry Style */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        /* Form Sections */
        .form-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 24px;
            transition: box-shadow 0.2s;
        }

        .form-section:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        .form-section.full-width {
            grid-column: span 2;
        }

        .form-section.description-section,
        .form-section.photos-section {
            grid-row: span 2;
        }

        /* Section Titles */
        .section-title {
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid #f1f5f9;
        }

        .section-title h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 4px;
        }

        .section-title p {
            font-size: 0.875rem;
            color: #94a3b8;
            margin: 0;
        }

        /* Property Grid (4 columns inside full-width section) */
        .property-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group:last-child {
            margin-bottom: 0;
        }

        /* Submit Container */
        .submit-container {
            text-align: center;
        }

        .btn-submit {
            min-width: 300px;
            padding: 14px 32px;
            font-size: 1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .property-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-section.full-width {
                grid-column: span 1;
            }

            .form-section.description-section,
            .form-section.photos-section {
                grid-row: span 1;
            }

            .property-grid {
                grid-template-columns: 1fr;
            }

            .btn-submit {
                width: 100%;
                min-width: auto;
            }
        }

        /* Username Validation Feedback */
        .username-feedback {
            margin-top: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .username-feedback.checking {
            color: #94a3b8;
        }

        .username-feedback.available {
            color: #059669;
        }

        .username-feedback.taken {
            color: #ef4444;
        }

        /* Drop Zone Styles */
        .drop-zone {
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 48px 24px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #f8fafc;
        }
        
        .drop-zone:hover {
            border-color: #3b82f6;
            background: #eff6ff;
        }
        
        .drop-zone.drag-over {
            border-color: #3b82f6;
            background: #eff6ff;
            border-style: solid;
        }
        
        .drop-zone-content svg {
            color: #94a3b8;
            margin-bottom: 16px;
        }
        
        .drop-zone-text {
            font-size: 0.9375rem;
            color: #475569;
            margin-bottom: 8px;
        }
        
        .browse-link {
            color: #3b82f6;
            font-weight: 500;
            cursor: pointer;
        }
        
        .drop-zone-hint {
            font-size: 0.8125rem;
            color: #94a3b8;
        }
        
        .preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }
        
        .preview-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            background: #f1f5f9;
        }
        
        .preview-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .preview-remove {
            position: absolute;
            top: 6px;
            right: 6px;
            width: 24px;
            height: 24px;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: background 0.2s;
        }
        
        .preview-remove:hover {
            background: #ef4444;
        }
        
        .file-count {
            margin-top: 12px;
            font-size: 0.8125rem;
            color: #64748b;
            text-align: center;
        }
    </style>

    <script>
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const previewContainer = document.getElementById('preview-container');
        const maxFiles = 10;
        let selectedFiles = [];

        dropZone.addEventListener('click', () => fileInput.click());

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.add('drag-over');
            });
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, () => {
                dropZone.classList.remove('drag-over');
            });
        });

        dropZone.addEventListener('drop', (e) => {
            const files = Array.from(e.dataTransfer.files);
            handleFiles(files);
        });

        fileInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });

        function handleFiles(files) {
            const imageFiles = files.filter(file => file.type.startsWith('image/'));
            const remainingSlots = maxFiles - selectedFiles.length;
            const filesToAdd = imageFiles.slice(0, remainingSlots);
            selectedFiles = [...selectedFiles, ...filesToAdd];
            updatePreview();
            updateFileInput();
        }

        function updatePreview() {
            previewContainer.innerHTML = '';
            
            selectedFiles.forEach((file, index) => {
                const reader = new FileReader();
                reader.onload = (e) => {
                    const div = document.createElement('div');
                    div.className = 'preview-item';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="preview-remove" onclick="removeFile(${index})">×</button>
                    `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);
            });
            
            if (selectedFiles.length > 0) {
                const countDiv = document.createElement('div');
                countDiv.className = 'file-count';
                countDiv.textContent = `${selectedFiles.length} of ${maxFiles} images selected`;
                previewContainer.appendChild(countDiv);
            }
        }

        function updateFileInput() {
            const dataTransfer = new DataTransfer();
            selectedFiles.forEach(file => dataTransfer.items.add(file));
            fileInput.files = dataTransfer.files;
        }

        window.removeFile = function(index) {
            selectedFiles.splice(index, 1);
            updatePreview();
            updateFileInput();
        };

        // Username Validation
        const usernameInput = document.getElementById('username-input');
        const usernameFeedback = document.getElementById('username-feedback');
        const submitBtn = document.querySelector('.btn-submit');
        
        if (usernameInput && usernameFeedback) {
            let usernameValid = false;
            let checkTimeout;

            usernameInput.addEventListener('input', function() {
                clearTimeout(checkTimeout);
                const username = this.value.trim();

                if (username.length < 3) {
                    usernameFeedback.className = 'username-feedback';
                    usernameFeedback.textContent = '';
                    usernameValid = false;
                    return;
                }

                usernameFeedback.className = 'username-feedback checking';
                usernameFeedback.textContent = 'Checking...';

                checkTimeout = setTimeout(() => {
                    checkUsername(username);
                }, 500);
            });

            function checkUsername(username) {
                fetch('check_username.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'username=' + encodeURIComponent(username)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        usernameFeedback.className = 'username-feedback available';
                        usernameFeedback.textContent = '✓ ' + data.message;
                        usernameValid = true;
                    } else {
                        usernameFeedback.className = 'username-feedback taken';
                        usernameFeedback.textContent = '✗ ' + data.message;
                        usernameValid = false;
                    }
                })
                .catch(error => {
                    console.error('Username check error:', error);
                    usernameFeedback.className = 'username-feedback';
                    usernameFeedback.textContent = '';
                });
            }

            // Prevent form submission if username is taken
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!usernameValid && usernameInput.value.trim().length >= 3) {
                    e.preventDefault();
                    usernameFeedback.className = 'username-feedback taken';
                    usernameFeedback.textContent = '✗ Please choose an available username';
                    usernameInput.focus();
                }
            });
        }
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>