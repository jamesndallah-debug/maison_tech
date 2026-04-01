<?php
include 'header.php';
include 'dp.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'money_agent' && $_SESSION['role'] !== 'admin')) {
    echo "<div class='alert alert-danger'>Access Denied. Money Agents and Admins only.</div>";
    include 'footer.php';
    exit;
}

$message = '';

function mt_bind_params($stmt, string $types, array $params): void {
    $refs = [];
    $refs[] = &$types;
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_money_tx'])) {
    $tx_type = strtolower(trim((string)($_POST['tx_type'] ?? '')));
    $provider = strtolower(trim((string)($_POST['provider'] ?? 'other')));
    $amount = (float)($_POST['amount'] ?? 0);
    $fee = 0; // Fee removed - only commission tracked
    $commission = (float)($_POST['commission'] ?? 0);
    $msisdn = trim((string)($_POST['customer_msisdn'] ?? ''));
    $reference = trim((string)($_POST['reference'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));
    $bank_name = trim((string)($_POST['bank_name'] ?? ''));

    $allowed_type = ['cash_in', 'cash_out'];
    $allowed_provider = ['mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'azam_pesa', 'bank_agency', 'other'];

    if (!in_array($tx_type, $allowed_type, true)) {
        $message = "<div class='alert alert-danger'>Invalid transaction type.</div>";
    } elseif (!in_array($provider, $allowed_provider, true)) {
        $provider = 'other';
    } elseif ($amount <= 0) {
        $message = "<div class='alert alert-danger'>Amount must be greater than 0.</div>";
    } elseif ($commission < 0) {
        $message = "<div class='alert alert-danger'>Commission cannot be negative.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO money_transactions (user_id, tx_type, provider, amount, commission, customer_msisdn, bank_name, reference, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issddssss", $_SESSION['user_id'], $tx_type, $provider, $amount, $commission, $msisdn, $bank_name, $reference, $notes);
        $stmt->execute();
        $stmt->close();

        $action = "Recorded mobile money transaction ($tx_type) - TSh " . number_format($amount, 0) . " (" . $provider . "), commission " . number_format($commission, 0);
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $_SESSION['user_id'], $action);
        $log->execute();
        $log->close();

        $message = "<div class='alert alert-success'>Transaction saved.</div>";
    }
}

