<?php
include 'db.php';

// --- SMART SEARCH LOGIC (CURRENCY & FORMATTING SUPPORT) ---
$search_display = "";
if (isset($_GET['search'])) {
    $search_raw = $_GET['search'];
    $search_display = htmlspecialchars($search_raw); 
    
    $safe_search = mysqli_real_escape_string($conn, $search_raw);
    $keywords = explode(" ", $safe_search);
    
    // Stop words to ignore
    $stop_words = array('bedroom', 'bedrooms', 'bed', 'beds', 'apartment', 'house', 'home', 'rent', 'rental', 'in', 'at', 'the', 'for', 'flat');

    $conditions = array();

    foreach ($keywords as $word) {
        $word = trim($word);
        
        if (!empty($word) && !in_array(strtolower($word), $stop_words)) {
            
            // 1. CLEANING: Remove K, ZMK, $, and commas to check for numbers
            $clean_word = str_ireplace(array('K', 'ZMK', '$', ','), '', $word);

            // 2. IS IT A NUMBER? (e.g. "5000" from "K5,000")
            if (is_numeric($clean_word)) {
                
                // Check if the original word had a currency symbol
                $has_currency = (stripos($word, 'k') !== false || stripos($word, 'zmk') !== false);

                if ($has_currency) {
                    // CASE A: Currency detected (e.g. K5000) -> STRICT PRICE SEARCH
                    $conditions[] = "price LIKE '%$clean_word%'";
                } 
                elseif ($clean_word > 10) {
                    // CASE B: Large number, no currency (e.g. 5000) -> PRICE or ADDRESS
                    $conditions[] = "(price LIKE '%$clean_word%' OR address LIKE '%$clean_word%')";
                } 
                else {
                    // CASE C: Small number (<= 10), no currency -> BEDROOMS
                    $conditions[] = "bedrooms = '$clean_word'";
                }

            } else {
                // 3. TEXT SEARCH (e.g. "Parklands")
                $conditions[] = "(city LIKE '%$word%' 
                                  OR address LIKE '%$word%' 
                                  OR description LIKE '%$word%')";
            }
        }
    }

    // Build Query
    $sql = "SELECT * FROM properties WHERE ";
    if (count($conditions) > 0) {
        $sql .= implode(' AND ', $conditions);
    } else {
        $sql .= "1"; 
    }
    $sql .= " ORDER BY created_at DESC";

} else {
    $sql = "SELECT * FROM properties ORDER BY created_at DESC";
}

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housing App - Find a Home</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <!-- MESSAGE LOGIC -->
    <?php if(isset($_SESSION['msg'])): ?>
        <div class="notification-toast <?php echo $_SESSION['msg_type']; ?>" id="notification" style="display:none;">
            <?php echo $_SESSION['msg']; unset($_SESSION['msg']); unset($_SESSION['msg_type']); ?>
        </div>
        <script>
            setTimeout(function() {
                var box = document.getElementById('notification');
                if(box) {
                    box.style.display = 'block'; 
                    setTimeout(function() { box.style.opacity = '0'; setTimeout(function(){ box.remove(); }, 1000); }, 15000);
                }
            }, 3000); 
        </script>
    <?php endif; ?>


    <!-- Navbar -->
    <nav class="navbar">
        <div class="brand">
            <a href="index.php">
                <!-- Image Logo -->
                <img src="house-logo.png" alt="Logo">
                <!-- New Text Logo -->
                Rent Direct
            </a>
        </div>
        <div class="nav-links">
            <a href="index.php" style="margin-right: 20px;">Rentals</a>
            <a href="add_listing.php" class="nav-btn">+ List Property</a>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Discover Your Next Home.</h1>
            <p>Simple. Transparent. Direct from Owners.</p>
            
            <!-- Search Bar -->
            <form action="index.php" method="GET" class="search-bar-container">
                <!-- Input value is removed to clear box after search -->
                <input type="text" name="search" class="search-input" placeholder="Try 'Kitwe 2 bedrooms' or 'K5,000'...">
                <button type="submit" class="search-btn">Search</button>
            </form>
        </div>
    </div>

    <!-- FEATURES STRIP -->
    <div class="features-section">
        <div class="feature-box">
            <div class="feature-icon">🔑</div>
            <h3>Move in Faster</h3>
            <p>Skip the paperwork delays of agencies.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon">📈</div>
            <h3>Maximize Income</h3>
            <p>Landlords keep 100% of the rental income.</p>
        </div>
        <div class="feature-box">
            <div class="feature-icon">🛡️</div>
            <h3>Real Listings</h3>
            <p>Current photos and details from actual owners.</p>
        </div>
    </div>

    <!-- LISTINGS SECTION -->
    <div class="container-fluid" id="listings">
        <div class="section-header">
            <h2>Available Rentals</h2>
            <?php if($search_display): ?>
                <p class="text-muted">Results for "<?php echo $search_display; ?>" (<a href="index.php">View All</a>)</p>
            <?php endif; ?>
        </div>
        
        <div class="listing-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                        $prop_id = $row['id'];
                        $imgSql = "SELECT image_path FROM property_images WHERE property_id = '$prop_id' LIMIT 1";
                        $imgResult = mysqli_query($conn, $imgSql);
                        $imgData = mysqli_fetch_assoc($imgResult);
                        $thumbnail = $imgData ? "uploads/" . $imgData['image_path'] : "https://via.placeholder.com/300";
                    ?>
                    
                    <a href="property_details.php?id=<?php echo $row['id']; ?>" class="card-link">
                        <div class="card">
                            <div class="card-img-wrapper">
                                <img src="<?php echo $thumbnail; ?>" alt="House" loading="lazy">
                            </div>
                            <div class="card-content">
                                <div class="card-header-row">
                                    <h3><?php echo htmlspecialchars($row['city']); ?></h3>
                                    <span class="rating">★ New</span>
                                </div>
                                <p class="card-address"><?php echo htmlspecialchars($row['address']); ?></p>
                                <p class="card-meta"><?php echo htmlspecialchars($row['bedrooms']); ?> Beds • Apartment</p>
                                <div class="card-price-row">
                                    <span class="price-value">K <?php echo number_format($row['price']); ?></span>
                                    <span class="price-period">/mo</span>
                                </div>
                            </div>
                        </div>
                    </a>

                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <h3>No listings found</h3>
                    <p>We couldn't find any properties matching your search criteria.</p>
                    <a href="index.php" class="nav-btn" style="display:inline-block; margin-top:15px;">View All Rentals</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

</body>
</html>