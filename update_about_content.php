<?php
include 'dp.php';

// Update About Us description to include "controlled by admin"
$new_description = "Maison Tech is your premier destination for high-quality technology solutions. Our entire system, from inventory to client orders, is professionally controlled by our Administrative team to ensure the highest standards of service and security.";

$stmt = $conn->prepare("UPDATE about_us SET description = ? WHERE id = 1");
$stmt->bind_param("s", $new_description);
$stmt->execute();
$stmt->close();

// Clear existing profiles and add the new ones
$conn->query("DELETE FROM official_profiles");

$officials = [
    ['Sylvester Ndallah', 'Chairman', 'Tech visionary leading the strategic growth of Maison Tech.'],
    ['James Ndallah', 'CEO', 'Driving innovation and operational excellence in technology sourcing.']
];

$stmt = $conn->prepare("INSERT INTO official_profiles (name, position, bio) VALUES (?, ?, ?)");
foreach ($officials as $official) {
    $stmt->bind_param("sss", $official[0], $official[1], $official[2]);
    $stmt->execute();
}
$stmt->close();

echo "About Us content and official profiles updated successfully.";
?>