<?php
include 'header.php';
include 'dp.php';

// Only admins and chairmen can access this report
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    echo "<div class='alert alert-danger'>Access Denied. Admins and Chairmen only.</div>";
    include 'footer.php';
    exit;
}

$start_date = (string)($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end_date = (string)($_GET['end_date'] ?? date('Y-m-d'));
$filter_bank = (string)($_GET['bank'] ?? 'all');

// Get banks for filter dropdown
$banks = $conn->query("SELECT * FROM banks WHERE is_active = TRUE ORDER BY bank_name")->fetch_all(MYSQLI_ASSOC);

// Build query
$where = ["DATE(mt.tx_time) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];
$types = "ss";

if ($filter_bank !== 'all') {
    $where[] = "mt.provider = ? AND mt.bank_name = ?";
    $types .= "ss";
    $params[] = $filter_bank;
}

$where_clause = implode(" AND ", $where);

$query = "SELECT mt.*, e.username 
           FROM money_transactions mt 
           JOIN employees e ON mt.user_id = e.id 
           WHERE $where_clause 
           ORDER BY mt.tx_time DESC";

$stmt = $conn->prepare($query);
call_user_func_array([$stmt, 'bind_param'], $types);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="header-actions">
    <h2><i class="fas fa-university"></i> Bank Agency Transactions Report</h2>
    <a href="money_dashboard.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

<div class="filters">
    <form method="GET" class="form-row">
        <div class="form-group">
            <label>Start Date</label>
            <input type="date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>End Date</label>
            <input type="date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" class="form-control">
        </div>
        <div class="form-group">
            <label>Bank</label>
            <select name="bank" class="form-control">
                <option value="all" <?php if($filter_bank === 'all') echo 'selected'; ?>>All Banks</option>
                <?php foreach($banks as $bank): ?>
                    <option value="<?php echo htmlspecialchars($bank['bank_name']); ?>" <?php if($filter_bank === $bank['bank_name']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($bank['bank_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-primary">Filter</button>
        </div>
    </form>
</div>

<div class="grid">
    <div class="card">
        <div class="card-head">
            <h2><i class="fas fa-chart-line"></i> Bank Agency Transactions</h2>
        </div>
        <div class="card-body">
            <?php if ($result->num_rows > 0): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>User</th>
                            <th>Bank</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Commission</th>
                            <th>Customer</th>
                            <th>Reference</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_amount = 0;
                        $total_commission = 0;
                        while($row = $result->fetch_assoc()): 
                            $total_amount += (float)$row['amount'];
                            $total_commission += (float)$row['commission'];
                        ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($row['tx_time'])); ?></td>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo htmlspecialchars($row['bank_name']); ?></td>
                            <td>
                                <span class="pill <?php echo $row['tx_type'] === 'cash_in' ? 'pill-in' : 'pill-out'; ?>">
                                    <?php echo $row['tx_type'] === 'cash_in' ? 'Cash In' : 'Cash Out'; ?>
                                </span>
                            </td>
                            <td><strong>TSh <?php echo number_format((float)$row['amount'], 0); ?></strong></td>
                            <td class="text-muted">TSh <?php echo number_format((float)$row['commission'], 0); ?></td>
                            <td><?php echo htmlspecialchars($row['customer_msisdn'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['reference'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($row['notes'] ?? '-'); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="total-row">
                            <td colspan="4"><strong>TOTALS</strong></td>
                            <td><strong>TSh <?php echo number_format($total_amount, 0); ?></strong></td>
                            <td><strong>TSh <?php echo number_format($total_commission, 0); ?></strong></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            <?php else: ?>
                <p class="text-muted">No bank agency transactions found for the selected period.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .header-actions { display:flex; justify-content:space-between; align-items:flex-end; gap: 16px; margin-bottom: 24px; }
    .text-muted { color:#64748b; font-size:14px; margin-top: 6px; }
    .grid { display:grid; grid-template-columns: 1fr; gap: 24px; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { margin:0 0 14px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap: 10px; }
    .card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; }
    .filters { margin-top: 10px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .form-row { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display:block; font-size: 12px; font-weight: 800; color:#334155; margin-bottom: 6px; }
    .table { width:100%; border-collapse:collapse; margin-top: 16px; }
    .table th, .table td { padding: 12px; text-align:left; border-bottom:1px solid #e9ecef; }
    .table th { background:#f8f9fa; font-weight:600; color:#495057; }
    .total-row { background:#f1f3f4; font-weight:bold; }
    .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
    .pill-in { background:#dcfce7; color:#166534; }
    .pill-out { background:#fee2e2; color:#991b1b; }
    @media (max-width: 1100px) { .grid { grid-template-columns: 1fr; } }
</style>

<?php
$conn->close();
include 'footer.php';
?>
