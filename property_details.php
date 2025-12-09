<?php
include 'db.php';

// Prevent browser caching to ensure fresh image order
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies

// 1. Check if ID is provided
if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit();
}

// 2. Secure and Fetch Property Data
$id = mysqli_real_escape_string($conn, $_GET['id']);
$sql = "SELECT * FROM properties WHERE id = '$id'";
$result = mysqli_query($conn, $sql);
$property = mysqli_fetch_assoc($result);

// 3. If property doesn't exist, redirect
if (!$property) {
  header("Location: index.php");
  exit();
}

// 4. Fetch Images ordered by display_order
$sqlImg = "SELECT * FROM property_images WHERE property_id = '$id' ORDER BY display_order ASC, id ASC";
$resultImg = mysqli_query($conn, $sqlImg);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($property['city']); ?> Rental - Rent Direct</title>
  <link rel="stylesheet" href="style.css">
</head>

<body>

  <!-- MESSAGE LOGIC (Toast Notifications) -->
  <?php if (isset($_SESSION['msg'])): ?>
    <div class="notification-toast <?php echo $_SESSION['msg_type']; ?>" id="notification">
      <?php
      echo $_SESSION['msg'];
      unset($_SESSION['msg']);
      unset($_SESSION['msg_type']);
      ?>
    </div>
    <script>
      setTimeout(function() {
        var box = document.getElementById('notification');
        if (box) {
          box.style.opacity = '0';
          setTimeout(function() {
            box.remove();
          }, 1000);
        }
      }, 15000);
    </script>
  <?php endif; ?>

  <!-- Force reload when navigating back to this page -->
  <script>
    // Detect if page was loaded from cache (back button)
    window.addEventListener('pageshow', function(event) {
      if (event.persisted) {
        // Page was loaded from bfcache (back/forward cache)
        window.location.reload();
      }
    });
    
    // Alternative method for older browsers
    if (performance.navigation.type === 2) {
      // Page was accessed by navigating back
      window.location.reload();
    }
  </script>

  <!-- Navbar -->
  <nav class="navbar">
    <div class="brand">
      <a href="index.php">
        <img src="house-logo.png" alt="Logo">
        Rent Direct
      </a>
    </div>
    <div><a href="index.php">Home</a></div>
  </nav>

  <div class="container" style="margin-top: 40px;">

    <!-- 1. HEADER SECTION (Title & Address) -->
    <header class="property-header">
      <div>
        <h1 class="prop-title"><?php echo htmlspecialchars($property['bedrooms']); ?> Bedroom Apartment in <?php echo htmlspecialchars($property['city']); ?></h1>
        <p class="prop-address">📍 <?php echo htmlspecialchars($property['address']); ?></p>
      </div>
      <div class="prop-meta">
        <span class="badge"><?php echo htmlspecialchars($property['bedrooms']); ?> Beds</span>
        <span class="badge">City View</span>
      </div>
    </header>

    <div class="details-container">

      <!-- 2. LEFT COLUMN (Gallery & Description) -->
      <div class="left-column">
        <div class="gallery">
          <?php while ($img = mysqli_fetch_assoc($resultImg)): ?>
            <div class="gallery-item">
              <img src="uploads/<?php echo $img['image_path']; ?>" alt="Property Image">
            </div>
          <?php endwhile; ?>
          <?php if (mysqli_num_rows($resultImg) == 0): ?>
            <div class="gallery-placeholder" style="grid-column: span 2; padding: 50px; text-align: center; background: #eee; border-radius: 12px;">
              No images uploaded.
            </div>
          <?php endif; ?>
        </div>

        <div class="description-box">
          <h3>About this space</h3>
          <p><?php echo nl2br(htmlspecialchars($property['description'])); ?></p>

          <hr style="border: 0; border-top: 1px solid #eee; margin: 30px 0;">

          <div class="host-info">
            <div class="host-avatar">
              <!-- First letter of owner name -->
              <?php echo strtoupper(substr($property['owner_name'], 0, 1)); ?>
            </div>
            <div>
              <strong>Hosted by <?php echo htmlspecialchars($property['owner_name']); ?></strong>
              <!-- Date Format: dd-mm-yyyy -->
              <p class="text-muted">Listed on <?php echo date("d-m-Y", strtotime($property['created_at'])); ?></p>
            </div>
          </div>
        </div>
      </div>

      <!-- 3. RIGHT COLUMN (Sticky Sidebar) -->
      <div class="right-column-wrapper">
        <div class="info-sidebar sticky-sidebar">
          <div class="sidebar-header">
            <span class="price-large">K <?php echo number_format($property['price']); ?></span>
            <span class="per-month">/mo</span>
          </div>

          <div class="contact-info-box">
            <!-- LISTING REF -->
            <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px dashed #eee;">
              <span style="font-size: 0.8rem; color: #aaa; text-transform: uppercase; font-weight: 600;">Listing Ref</span>
              <!-- Formats ID 5 to #0005 -->
              <span style="display: block; font-size: 1.1rem; color: #2c3e50; font-weight: 700;">#<?php echo str_pad($property['id'], 4, '0', STR_PAD_LEFT); ?></span>
            </div>

            <!-- CONTACT PERSON -->
            <p class="contact-label">Contact Person</p>
            <h4><?php echo htmlspecialchars($property['contact_person']); ?></h4>

            <!-- Green Call Button -->
            <a href="tel:<?php echo htmlspecialchars($property['contact_number']); ?>" class="phone-btn">
              Contact Host
            </a>

            <p class="text-small mt-2">Phone: <?php echo htmlspecialchars($property['contact_number']); ?></p>
          </div>

          <!-- Owner Zone (Collapsible) -->
          <div class="owner-zone-collapsed">
            <details>
              <summary>Manage Listing (Owner)</summary>
              <div class="owner-content">
                <form action="verify_admin.php" method="POST" class="delete-form-stacked">
                  <input type="hidden" name="id" value="<?php echo $property['id']; ?>">
                  <input type="text" name="username" id="admin-username" placeholder="Enter Username" required style="margin-bottom: 10px;">
                  <input type="password" name="password_check" id="admin-password" placeholder="Enter Password" required>
                  <button type="submit" class="phone-btn full-width" id="admin-btn" disabled style="opacity: 0.5; cursor: not-allowed;">Admin</button>
                </form>
                <script>
                  // Enable button only when both username and password are entered
                  function checkFields() {
                    var username = document.getElementById('admin-username').value;
                    var password = document.getElementById('admin-password').value;
                    var btn = document.getElementById('admin-btn');
                    
                    if (username.length > 0 && password.length > 0) {
                      btn.disabled = false;
                      btn.style.opacity = '1';
                      btn.style.cursor = 'pointer';
                    } else {
                      btn.disabled = true;
                      btn.style.opacity = '0.5';
                      btn.style.cursor = 'not-allowed';
                    }
                  }
                  
                  document.getElementById('admin-username').addEventListener('input', checkFields);
                  document.getElementById('admin-password').addEventListener('input', checkFields);
                </script>
              </div>
            </details>
          </div>
        </div>
      </div>

    </div>
  </div>

  <?php include 'footer.php'; ?>

</body>

</html>