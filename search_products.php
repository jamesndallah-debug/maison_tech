<?php
include 'dp.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';
$cat_id = (int)($_GET['cat_id'] ?? 0);
$include_out_of_stock = isset($_GET['include_out_of_stock']) && $_GET['include_out_of_stock'] == '1';

$stock_condition = $include_out_of_stock ? "" : "AND quantity > 0";

if ($cat_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE category_id = ? $stock_condition");
    $stmt->bind_param("i", $cat_id);
} elseif (strlen($query) >= 2) {
    $stmt = $conn->prepare("SELECT id, name, price, quantity FROM products WHERE name LIKE ? $stock_condition");
    $search_query = "%" . $query . "%";
    $stmt->bind_param("s", $search_query);
} else {
    echo json_encode([]);
    exit;
}
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode($products);
?>