// Filters (GET)
$start_date = (string)($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end_date = (string)($_GET['end_date'] ?? date('Y-m-d'));
$filter_type = strtolower(trim((string)($_GET['type'] ?? 'all')));
$filter_provider = strtolower(trim((string)($_GET['provider'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));

$allowed_type = ['all', 'cash_in', 'cash_out'];
$allowed_provider = ['all', 'mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'azam_pesa', 'bank_agency', 'kingamuzi', 'government', 'other'];

$display_providers = [
    'mpesa' => 'M-Pesa',
    'mixx_by_yass' => 'Mixx by Yass',
    'airtelmoney' => 'Airtel Money',
    'halopesa' => 'HaloPesa',
    'azam_pesa' => 'Azam Pesa',
    'bank_agency' => 'Bank Agency',
    'other' => 'Other'
];

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
    SELECT mt.*, e.username
    FROM money_transactions mt
    JOIN employees e ON e.id = mt.user_id
    WHERE " . implode(" AND ", $where) . "
    ORDER BY mt.tx_time DESC
    LIMIT 500
";

$stmt = $conn->prepare($sql);
mt_bind_params($stmt, $types, $params);
$stmt->execute();
$tx = $stmt->get_result();
$stmt->close();
?>

<div class="header-actions">
    <div>
        <h1>Mobile Money Transactions</h1>
        <p class="text-muted">Record and review cash-in / cash-out operations.</p>
    </div>
    <div class="actions">
        <a href="money_dashboard.php" class="btn btn-secondary">&larr; Dashboard</a>
    </div>
</div>

<?php echo $message; ?>

<div class="card" style="margin-bottom: 16px;">
    <div class="card-head">
        <h2><i class="fas fa-filter"></i> Filters</h2>
        <div class="actions">
            <a class="btn btn-secondary" href="money_transactions.php">Reset</a>
            <a class="btn btn-primary" href="money_export.php?<?php echo http_build_query($_GET); ?>"><i class="fas fa-file-csv"></i> Export CSV</a>
        </div>
    </div>
    <form method="get" action="money_transactions.php" class="filters" autocomplete="off">
        <div class="form-row">
            <div class="form-group">
                <label>From</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date); ?>">
            </div>
            <div class="form-group">
                <label>To</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date); ?>">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type" class="form-control">
                    <option value="all" <?php if($filter_type==='all') echo 'selected'; ?>>All</option>
                    <option value="cash_in" <?php if($filter_type==='cash_in') echo 'selected'; ?>>Cash In</option>
                    <option value="cash_out" <?php if($filter_type==='cash_out') echo 'selected'; ?>>Cash Out</option>
                </select>
            </div>
            <div class="form-group">
                <label>Provider</label>
                <select name="provider" class="form-control">
                    <option value="all" <?php if($filter_provider==='all') echo 'selected'; ?>>All Providers</option>
                    <?php foreach($display_providers as $p => $label): ?>
                        <option value="<?php echo $p; ?>" <?php if($filter_provider===$p) echo 'selected'; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="grid-column: 1 / -1;">
                <label>Search</label>
                <input type="text" name="q" class="form-control" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search by customer phone, reference, notes, or staff username...">
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Apply Filters</button>
    </form>
</div>

<div class="grid">
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> New Transaction</h2>
        <form action="money_transactions.php" method="post" autocomplete="off">
            <div class="form-row">
                <div class="form-group">
                    <label>Type</label>
                    <select name="tx_type" class="form-control" required>
                        <option value="cash_in">Cash In</option>
                        <option value="cash_out">Cash Out</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Provider</label>
                    <select name="provider" class="form-control" id="providerSelect" required>
                        <?php foreach($display_providers as $p => $label): ?>
                            <option value="<?php echo $p; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="bankGroup" style="display: none;">
                    <label>Bank</label>
                    <select name="bank_name" class="form-control" id="bankSelect">
                        <option value="">Select Bank</option>
                        <option value="CRDB">CRDB</option>
                        <option value="NMB">NMB</option>
                        <option value="NBC">NBC</option>
                        <option value="Selcom">Selcom</option>
                        <option value="TCB">TCB</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Amount (TSh)</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Commission Earned (TSh)</label>
                    <input type="number" step="0.01" name="commission" class="form-control" value="0">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Customer Phone (optional)</label>
                    <input type="text" name="customer_msisdn" class="form-control" placeholder="e.g. 2557xxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Reference (optional)</label>
                    <input type="text" name="reference" class="form-control" placeholder="Receipt/Txn ref">
                </div>
            </div>

            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="Any details...">
            </div>

            <button type="submit" name="add_money_tx" class="btn btn-primary">Save Transaction</button>
        </form>
    </div>

    <div class="card">
        <div class="card-head">
            <h2><i class="fas fa-list"></i> Recent</h2>
            <span class="text-muted">Last 200</span>
        </div>
        <div class="table-container" style="max-height: 520px; overflow:auto;">
            <table>
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Provider</th>
                        <th>Amount</th>
                        <th>Commission</th>
                        <th>Customer</th>
                        <th>Ref</th>
                        <th>By</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $tx->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="pill <?php echo $row['tx_type'] === 'cash_in' ? 'pill-in' : 'pill-out'; ?>">
                                    <?php echo $row['tx_type'] === 'cash_in' ? 'Cash In' : 'Cash Out'; ?>
                                </span>
                            </td>
                            <td>
                                <?php 
                                    $p_code = $row['provider'];
                                    if ($p_code === 'bank_agency') {
                                        echo htmlspecialchars($row['bank_name'] ?? 'Bank Agency');
                                    } else {
                                        echo htmlspecialchars($display_providers[$p_code] ?? $p_code);
                                    }
                                ?>
                            </td>
                            <td><strong>TSh <?php echo number_format((float)$row['amount'], 0); ?></strong></td>
                            <td class="text-muted">TSh <?php echo number_format((float)($row['commission'] ?? 0), 0); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($row['customer_msisdn'] ?? '-'); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($row['reference'] ?? '-'); ?></td>
                            <td class="text-muted"><?php echo htmlspecialchars($row['username']); ?></td>
                            <td class="text-muted"><?php echo date('M d, H:i', strtotime($row['tx_time'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
    .header-actions { display:flex; justify-content:space-between; align-items:flex-end; gap: 16px; margin-bottom: 24px; }
    .text-muted { color:#64748b; font-size:14px; margin-top: 6px; }
    .grid { display:grid; grid-template-columns: 1fr 1.2fr; gap: 24px; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { margin:0 0 14px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap: 10px; }
    .card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; }
    .filters { margin-top: 10px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .form-row { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display:block; font-size: 12px; font-weight: 800; color:#334155; margin-bottom: 6px; }
    .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
    .pill-in { background:#dcfce7; color:#166534; }
    .pill-out { background:#fee2e2; color:#991b1b; }
    @media (max-width: 1100px) { .grid { grid-template-columns: 1fr; } }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const providerSelect = document.getElementById('providerSelect');
    const bankGroup = document.getElementById('bankGroup');
    const bankSelect = document.getElementById('bankSelect');
    
    if (providerSelect && bankGroup && bankSelect) {
        providerSelect.addEventListener('change', function() {
            if (this.value === 'bank_agency') {
                bankGroup.style.display = 'block';
                bankSelect.setAttribute('required', 'required');
            } else {
                bankGroup.style.display = 'none';
                bankSelect.removeAttribute('required');
                bankSelect.value = '';
            }
        });
    }
});
</script>

<?php
$conn->close();
include 'footer.php';
?>

