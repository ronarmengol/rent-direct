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
    // 2. No Hashing (DEV MODE)
    $hashed_password = $pass;

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

    <div class="container" style="max-width: 1200px;">
        <div class="page-header">
            <h1>List Your Property</h1>
            <p>Fill in the details below to create your rental listing</p>
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

                <!-- Full Width - Property Details -->
                <div class="form-section full-width">
                    <div class="section-title">
                        <h3>Property Details</h3>
                        <p>Tell us about your property</p>
                    </div>
                    
                    <div class="property-grid">
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
                            <label>Bedrooms</label>
                            <select name="bedrooms">
                                <option value="1">1 Bedroom</option>
                                <option value="2">2 Bedrooms</option>
                                <option value="3">3 Bedrooms</option>
                                <option value="4">4 Bedrooms</option>
                                <option value="5">5+ Bedrooms</option>
                            </select>
                        </div>
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