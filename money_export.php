<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'dp.php';

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    http_response_code(401);
    echo "Not logged in.";
    exit;
}
if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'money_agent' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman')) {
    http_response_code(403);
    echo "Access denied.";
    exit;
}

function mt_bind_params($stmt, string $types, array $params): void {
    $refs = [];
    $refs[] = &$types;
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

$start_date = (string)($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end_date = (string)($_GET['end_date'] ?? date('Y-m-d'));
$filter_type = strtolower(trim((string)($_GET['type'] ?? 'all')));
$filter_provider = strtolower(trim((string)($_GET['provider'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));

$allowed_type = ['all', 'cash_in', 'cash_out'];
$allowed_provider = ['all', 'mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'other'];
if (!in_array($filter_type, $allowed_type, true)) $filter_type = 'all';
if (!in_array($filter_provider, $allowed_provider, true)) $filter_provider = 'all';

$where = ["DATE(mt.tx_time) BETWEEN ? AND ?"];
$types = "ss";
$params = [$start_date, $end_date];

if ($filter_type !== 'all') {
    $where[] = "mt.tx_type = ?";
    $types .= "s";
    $params[] = $filter_type;
}
if ($filter_provider !== 'all') {
    $where[] = "mt.provider = ?";
    $types .= "s";
    $params[] = $filter_provider;
}
if ($q !== '') {
    $where[] = "(mt.customer_msisdn LIKE ? OR mt.reference LIKE ? OR mt.notes LIKE ? OR e.username LIKE ?)";
    $types .= "ssss";
    $like = "%" . $q . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "
    SELECT mt.id, mt.tx_time, e.username, mt.tx_type, mt.provider, mt.amount, mt.fee, mt.commission, mt.customer_msisdn, mt.reference, mt.notes
    FROM money_transactions mt
    JOIN employees e ON e.id = mt.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY mt.tx_time DESC
    LIMIT 5000
";
$stmt = $conn->prepare($sql);
mt_bind_params($stmt, $types, $params);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$filename = "money_transactions_" . $start_date . "_to_" . $end_date . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fputcsv($out, ['id','tx_time','username','tx_type','provider','amount','fee','commission','customer_msisdn','reference','notes']);
while ($row = $res->fetch_assoc()) {
    fputcsv($out, [
        $row['id'],
        $row['tx_time'],
        $row['username'],
        $row['tx_type'],
        $row['provider'],
        $row['amount'],
        $row['fee'],
        $row['commission'],
        $row['customer_msisdn'],
        $row['reference'],
        $row['notes'],
    ]);
}
fclose($out);
exit;

