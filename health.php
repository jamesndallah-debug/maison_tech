<?php
header('Content-Type: application/json; charset=utf-8');

$result = [
    'ok' => false,
    'app' => 'maison_tech',
    'time' => gmdate('c'),
    'php_version' => PHP_VERSION,
    'db_connected' => false,
    'database' => null,
    'tables' => [],
    'errors' => [],
];

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli('localhost', 'root', '', 'maison_tech');
    $result['db_connected'] = true;

    $dbRes = $conn->query("SELECT DATABASE() AS db");
    $result['database'] = $dbRes->fetch_assoc()['db'] ?? null;

    $requiredTables = [
        'employees',
        'products',
        'sales',
        'sale_items',
        'activity_logs',
        'money_transactions',
        'money_cash_opening',
        'money_float_opening'
    ];

    foreach ($requiredTables as $table) {
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        $result['tables'][$table] = $exists ? 'ok' : 'missing';
    }

    $missing = array_filter($result['tables'], function ($v) { return $v !== 'ok'; });
    $result['ok'] = $result['db_connected'] && empty($missing);
    $conn->close();
} catch (Throwable $e) {
    $result['errors'][] = $e->getMessage();
}

http_response_code($result['ok'] ? 200 : 500);
echo json_encode($result, JSON_PRETTY_PRINT);

