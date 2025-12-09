<?php
include 'db.php';

// Fetch all properties with owner credentials
$sql = "SELECT id, owner_name, password, contact_person, contact_number, city FROM properties ORDER BY id";
$result = mysqli_query($conn, $sql);

echo "<!DOCTYPE html>\n";
echo "<html><head><title>User List</title></head><body>\n";
echo "<h2>Property Owners & Credentials</h2>\n";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>\n";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>ID</th><th>Username (Owner Name)</th><th>Password</th><th>Contact Person</th><th>Phone</th><th>City</th>";
echo "</tr>\n";

if (mysqli_num_rows($result) > 0) {
    while($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . htmlspecialchars($row['owner_name']) . "</strong></td>";
        echo "<td><code>" . htmlspecialchars($row['password']) . "</code></td>";
        echo "<td>" . htmlspecialchars($row['contact_person']) . "</td>";
        echo "<td>" . htmlspecialchars($row['contact_number']) . "</td>";
        echo "<td>" . htmlspecialchars($row['city']) . "</td>";
        echo "</tr>\n";
    }
} else {
    echo "<tr><td colspan='6' style='text-align:center;'>No users found</td></tr>\n";
}

echo "</table>\n";
echo "<p><strong>Total Users:</strong> " . mysqli_num_rows($result) . "</p>\n";
echo "</body></html>";
?>
