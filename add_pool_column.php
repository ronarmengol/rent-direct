<?php
include 'db.php';

// Add new columns to properties table
$columns = [
    "ALTER TABLE properties ADD COLUMN has_pool TINYINT(1) DEFAULT 0 AFTER bedrooms",
    "ALTER TABLE properties ADD COLUMN has_water_tank TINYINT(1) DEFAULT 0 AFTER has_pool",
    "ALTER TABLE properties ADD COLUMN bathrooms INT DEFAULT 1 AFTER has_water_tank",
    "ALTER TABLE properties ADD COLUMN toilets INT DEFAULT 1 AFTER bathrooms"
];

$results = [];

foreach ($columns as $sql) {
    if (mysqli_query($conn, $sql)) {
        preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
        $results[] = "✓ Column '{$matches[1]}' added successfully!";
    } else {
        if (mysqli_errno($conn) == 1060) {
            preg_match('/ADD COLUMN (\w+)/', $sql, $matches);
            $results[] = "• Column '{$matches[1]}' already exists.";
        } else {
            $results[] = "✗ Error: " . mysqli_error($conn);
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Update</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; margin-bottom: 20px; }
        .result { padding: 10px; margin: 8px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; }
        .info { background: #d1ecf1; color: #0c5460; }
        .error { background: #f8d7da; color: #721c24; }
        .btn { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Database Update Results</h2>
        <?php foreach ($results as $result): ?>
            <?php 
                $class = 'info';
                if (strpos($result, '✓') !== false) $class = 'success';
                if (strpos($result, '✗') !== false) $class = 'error';
            ?>
            <div class="result <?php echo $class; ?>"><?php echo $result; ?></div>
        <?php endforeach; ?>
        <a href="index.php" class="btn">Go to Homepage</a>
    </div>
</body>
</html>
