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
                    // CASE A: Currency detected (e.g. K5000) -> PRICE RANGE SEARCH
                    if ($clean_word > 1000) {
                        // Show properties with rent <= searched amount
                        $conditions[] = "price <= '$clean_word'";
                    } else {
                        // Exact match for small amounts
                        $conditions[] = "price LIKE '%$clean_word%'";
                    }
                } 
                elseif ($clean_word > 1000) {
                    // CASE B: Large number, no currency (e.g. 5000) -> PRICE RANGE or ADDRESS
                    $conditions[] = "(price <= '$clean_word' OR address LIKE '%$clean_word%')";
                } 
                else {
                    // CASE C: Small number (<= 1000), no currency -> BEDROOMS
                    $conditions[] = "bedrooms = '$clean_word'";
                }

            } else {
                // 3. AMENITY KEYWORDS
                $word_lower = strtolower($word);
                
                // Check for amenity keywords
                if (in_array($word_lower, ['pool', 'swimming', 'swim'])) {
                    $conditions[] = "has_pool = 1";
                } 
                elseif (in_array($word_lower, ['water', 'tank', 'watertank'])) {
                    $conditions[] = "has_water_tank = 1";
                } 
                elseif (in_array($word_lower, ['ac', 'aircon', 'air-con', 'conditioning', 'air'])) {
                    $conditions[] = "has_ac = 1";
                } 
                elseif (in_array($word_lower, ['solar', 'power'])) {
                    $conditions[] = "has_solar = 1";
                } 
                elseif (in_array($word_lower, ['gate', 'remote'])) {
                    $conditions[] = "has_remote_gate = 1";
                } 
                elseif (in_array($word_lower, ['cctv', 'camera', 'cameras', 'security'])) {
                    $conditions[] = "has_cctv = 1";
                } 
                elseif (in_array($word_lower, ['fence', 'wall'])) {
                    $conditions[] = "(has_wall_fence = 1 OR has_electric_fence = 1)";
                } 
                elseif (in_array($word_lower, ['electric'])) {
                    $conditions[] = "has_electric_fence = 1";
                } 
                elseif (in_array($word_lower, ['parking', 'carpark', 'garage'])) {
                    $conditions[] = "carpark > 0";
                } 
                elseif (in_array($word_lower, ['geyser', 'heater'])) {
                    $conditions[] = "geyser_type != 'none'";
                } 
                else {
                    // 4. TEXT SEARCH (e.g. "Parklands")
                    $conditions[] = "(city LIKE '%$word%' 
                                      OR address LIKE '%$word%' 
                                      OR description LIKE '%$word%')";
                }
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

// Pagination
$items_per_page = 8;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $items_per_page;

// Get total count for pagination
$count_sql = str_replace("SELECT *", "SELECT COUNT(*) as total", $sql);
$count_result = mysqli_query($conn, $count_sql);
$count_row = mysqli_fetch_assoc($count_result);
$total_items = $count_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Add LIMIT to main query
$sql .= " LIMIT $items_per_page OFFSET $offset";

