<?php
include 'dp.php';
$addr = 'Rwezaula Singida';
$phone = '+255710726602, +2557672027115';
$email = 'jamesndallah@gmail.com';
$stmt = $conn->prepare('UPDATE about_us SET address = ?, contact_phone = ?, contact_email = ? WHERE id = 1');
$stmt->bind_param('sss', $addr, $phone, $email);
if ($stmt->execute()) {
    echo "Contact info updated in DB successfully\n";
} else {
    echo "Error updating contact info: " . $conn->error . "\n";
}
$stmt->close();
?>