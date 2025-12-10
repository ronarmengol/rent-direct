<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $property_id = (int)$_POST['property_id'];
    $current_password = $_POST['current_password'];
    $new_username = mysqli_real_escape_string($conn, trim($_POST['new_username']));
    $new_password = $_POST['new_password'];
    
    // Verify current password
    $sql = "SELECT password FROM properties WHERE id = $property_id";
    $result = mysqli_query($conn, $sql);
    $property = mysqli_fetch_assoc($result);
    
    if ($property['password'] !== $current_password) {
        header("Location: admin.php?id=$property_id&error=incorrect_password");
        exit();
    }
    
    // Update username and/or password
    $updates = [];
    $params = [];
    
    if (!empty($new_username)) {
        $updates[] = "owner_name = ?";
        $params[] = $new_username;
    }
    
    if (!empty($new_password)) {
        $updates[] = "password = ?";
        $params[] = $new_password;
    }
    
    if (count($updates) > 0) {
        $sql = "UPDATE properties SET " . implode(", ", $updates) . " WHERE id = ?";
        $stmt = mysqli_stmt_init($conn);
        
        if (mysqli_stmt_prepare($stmt, $sql)) {
            $params[] = $property_id;
            $types = str_repeat('s', count($params) - 1) . 'i';
            mysqli_stmt_bind_param($stmt, $types, ...$params);
            mysqli_stmt_execute($stmt);
            
            header("Location: admin.php?id=$property_id&success=credentials_updated");
            exit();
        }
    }
    
    header("Location: admin.php?id=$property_id");
    exit();
}
?>
