<?php
include 'db.php';

// Check if ID is provided
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

// Fetch property data
$id = mysqli_real_escape_string($conn, $_GET['id']);
$sql = "SELECT * FROM properties WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$property = mysqli_fetch_assoc($result);

// If property doesn't exist, redirect
if (!$property) {
    header("Location: index.php");
    exit();
}

// Fetch images ordered by display_order
$sqlImg = "SELECT * FROM property_images WHERE property_id = '$id' ORDER BY display_order ASC, id ASC";
$resultImg = mysqli_query($conn, $sqlImg);
$imageCount = mysqli_num_rows($resultImg);
$maxImages = 10;
$canUpload = $imageCount < $maxImages;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Listing #<?php echo str_pad($property['id'], 4, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Modern Admin Styles */
        .admin-page {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            min-height: 100vh;
        }
        
        .admin-nav {
            background: #1e293b;
            padding: 0 24px;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .admin-nav .logo {
            color: white;
            font-weight: 600;
            font-size: 1rem;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .admin-nav .logo img {
            height: 28px;
        }
        
        .admin-nav-links {
            display: flex;
            gap: 24px;
        }
        
        .admin-nav-links a {
            color: #94a3b8;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 400;
            transition: color 0.2s;
        }
        
        .admin-nav-links a:hover {
            color: white;
        }
        
        
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 32px 24px 80px;
        }
        
        /* Admin Grid Layout */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
        }
        
        .grid-item {
            display: contents;
        }
        
        .grid-item .admin-card {
            grid-column: span 1;
        }
        
        .tall-item {
            grid-row: span 2;
        }
        
        @media (max-width: 968px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
            
            .tall-item {
                grid-row: span 1;
            }
        }
        
        .admin-header {
            margin-bottom: 32px;
        }
        
        .admin-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0 0 4px 0;
        }
        
        .admin-header .subtitle {
            font-size: 0.875rem;
            color: #64748b;
            margin: 0;
        }
        
        .admin-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        
        .admin-card-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-card-header h2 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }
        
        .admin-card-body {
            padding: 24px;
        }
        
        .save-link {
            color: #3b82f6;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            transition: color 0.2s;
        }
        
        .save-link:hover {
            color: #2563eb;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        
        .form-group label {
            font-size: 0.8125rem;
            font-weight: 500;
            color: #64748b;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 10px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9375rem;
            font-weight: 500;
            color: #0f172a;
            transition: border-color 0.2s, box-shadow 0.2s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
            line-height: 1.6;
        }
        
        .price-input {
            color: #059669 !important;
        }
        
        /* Images Section */
        .images-header-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .image-count {
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
            background: #f1f5f9;
            padding: 4px 10px;
            border-radius: 12px;
        }
        
        .add-link {
            color: #3b82f6;
            font-size: 0.8125rem;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .add-link:hover {
            color: #2563eb;
        }
        
        /* Image Gallery - Masonry Layout */
        .image-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
        }
        
        .image-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            cursor: grab;
            transition: all 0.3s ease;
            background: #f1f5f9;
        }
        
        /* First image (cover) - larger */
        .image-item:first-child {
            grid-column: span 2;
            grid-row: span 2;
        }
        
        .image-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .image-item.dragging {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
        .image-item.drag-over {
            border: 2px dashed #3b82f6;
            background: #eff6ff;
        }
        
        /* Image number badge */
        .image-number {
            position: absolute;
            top: 8px;
            left: 8px;
            width: 28px;
            height: 28px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            color: white;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            z-index: 3;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Cover badge for first image */
        .image-item:first-child .image-number {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            width: auto;
            padding: 0 10px;
            font-size: 0.6875rem;
            letter-spacing: 0.5px;
        }
        
        .image-item:first-child .image-number::before {
            content: '★ ';
            margin-right: 2px;
        }
        
        /* Image thumbnail */
        .image-thumb {
            width: 100%;
            height: 100%;
            position: relative;
        }
        
        .image-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        
        .image-item:hover .image-thumb img {
            transform: scale(1.05);
        }
        
        /* Gradient overlay on hover */
        .image-thumb::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.4), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .image-item:hover .image-thumb::after {
            opacity: 1;
        }
        
        /* Image info - show on hover */
        .image-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 12px;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            transform: translateY(100%);
            transition: transform 0.3s ease;
            z-index: 2;
        }
        
        .image-item:hover .image-info {
            transform: translateY(0);
        }
        
        .image-info p {
            margin: 0;
            font-size: 0.6875rem;
            color: white;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Image actions */
        .image-actions {
            position: absolute;
            top: 8px;
            right: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 3;
        }
        
        .image-item:hover .image-actions {
            opacity: 1;
        }
        
        .delete-icon {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: rgba(239, 68, 68, 0.9);
            backdrop-filter: blur(8px);
            color: white;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 1.125rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        .delete-icon:hover {
            background: #dc2626;
            transform: scale(1.1);
        }
        
        .drag-icon {
            color: white;
            font-size: 1rem;
            cursor: grab;
            padding: 6px;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            border-radius: 8px;
            line-height: 1;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
        }
        
        /* Illustration Container - Separate Grid Item */
        .illustration-container {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
            opacity: 0.4;
        }
        
        .illustration-container img {
            max-width: 100%;
            height: auto;
        }
        
        @media (max-width: 968px) {
            .illustration-container {
                display: none;
            }
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 24px;
            color: #64748b;
        }
        
        .empty-state p {
            margin: 0 0 16px 0;
            font-size: 0.875rem;
        }
        
        /* Description helper */
        .form-helper {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 8px;
        }
        
        /* Danger Zone - Full Width */
        .danger-card-full {
            grid-column: 1 / -1;
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 12px;
            padding: 24px;
        }
        
        .danger-card-full .admin-card-body {
            padding: 0;
        }
        
        .danger-text {
            font-size: 0.875rem;
            color: #7f1d1d;
            margin: 0 0 20px 0;
            line-height: 1.6;
        }
        
        .danger-form {
            display: flex;
            gap: 12px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .danger-form input {
            padding: 10px 12px;
            border: 1px solid #fecaca;
            border-radius: 8px;
            font-size: 0.875rem;
            flex: 1;
            min-width: 200px;
            max-width: 300px;
        }
        
        .delete-btn-slim {
            background: #dc2626;
            border: none;
            color: white;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s;
        }
        
        .delete-btn-slim:hover {
            background: #dc2626;
            color: white;
            border-color: #dc2626;
        }
        
        /* Fixed Save Button - Minimal */
        .floating-save {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1000;
        }
        
        .floating-save button {
            background: #e2e8f0;
            color: #64748b;
            padding: 12px 20px;
            border: none;
            border-radius: 24px;
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: not-allowed;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .floating-save button.has-changes {
            background: #3b82f6;
            color: white;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        
        .floating-save button.has-changes:hover {
            background: #2563eb;
            transform: translateY(-1px);
        }
        
        .saved-toast {
            display: none;
            background: #059669;
            color: white;
            padding: 12px 20px;
            border-radius: 24px;
            font-size: 0.8125rem;
            font-weight: 500;
            position: absolute;
            bottom: 0;
            right: 0;
        }
        
        @media (max-width: 640px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            .danger-form {
                flex-direction: column;
                align-items: stretch;
            }
            .danger-form input {
                width: 100%;
            }
        }
        
        /* Modal Overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(4px);
            z-index: 9999;
            animation: fadeIn 0.2s ease-out;
        }
        
        .modal-overlay.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Modal Content */
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 32px;
            max-width: 440px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideUp 0.3s ease-out;
        }
        
        .modal-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .modal-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .modal-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
        }
        
        .modal-body {
            margin-bottom: 24px;
        }
        
        .modal-body p {
            color: #64748b;
            font-size: 0.9375rem;
            line-height: 1.6;
            margin: 0 0 20px 0;
        }
        
        .modal-input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9375rem;
            transition: all 0.2s;
        }
        
        .modal-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .modal-footer {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .modal-btn {
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }
        
        .modal-btn-cancel {
            background: #f1f5f9;
            color: #64748b;
        }
        
        .modal-btn-cancel:hover {
            background: #e2e8f0;
        }
        
        .modal-btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .modal-btn-delete:hover {
            background: #dc2626;
        }
        
        .modal-btn-delete:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Upload Progress Indicator */
        .upload-progress {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            z-index: 10000;
            min-width: 320px;
            text-align: center;
        }
        
        .upload-progress.active {
            display: block;
        }
        
        .upload-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 16px;
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .upload-progress h3 {
            font-size: 1.125rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 8px;
        }
        
        .upload-progress p {
            color: #64748b;
            font-size: 0.875rem;
            margin-bottom: 20px;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #f1f5f9;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #2563eb);
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 0.75rem;
            color: #94a3b8;
            font-weight: 500;
        }
        
        /* Toast Notification */
        .toast-notification {
            position: fixed;
            top: 24px;
            right: 24px;
            background: white;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: none;
            align-items: center;
            gap: 12px;
            z-index: 10001;
            min-width: 300px;
            animation: slideInRight 0.3s ease-out;
        }
        
        .toast-notification.active {
            display: flex;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .toast-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .toast-notification.error .toast-icon {
            background: #fef2f2;
            color: #ef4444;
        }
        
        .toast-notification.success .toast-icon {
            background: #f0fdf4;
            color: #22c55e;
        }
        
        .toast-content {
            flex: 1;
        }
        
        .toast-content strong {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 2px;
        }
        
        .toast-content p {
            font-size: 0.8125rem;
            color: #64748b;
            margin: 0;
        }
    </style>
</head>
<body class="admin-page">

    <!-- MESSAGE LOGIC -->
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="notification-toast <?php echo $_SESSION['msg_type']; ?>" id="notification">
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
        </div>
        <script>
            setTimeout(function() {
                var box = document.getElementById('notification');
                if(box) {
                    box.style.opacity = '0';
                    setTimeout(function(){ box.remove(); }, 1000);
                }
            }, 5000);
        </script>
    <?php endif; ?>

    <!-- Navbar -->
    <nav class="admin-nav">
        <a href="index.php" class="logo">
            <img src="house-logo.png" alt="Logo">
            Rent Direct
        </a>
        <div class="admin-nav-links">
            <a href="property_details.php?id=<?php echo $property['id']; ?>">View Listing</a>
            <a href="index.php">Home</a>
        </div>
    </nav>



    <div class="admin-container">
        
        <!-- Header -->
        <header class="admin-header">
            <h1>Manage Listing</h1>
            <p class="subtitle">Listing #<?php echo str_pad($property['id'], 4, '0', STR_PAD_LEFT); ?> · <?php echo htmlspecialchars($property['city']); ?></p>
        </header>

        <!-- Grid Layout -->
        <div class="admin-grid">
            
            <!-- Basic Info Card -->
            <form id="property-details-form" class="grid-item">
                <input type="hidden" name="update_section" value="basic">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>📍 Basic Info</h2>
                        <button type="submit" class="save-link">Save changes</button>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" value="<?php echo htmlspecialchars($property['city']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Address</label>
                                <input type="text" name="address" value="<?php echo htmlspecialchars($property['address']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Bedrooms</label>
                                <select name="bedrooms" required>
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($property['bedrooms'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Bedroom<?php echo ($i > 1) ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Bathrooms</label>
                                <select name="bathrooms" required>
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($property['bathrooms'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Bathroom<?php echo ($i > 1) ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Toilets</label>
                                <select name="toilets" required>
                                    <?php for($i = 1; $i <= 10; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($property['toilets'] == $i) ? 'selected' : ''; ?>><?php echo $i; ?> Toilet<?php echo ($i > 1) ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Parking Spaces</label>
                                <select name="carpark" required>
                                    <?php for($i = 0; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo ($property['carpark'] == $i) ? 'selected' : ''; ?>><?php echo $i == 0 ? 'No Parking' : $i . ' Space' . ($i > 1 ? 's' : ''); ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Geyser Type</label>
                                <select name="geyser_type" required>
                                    <option value="none" <?php echo ($property['geyser_type'] == 'none') ? 'selected' : ''; ?>>None</option>
                                    <option value="electric" <?php echo ($property['geyser_type'] == 'electric') ? 'selected' : ''; ?>>Electric</option>
                                    <option value="solar" <?php echo ($property['geyser_type'] == 'solar') ? 'selected' : ''; ?>>Solar</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Available From</label>
                                <input type="text" name="available_from" id="available_from" 
                                       value="<?php 
                                       if (!empty($property['available_from']) && $property['available_from'] != '0000-00-00') {
                                           $date = DateTime::createFromFormat('Y-m-d', $property['available_from']);
                                           echo $date ? $date->format('d/m/Y') : '';
                                       }
                                       ?>" 
                                       placeholder="DD/MM/YYYY" 
                                       readonly
                                       style="cursor: pointer;">
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Amenities Card -->
            <form id="amenities-form" class="grid-item">
                <input type="hidden" name="update_section" value="amenities">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>✨ Amenities & Security</h2>
                        <button type="submit" class="save-link">Save changes</button>
                    </div>
                    <div class="admin-card-body">
                        <div style="margin-bottom: 20px;">
                            <h3 style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 12px;">Features</h3>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_pool" value="1" <?php echo ($property['has_pool'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Swimming Pool</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_water_tank" value="1" <?php echo ($property['has_water_tank'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Water Tank</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_ac" value="1" <?php echo ($property['has_ac'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Air Conditioning</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_solar" value="1" <?php echo ($property['has_solar'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Solar Power</span>
                                </label>
                            </div>
                        </div>
                        
                        <div>
                            <h3 style="font-size: 0.875rem; font-weight: 600; color: #475569; margin-bottom: 12px;">Security</h3>
                            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_remote_gate" value="1" <?php echo ($property['has_remote_gate'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Remote Gate</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_cctv" value="1" <?php echo ($property['has_cctv'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>CCTV</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_wall_fence" value="1" <?php echo ($property['has_wall_fence'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Wall Fence</span>
                                </label>
                                <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 12px; background: #f8fafc; border-radius: 8px; transition: all 0.2s;">
                                    <input type="checkbox" name="has_electric_fence" value="1" <?php echo ($property['has_electric_fence'] == 1) ? 'checked' : ''; ?> style="width: auto; cursor: pointer;">
                                    <span>Electric Fence</span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Contact & Pricing Card -->
            <form id="contact-pricing-form" class="grid-item">
                <input type="hidden" name="update_section" value="contact">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>💰 Contact & Pricing</h2>
                        <button type="submit" class="save-link">Save changes</button>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Monthly Rent (K)</label>
                                <input type="number" name="price" value="<?php echo $property['price']; ?>" required min="0" step="0.01" class="price-input">
                            </div>
                            <div class="form-group">
                                <label>Contact Person</label>
                                <input type="text" name="contact_person" value="<?php echo htmlspecialchars($property['contact_person']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="text" name="contact_number" value="<?php echo htmlspecialchars($property['contact_number']); ?>" required>
                            </div>
                        </div>
                    </div>
                </div>
            </form>


            <!-- Description Card -->
            <form id="description-form" class="grid-item">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Description</h2>
                        <button type="submit" class="save-link">Save changes</button>
                    </div>
                    <div class="admin-card-body">
                        <div class="form-group">
                            <textarea name="description" rows="8" required><?php echo htmlspecialchars($property['description']); ?></textarea>
                            <p class="form-helper">Describe your property to attract potential tenants</p>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Images Card - Spans 2 rows -->
            <div class="admin-card grid-item tall-item">
                <div class="admin-card-body">
                    <!-- Controls above masonry -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div style="display: flex; align-items: center; gap: 16px;">
                            <span style="font-size: 0.875rem; color: #64748b; font-weight: 500;">Images <?php echo $imageCount; ?>/<?php echo $maxImages; ?></span>
                            <button id="save-changes-btn" onclick="manualSave()" class="save-link" style="opacity: 0.4; cursor: not-allowed;" type="button" disabled>
                                Save order
                            </button>
                            <span id="saved-indicator" style="display: none; color: #059669; font-size: 0.8125rem; font-weight: 500;">✓ Saved</span>
                        </div>
                        
                        <?php if($canUpload): ?>
                            <a class="add-link" onclick="document.getElementById('image-upload-input').click()">
                                + Add
                            </a>
                            <form id="upload-form" style="display: none;">
                                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                                <input type="file" id="image-upload-input" name="images[]" multiple accept="image/jpeg,image/jpg,image/png" onchange="uploadImages()">
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php if($imageCount > 0): ?>
                        <p style="font-size: 0.75rem; color: #94a3b8; margin: 0 0 16px 0;">Drag to reorder · First image is the cover</p>
                        
                        <!-- Gallery and Illustration Container -->
                        <div class="gallery-with-illustration">
                            <div id="image-gallery" class="image-list">
                                <?php 
                                mysqli_data_seek($resultImg, 0);
                                $idx = 1;
                                while($img = mysqli_fetch_assoc($resultImg)): 
                                ?>
                                    <div class="image-item" draggable="true" data-image-id="<?php echo $img['id']; ?>">
                                        <div class="image-number"><?php echo $idx++; ?></div>
                                        <div class="image-thumb">
                                            <img src="uploads/<?php echo $img['image_path']; ?>" alt="Property Image">
                                        </div>
                                        <div class="image-info">
                                            <p><?php echo basename($img['image_path']); ?></p>
                                        </div>
                                        <div class="image-actions">
                                            <button type="button" class="delete-icon" onclick="deleteImage(<?php echo $img['id']; ?>, '<?php echo htmlspecialchars($img['image_path']); ?>')" title="Delete">×</button>
                                            <span class="drag-icon">⋮⋮</span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <p>No images uploaded yet</p>
                            <a class="add-link" onclick="document.getElementById('image-upload-input').click()">+ Add images</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Decorative Illustration - Separate Grid Item -->
            <div class="illustration-container">
                <img src="property_illustration.webp" alt="Property Illustration">
            </div>


            <!-- Account Credentials Card - Full Width -->
            <form id="credentials-form" style="grid-column: 1 / -1;">
                <input type="hidden" name="property_id" value="<?php echo $property['id']; ?>">
                
                <div class="admin-card">
                    <div class="admin-card-header">
                        <h2>Account Credentials</h2>
                        <button type="submit" class="save-link">Update</button>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>New Username</label>
                                <input type="text" name="new_username" placeholder="<?php echo htmlspecialchars($property['owner_name']); ?>">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>New Password</label>
                                <input type="password" name="new_password" placeholder="Leave blank to keep current">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Current Password</label>
                                <input type="password" name="current_password" required placeholder="Required to update">
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <!-- Danger Zone Card - Full Width -->
            <div class="danger-card-full">
                <div class="admin-card-body">
                    <h2 style="color: #dc2626; font-size: 1.125rem; font-weight: 600; margin-bottom: 12px;">⚠️ Danger Zone</h2>
                    <p class="danger-text">Permanently delete this listing and all associated images. This action cannot be undone.</p>
                    <form action="delete_listing.php" method="POST" class="danger-form" onsubmit="return confirm('Delete this listing permanently?');">
                        <input type="hidden" name="id" value="<?php echo $property['id']; ?>">
                        <input type="password" name="password_check" placeholder="Enter password to confirm" required>
                        <button type="submit" class="delete-btn-slim">Delete listing permanently</button>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <!-- Upload Progress Indicator -->
    <div id="upload-progress" class="upload-progress">
        <div class="upload-icon">📤</div>
        <h3>Uploading Images</h3>
        <p>Please wait while your images are being uploaded...</p>
        <div class="progress-bar-container">
            <div id="progress-bar" class="progress-bar"></div>
        </div>
        <div class="progress-text" id="progress-text">0%</div>
    </div>

    <!-- Delete Image Modal -->
    <div id="delete-modal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-icon">🗑️</div>
                <h3>Delete Image</h3>
            </div>
            <div class="modal-body">
                <p>Enter your password to confirm deletion of this image.</p>
                <input type="password" id="delete-password" class="modal-input" placeholder="Enter password" autocomplete="off">
            </div>
            <div class="modal-footer">
                <button class="modal-btn modal-btn-cancel" onclick="closeDeleteModal()">Cancel</button>
                <button class="modal-btn modal-btn-delete" id="confirm-delete-btn" disabled onclick="confirmDelete()">Delete Image</button>
            </div>
        </div>
    </div>

    <script>
        // AJAX Image Upload with Progress
        function uploadImages() {
            const fileInput = document.getElementById('image-upload-input');
            const files = fileInput.files;
            
            if (files.length === 0) return;
            
            const formData = new FormData();
            formData.append('property_id', '<?php echo $property['id']; ?>');
            
            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }
            
            const progressDiv = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            
            // Show progress indicator
            progressDiv.classList.add('active');
            
            const xhr = new XMLHttpRequest();
            
            // Track upload progress
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = Math.round((e.loaded / e.total) * 100);
                    progressBar.style.width = percentComplete + '%';
                    progressText.textContent = percentComplete + '%';
                }
            });
            
            // Handle completion
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    progressBar.style.width = '100%';
                    progressText.textContent = '100%';
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    progressDiv.classList.remove('active');
                    showToast('error', 'Upload Failed', 'Please try again.');
                }
            });
            
            // Handle errors
            xhr.addEventListener('error', function() {
                progressDiv.classList.remove('active');
                showToast('error', 'Upload Failed', 'Please try again.');
            });
            
            xhr.open('POST', 'upload_images.php', true);
            xhr.send(formData);
            
            // Reset file input
            fileInput.value = '';
        }
        
        // Delete Image Modal
        let pendingDeleteImageId = null;
        let pendingDeleteImagePath = null;
        
        function deleteImage(imageId, imagePath) {
            pendingDeleteImageId = imageId;
            pendingDeleteImagePath = imagePath;
            document.getElementById('delete-modal').classList.add('active');
            document.getElementById('delete-password').value = '';
            document.getElementById('delete-password').focus();
            document.getElementById('confirm-delete-btn').disabled = true;
        }
        
        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.remove('active');
            pendingDeleteImageId = null;
            pendingDeleteImagePath = null;
        }
        
        // Enable delete button when password is entered
        document.getElementById('delete-password').addEventListener('input', function() {
            document.getElementById('confirm-delete-btn').disabled = this.value.length === 0;
        });
        
        // Allow Enter key to confirm
        document.getElementById('delete-password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value.length > 0) {
                confirmDelete();
            }
        });
        
        // Close modal on overlay click
        document.getElementById('delete-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        
        function confirmDelete() {
            const password = document.getElementById('delete-password').value;
            
            console.log('confirmDelete called');
            console.log('Password:', password);
            console.log('Pending Image ID:', pendingDeleteImageId);
            console.log('Property ID:', <?php echo $property['id']; ?>);
            
            if (!password || !pendingDeleteImageId) {
                console.log('Validation failed - missing password or image ID');
                return;
            }
            
            // Store values BEFORE closing modal (which resets them)
            const imageIdToDelete = pendingDeleteImageId;
            const passwordToUse = password;
            
            // Close modal and show progress
            closeDeleteModal();
            
            const progressDiv = document.getElementById('upload-progress');
            const progressBar = document.getElementById('progress-bar');
            const progressText = document.getElementById('progress-text');
            const progressTitle = progressDiv.querySelector('h3');
            const progressDesc = progressDiv.querySelector('p');
            const progressIcon = progressDiv.querySelector('.upload-icon');
            
            // Change to deletion mode
            progressTitle.textContent = 'Deleting Image';
            progressDesc.textContent = 'Please wait while the image is being deleted...';
            progressIcon.textContent = '🗑️';
            progressBar.style.width = '50%';
            progressText.textContent = 'Processing...';
            progressDiv.classList.add('active');
            
            const requestData = {
                image_id: imageIdToDelete,
                property_id: <?php echo $property['id']; ?>,
                password: passwordToUse
            };
            
            console.log('Sending request with data:', requestData);
            
            fetch('delete_image.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                if (data.success) {
                    progressBar.style.width = '100%';
                    progressText.textContent = 'Complete!';
                    
                    setTimeout(function() {
                        window.location.reload();
                    }, 500);
                } else {
                    progressDiv.classList.remove('active');
                    
                    // Reset to upload mode
                    progressTitle.textContent = 'Uploading Images';
                    progressDesc.textContent = 'Please wait while your images are being uploaded...';
                    progressIcon.textContent = '📤';
                    progressBar.style.width = '0%';
                    progressText.textContent = '0%';
                    
                    // Show error toast
                    showToast('error', 'Deletion Failed', data.error || 'Failed to delete image');
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                progressDiv.classList.remove('active');
                
                // Reset to upload mode
                progressTitle.textContent = 'Uploading Images';
                progressDesc.textContent = 'Please wait while your images are being uploaded...';
                progressIcon.textContent = '📤';
                progressBar.style.width = '0%';
                progressText.textContent = '0%';
                
                showToast('error', 'Error', 'An error occurred while deleting the image');
            });
        }
    </script>

    <!-- Flatpickr Library -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        
        // Toast Notification System
        function showToast(type, title, message) {
            const existingToast = document.querySelector('.toast-notification');
            if (existingToast) {
                existingToast.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = `toast-notification ${type}`;
            
            const icon = type === 'error' ? '❌' : '✅';
            
            toast.innerHTML = `
                <div class="toast-icon">${icon}</div>
                <div class="toast-content">
                    <strong>${title}</strong>
                    <p>${message}</p>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('active');
            }, 10);
            
            setTimeout(() => {
                toast.classList.remove('active');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        }

        // Drag and Drop
        const gallery = document.getElementById('image-gallery');
        const saveBtn = document.getElementById('save-changes-btn');
        const savedIndicator = document.getElementById('saved-indicator');
        let hasUnsavedChanges = false;

        if (gallery) {
            const items = gallery.querySelectorAll('.image-item');
            let draggedElement = null;

            items.forEach(item => {
                item.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                item.addEventListener('dragend', function() {
                    this.classList.remove('dragging');
                    document.querySelectorAll('.drag-over').forEach(el => el.classList.remove('drag-over'));
                    updateImageNumbers();
                    markAsUnsaved();
                });

                item.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    if (this === draggedElement) return;
                    const bounding = this.getBoundingClientRect();
                    const offset = e.clientY - bounding.top;
                    if (offset > bounding.height / 2) {
                        this.parentNode.insertBefore(draggedElement, this.nextSibling);
                    } else {
                        this.parentNode.insertBefore(draggedElement, this);
                    }
                });

                item.addEventListener('dragenter', function(e) {
                    e.preventDefault();
                    if (this !== draggedElement) this.classList.add('drag-over');
                });

                item.addEventListener('dragleave', function(e) {
                    if (e.target === this) this.classList.remove('drag-over');
                });
            });

            function updateImageNumbers() {
                gallery.querySelectorAll('.image-item').forEach((item, index) => {
                    const num = item.querySelector('.image-number');
                    if (num) num.textContent = index + 1;
                });
            }

            function markAsUnsaved() {
                hasUnsavedChanges = true;
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.style.opacity = '1';
                    saveBtn.style.cursor = 'pointer';
                }
                if (savedIndicator) {
                    savedIndicator.style.display = 'none';
                }
            }

            function markAsSaved() {
                hasUnsavedChanges = false;
                if (saveBtn) {
                    saveBtn.disabled = true;
                    saveBtn.style.opacity = '0.4';
                    saveBtn.style.cursor = 'not-allowed';
                }
                if (savedIndicator) {
                    savedIndicator.style.display = 'inline';
                    setTimeout(() => { savedIndicator.style.display = 'none'; }, 2000);
                }
            }

            window.saveImageOrder = function() {
                const imageIds = [];
                gallery.querySelectorAll('.image-item').forEach((item, index) => {
                    imageIds.push({ id: item.getAttribute('data-image-id'), order: index });
                });

                fetch('update_image_order.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ property_id: <?php echo $property['id']; ?>, images: imageIds })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) markAsSaved();
                });
            };
        }

        function manualSave() {
            if (hasUnsavedChanges && window.saveImageOrder) {
                window.saveImageOrder();
            }
        }
        
        // AJAX Form Submissions
        
        // Property Details Form
        document.getElementById('property-details-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            button.textContent = 'Saving...';
            button.disabled = true;
            
            fetch('update_property.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('success', 'Saved', 'Property details updated successfully');
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update property details');
            });
        });
        
        // Description Form
        document.getElementById('description-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            button.textContent = 'Saving...';
            button.disabled = true;
            
            fetch('update_description.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('success', 'Saved', 'Description updated successfully');
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update description');
            });
        });
        
        // Account Credentials Form
        document.getElementById('credentials-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            
            button.textContent = 'Updating...';
            button.disabled = true;
            
            fetch('update_credentials.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                
                // Check if update was successful (basic check)
                if (data.includes('success') || data.includes('updated')) {
                    showToast('success', 'Updated', 'Credentials updated successfully');
                    // Clear the form fields
                    this.querySelector('input[name="new_username"]').value = '';
                    this.querySelector('input[name="new_password"]').value = '';
                    this.querySelector('input[name="current_password"]').value = '';
                } else {
                    showToast('error', 'Error', 'Failed to update credentials. Please check your current password.');
                }
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update credentials');
            });
        });

        // Handle Basic Info Form (Property Details)
        document.getElementById('property-details-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.textContent = 'Saving...';
            button.disabled = true;

            const formData = new FormData(this);
            
            // Convert date from DD/MM/YYYY to YYYY-MM-DD
            const dateInput = document.getElementById('available_from');
            if (dateInput && dateInput.value) {
                const dateParts = dateInput.value.split('/');
                if (dateParts.length === 3) {
                    const mysqlDate = `${dateParts[2]}-${dateParts[1]}-${dateParts[0]}`;
                    formData.set('available_from', mysqlDate);
                }
            }
            
            fetch('update_property.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                if (data.success) {
                    showToast('success', 'Success', 'Property details updated successfully');
                } else {
                    showToast('error', 'Error', data.message || 'Failed to update property details');
                }
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update property details');
            });
        });

        // Initialize Flatpickr for date picker
        const dateInput = document.getElementById('available_from');
        if (dateInput) {
            flatpickr(dateInput, {
                dateFormat: "d/m/Y",
                altInput: false,
                allowInput: true,
                locale: {
                    firstDayOfWeek: 1
                }
            });
        }

        // Handle Amenities & Security Form
        document.getElementById('amenities-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.textContent = 'Saving...';
            button.disabled = true;

            const formData = new FormData(this);
            
            fetch('update_property.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                if (data.success) {
                    showToast('success', 'Success', 'Amenities updated successfully');
                } else {
                    showToast('error', 'Error', data.message || 'Failed to update amenities');
                }
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update amenities');
            });
        });

        // Handle Contact & Pricing Form
        document.getElementById('contact-pricing-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.textContent;
            button.textContent = 'Saving...';
            button.disabled = true;

            const formData = new FormData(this);
            
            fetch('update_property.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.textContent = originalText;
                button.disabled = false;
                if (data.success) {
                    showToast('success', 'Success', 'Contact & pricing updated successfully');
                } else {
                    showToast('error', 'Error', data.message || 'Failed to update contact & pricing');
                }
            })
            .catch(error => {
                button.textContent = originalText;
                button.disabled = false;
                showToast('error', 'Error', 'Failed to update contact & pricing');
            });
        });
    </script>

    <?php include 'footer.php'; ?>

</body>
</html>
