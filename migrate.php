<?php
// Database migration - adds all property amenity columns
include 'db.php';

// Disable exception mode for this script
mysqli_report(MYSQLI_REPORT_OFF);

echo "<h2>Adding Database Columns...</h2>";
echo "<pre>";

$columns = [
    // Existing columns
    ['name' => 'has_pool', 'sql' => "ALTER TABLE properties ADD COLUMN has_pool TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_water_tank', 'sql' => "ALTER TABLE properties ADD COLUMN has_water_tank TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_ac', 'sql' => "ALTER TABLE properties ADD COLUMN has_ac TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_solar', 'sql' => "ALTER TABLE properties ADD COLUMN has_solar TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'bathrooms', 'sql' => "ALTER TABLE properties ADD COLUMN bathrooms INT DEFAULT 1 AFTER bedrooms"],
    ['name' => 'toilets', 'sql' => "ALTER TABLE properties ADD COLUMN toilets INT DEFAULT 1 AFTER bedrooms"],
    
    // New columns
    ['name' => 'carpark', 'sql' => "ALTER TABLE properties ADD COLUMN carpark INT DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_remote_gate', 'sql' => "ALTER TABLE properties ADD COLUMN has_remote_gate TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_cctv', 'sql' => "ALTER TABLE properties ADD COLUMN has_cctv TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_wall_fence', 'sql' => "ALTER TABLE properties ADD COLUMN has_wall_fence TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'has_electric_fence', 'sql' => "ALTER TABLE properties ADD COLUMN has_electric_fence TINYINT(1) DEFAULT 0 AFTER bedrooms"],
    ['name' => 'geyser_type', 'sql' => "ALTER TABLE properties ADD COLUMN geyser_type VARCHAR(20) DEFAULT 'none' AFTER bedrooms"],
    ['name' => 'available_from', 'sql' => "ALTER TABLE properties ADD COLUMN available_from DATE NULL AFTER bedrooms"]
];

foreach ($columns as $column) {
    echo "Adding column: {$column['name']}... ";
    
    $result = @mysqli_query($conn, $column['sql']);
    
    if ($result) {
        echo "SUCCESS ✓\n";
    } else {
        $error = mysqli_error($conn);
        if (strpos($error, 'Duplicate column') !== false || strpos($error, 'duplicate') !== false) {
            echo "ALREADY EXISTS ✓\n";
        } else {
            echo "ERROR: $error\n";
        }
    }
}

echo "\n\nDone! All columns added successfully.";
echo "\nYou can now close this page and refresh your site.";
echo "</pre>";

mysqli_close($conn);
?>