$result = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Housing App - Find a Home</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .pagination-btn:hover,
        .pagination-number:hover {
            background: #f1f5f9 !important;
            border-color: #cbd5e1 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .pagination-number.active:hover {
            background: #2563eb !important;
            border-color: #2563eb !important;
        }
    </style>
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
    </nav>

    <!-- HERO SECTION -->
    <div class="hero-section">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <h1>Discover Your Next Home.</h1>
            <p>Simple. Transparent. Direct from Owners.</p>
            
            <!-- Search Bar -->
            <form id="search-form" class="search-bar-container">
                <input type="text" id="search-input" name="search" class="search-input" placeholder="Try 'Kitwe 2 bedrooms' or 'K5,000'..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit" class="search-btn" id="search-btn">Search</button>
            </form>
            
            <div class="hero-secondary-cta">
                <span style="opacity: 0.9;">Own a property?</span>
                <a href="add_listing.php" class="btn-glass">List it for Free &rarr;</a>
            </div>
        </div>
    </div>

    <!-- FEATURES STRIP (Marketplace Value) -->
    <div class="market-value-section">
        <div class="market-col renter-col">
            <div class="col-header">
                <span class="icon">🛋️</span>
                <h3>For Renters</h3>
            </div>
            <ul class="benefit-list">
                <li><strong>Direct Contact</strong> - No middleman fees.</li>
                <li><strong>Verified Photos</strong> - See exactly what you get.</li>
                <li><strong>Simple Search</strong> - Find your home in seconds.</li>
            </ul>
        </div>
        <div class="market-col owner-col">
            <div class="col-header">
                <span class="icon">📈</span>
                <h3>For Landlords</h3>
            </div>
            <ul class="benefit-list">
                <li><strong>100% Income</strong> - We charge zero commissions.</li>
                <li><strong>Total Control</strong> - Manage listings on your phone.</li>
                <li><strong>Quality Leads</strong> - Serious tenants only.</li>
            </ul>
        </div>
    </div>

    <!-- LISTINGS SECTION -->
    <div class="container-fluid" id="listings">
        <div class="section-header">
            <h2 id="section-title">Available Rentals</h2>
            <p class="text-muted" id="search-results-text" style="display: <?php echo $search_display ? 'block' : 'none'; ?>">
                Results for "<span id="search-term"><?php echo $search_display; ?></span>" (<a href="#" onclick="clearSearch(); return false;">View All</a>)
            </p>
        </div>
        
        <div class="listing-grid">
            <?php if (mysqli_num_rows($result) > 0): ?>
                <?php while($row = mysqli_fetch_assoc($result)): ?>
                    <?php
                        $prop_id = $row['id'];
                        $imgSql = "SELECT image_path FROM property_images WHERE property_id = '$prop_id' ORDER BY display_order ASC, id ASC LIMIT 1";
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
                                <p class="card-meta">
                                    <?php echo htmlspecialchars($row['bedrooms']); ?> Beds • 
                                    <?php echo htmlspecialchars($row['bathrooms']); ?> Baths<?php 
                                    echo ($row['has_pool'] == 1) ? ' • 🏊' : ''; 
                                    echo ($row['has_water_tank'] == 1) ? ' • 💧' : ''; 
                                    echo ($row['has_ac'] == 1) ? ' • ❄️' : ''; 
                                    echo ($row['has_solar'] == 1) ? ' • ☀️' : ''; 
                                    echo ($row['has_remote_gate'] == 1) ? ' • 🚪' : ''; 
                                    echo ($row['has_cctv'] == 1) ? ' • 📹' : ''; 
                                    echo ($row['has_wall_fence'] == 1) ? ' • 🧱' : ''; 
                                    echo ($row['has_electric_fence'] == 1) ? ' • ⚡' : ''; 
                                    ?>
                                </p>
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

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination" style="display: flex; justify-content: center; align-items: center; gap: 8px; margin-top: 40px; margin-bottom: 40px;">
                <?php if ($page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                       class="pagination-btn" 
                       style="padding: 10px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-weight: 500; transition: all 0.2s;">
                        ← Previous
                    </a>
                <?php endif; ?>

                <?php
                // Show page numbers
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => 1])); ?>" 
                       class="pagination-number" 
                       style="padding: 10px 14px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; min-width: 44px; text-align: center; transition: all 0.2s;">
                        1
                    </a>
                    <?php if ($start_page > 2): ?>
                        <span style="color: #94a3b8;">...</span>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>" 
                       class="pagination-number <?php echo ($i == $page) ? 'active' : ''; ?>" 
                       style="padding: 10px 14px; background: <?php echo ($i == $page) ? '#3b82f6' : 'white'; ?>; border: 1px solid <?php echo ($i == $page) ? '#3b82f6' : '#e2e8f0'; ?>; border-radius: 8px; text-decoration: none; color: <?php echo ($i == $page) ? 'white' : '#334155'; ?>; min-width: 44px; text-align: center; font-weight: <?php echo ($i == $page) ? '600' : '500'; ?>; transition: all 0.2s;">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span style="color: #94a3b8;">...</span>
                    <?php endif; ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $total_pages])); ?>" 
                       class="pagination-number" 
                       style="padding: 10px 14px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; min-width: 44px; text-align: center; transition: all 0.2s;">
                        <?php echo $total_pages; ?>
                    </a>
                <?php endif; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                       class="pagination-btn" 
                       style="padding: 10px 16px; background: white; border: 1px solid #e2e8f0; border-radius: 8px; text-decoration: none; color: #334155; font-weight: 500; transition: all 0.2s;">
                        Next →
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // AJAX Search Functionality
        const searchForm = document.getElementById('search-form');
        const searchInput = document.getElementById('search-input');
        const searchBtn = document.getElementById('search-btn');
        const listingGrid = document.querySelector('.listing-grid');
        const searchResultsText = document.getElementById('search-results-text');
        const searchTerm = document.getElementById('search-term');
        
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            performSearch();
        });
        
        function performSearch() {
            const query = searchInput.value.trim();
            
            // Update button state
            searchBtn.textContent = 'Searching...';
            searchBtn.disabled = true;
            
            // Show loading state
            listingGrid.style.opacity = '0.5';
            
            // Build URL
            const url = query ? `search_ajax.php?search=${encodeURIComponent(query)}` : 'search_ajax.php';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    // Update search results text
                    if (query) {
                        searchTerm.textContent = data.search_term;
                        searchResultsText.style.display = 'block';
                    } else {
                        searchResultsText.style.display = 'none';
                    }
                    
                    // Render results
                    renderResults(data.properties);
                    
                    // Reset button
                    searchBtn.textContent = 'Search';
                    searchBtn.disabled = false;
                    listingGrid.style.opacity = '1';
                    
                    // Scroll to results
                    document.getElementById('listings').scrollIntoView({ behavior: 'smooth', block: 'start' });
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchBtn.textContent = 'Search';
                    searchBtn.disabled = false;
                    listingGrid.style.opacity = '1';
                });
        }
        
        function renderResults(properties) {
            if (properties.length === 0) {
                listingGrid.innerHTML = `
                    <div class="no-results">
                        <h3>No listings found</h3>
                        <p>We couldn't find any properties matching your search criteria.</p>
                        <a href="#" onclick="clearSearch(); return false;" class="nav-btn" style="display:inline-block; margin-top:15px;">View All Rentals</a>
                    </div>
                `;
                return;
            }
            
            let html = '';
            properties.forEach(property => {
                html += `
                    <a href="property_details.php?id=${property.id}" class="card-link">
                        <div class="card">
                            <div class="card-img-wrapper">
                                <img src="${property.thumbnail}" alt="House" loading="lazy">
                            </div>
                            <div class="card-content">
                                <div class="card-header-row">
                                    <h3>${escapeHtml(property.city)}</h3>
                                    <span class="rating">★ New</span>
                                </div>
                                <p class="card-address">${escapeHtml(property.address)}</p>
                                <p class="card-meta">
                                    ${escapeHtml(property.bedrooms)} Beds • 
                                    ${escapeHtml(property.bathrooms)} Baths${property.has_pool == 1 ? ' • 🏊' : ''}${property.has_water_tank == 1 ? ' • 💧' : ''}${property.has_ac == 1 ? ' • ❄️' : ''}${property.has_solar == 1 ? ' • ☀️' : ''}${property.has_remote_gate == 1 ? ' • 🚪' : ''}${property.has_cctv == 1 ? ' • 📹' : ''}${property.has_wall_fence == 1 ? ' • 🧱' : ''}${property.has_electric_fence == 1 ? ' • ⚡' : ''}
                                </p>
                                <div class="card-price-row">
                                    <span class="price-value">K ${numberFormat(property.price)}</span>
                                    <span class="price-period">/mo</span>
                                </div>
                            </div>
                        </div>
                    </a>
                `;
            });
            
            listingGrid.innerHTML = html;
        }
        
        function clearSearch() {
            searchInput.value = '';
            performSearch();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function numberFormat(number) {
            return parseFloat(number).toLocaleString('en-US');
        }
    </script>

    <?php include 'footer.php'; ?>

</body>
</html>