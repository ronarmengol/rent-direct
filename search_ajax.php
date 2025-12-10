<?php
include 'db.php';

header('Content-Type: application/json');

$search_raw = isset($_GET['search']) ? $_GET['search'] : '';
$search_display = htmlspecialchars($search_raw);

if (empty($search_raw)) {
    $sql = "SELECT * FROM properties ORDER BY created_at DESC";
} else {
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
}

$result = mysqli_query($conn, $sql);
$properties = array();

while ($row = mysqli_fetch_assoc($result)) {
    // Get thumbnail image
    $prop_id = $row['id'];
    $imgSql = "SELECT image_path FROM property_images WHERE property_id = '$prop_id' ORDER BY display_order ASC, id ASC LIMIT 1";
    $imgResult = mysqli_query($conn, $imgSql);
    $imgData = mysqli_fetch_assoc($imgResult);
    $thumbnail = $imgData ? "uploads/" . $imgData['image_path'] : "https://via.placeholder.com/300";
    
    $row['thumbnail'] = $thumbnail;
    $properties[] = $row;
}

echo json_encode([
    'success' => true,
    'properties' => $properties,
    'count' => count($properties),
    'search_term' => $search_display
]);
?>
