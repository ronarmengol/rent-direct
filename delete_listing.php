<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = mysqli_real_escape_string($conn, $_POST['id']);
    $input_pass = $_POST['password_check'];

    // 1. Get stored hash
    $sql = "SELECT password FROM properties WHERE id = '$id'";
    $result = mysqli_query($conn, $sql);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        // 2. Verify Password (DEV MODE: Direct comparison)
        if ($input_pass === $row['password']) {
            
            // Correct Password: Delete images
            $sqlImg = "SELECT image_path FROM property_images WHERE property_id = '$id'";
            $resultImg = mysqli_query($conn, $sqlImg);

            while($imgRow = mysqli_fetch_assoc($resultImg)) {
                $file = "uploads/" . $imgRow['image_path'];
                if (file_exists($file)) unlink($file);
            }

            // Delete Property
            $deleteSql = "DELETE FROM properties WHERE id = '$id'";
            if (mysqli_query($conn, $deleteSql)) {
                // SUCCESS: Set session message and go to Index
                $_SESSION['msg'] = "Listing removed successfully.";
                $_SESSION['msg_type'] = "success";
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['msg'] = "Database error.";
                $_SESSION['msg_type'] = "error";
                header("Location: property_details.php?id=$id");
                exit();
            }

        } else {
            // WRONG PASSWORD: Set session message and go back to Details
            $_SESSION['msg'] = "Incorrect Password. Unable to delete.";
            $_SESSION['msg_type'] = "error";
            header("Location: property_details.php?id=$id");
            exit();
        }
    } else {
        $_SESSION['msg'] = "Listing not found.";
        $_SESSION['msg_type'] = "error";
        header("Location: index.php");
        exit();
    }
} else {
    header("Location: index.php");
}
?>