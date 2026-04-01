<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'dp.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || !in_array($_SESSION['role'], ['admin', 'chairman'])) {
    http_response_code(403);
    echo "Access Denied.";
    exit;
}

$query = "
    SELECT 
        sp.id as payment_id,
        e.username as employee_name,
        e.role as employee_role,
        sp.amount,
        sp.payment_date,
        sp.payment_method,
        sp.notes,
        p.username as processed_by,
        sp.created_at as processed_at
    FROM salary_payments sp
    JOIN employees e ON sp.employee_id = e.id
    JOIN employees p ON sp.processed_by = p.id
    ORDER BY sp.payment_date DESC, sp.created_at DESC
";

$result = $conn->query($query);

$filename = "salary_payments_" . date('Y-m-d') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

fputcsv($output, [
    'Payment ID', 'Employee Name', 'Employee Role', 'Amount', 'Payment Date', 
    'Payment Method', 'Notes', 'Processed By', 'Processed At'
]);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
}

fclose($output);
$conn->close();
exit;
