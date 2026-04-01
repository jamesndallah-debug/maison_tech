<?php
include 'dp.php';

// Clear existing profiles and add the updated ones with phone and email in the bio
$conn->query("DELETE FROM official_profiles");

$officials = [
    [
        'Sylvester Ndallah', 
        'Chairman', 
        'Tech visionary leading the strategic growth of Maison Tech. Phone: +255767207115, Email: sylvesterpius17@gmail.com'
    ],
    [
        'James Ndallah', 
        'CEO', 
        'Driving innovation and operational excellence in technology sourcing. Phone: +255710726602, Email: jamesndallah@gmail.com'
    ]
];

$stmt = $conn->prepare("INSERT INTO official_profiles (name, position, bio) VALUES (?, ?, ?)");
foreach ($officials as $official) {
    $stmt->bind_param("sss", $official[0], $official[1], $official[2]);
    $stmt->execute();
}
$stmt->close();

echo "About Us official profiles updated with specific contact details successfully.";
?>