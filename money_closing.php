<?php
include 'header.php';
include 'dp.php';

$allowed = isset($_SESSION['role']) && ($_SESSION['role'] === 'money_agent' || $_SESSION['role'] === 'admin');
if (!$allowed) {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    include 'footer.php';
    exit;
}

$providers = ['mpesa','mixx_by_yass','airtelmoney','halopesa','azam_pesa','bank_agency','kingamuzi','government','other'];
$display_providers = [
    'mpesa' => 'M-Pesa',
    'mixx_by_yass' => 'Mixx by Yass',
    'airtelmoney' => 'Airtel Money',
    'halopesa' => 'HaloPesa',
    'azam_pesa' => 'Azam Pesa',
    'bank_agency' => 'Bank Agency',
    'kingamuzi' => 'Kingamuzi (TV)',
    'government' => 'Govt Payments',
    'other' => 'Other'
];
$message = '';

$closing_date = (string)($_GET['date'] ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $closing_date)) {
    $closing_date = date('Y-m-d');
}

// Expected cash up to closing_date (inclusive)
$opening_cash = (float)($conn->query("SELECT opening_cash FROM money_cash_opening WHERE id = 1")->fetch_assoc()['opening_cash'] ?? 0);
$cash_delta_stmt = $conn->prepare("
    SELECT SUM(
        CASE 
            WHEN tx_type='cash_in' THEN (amount + commission)
            WHEN tx_type='cash_out' THEN (-amount + commission)
            ELSE 0
        END
    ) AS total
    FROM money_transactions
    WHERE DATE(tx_time) <= ?
");
$cash_delta_stmt->bind_param("s", $closing_date);
$cash_delta_stmt->execute();
$cash_delta = (float)($cash_delta_stmt->get_result()->fetch_assoc()['total'] ?? 0);
$cash_delta_stmt->close();
$expected_cash = $opening_cash + $cash_delta;

// Expected float per provider up to closing_date (inclusive)
$open_rs = $conn->query("SELECT provider, opening_float FROM money_float_opening");
$opening_float = [];
while($r = $open_rs->fetch_assoc()) $opening_float[$r['provider']] = (float)$r['opening_float'];

$delta_stmt = $conn->prepare("
    SELECT provider, SUM(CASE WHEN tx_type='cash_in' THEN -amount WHEN tx_type='cash_out' THEN amount ELSE 0 END) AS delta
    FROM money_transactions
    WHERE DATE(tx_time) <= ?
    GROUP BY provider
");
$delta_stmt->bind_param("s", $closing_date);
$delta_stmt->execute();
$delta_rs = $delta_stmt->get_result();
$float_delta = [];
while($r = $delta_rs->fetch_assoc()) $float_delta[$r['provider']] = (float)$r['delta'];
$delta_stmt->close();

$expected_float = [];
foreach ($providers as $p) $expected_float[$p] = ($opening_float[$p] ?? 0) + ($float_delta[$p] ?? 0);

// Save closing (one per date)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_closing'])) {
    $counted_cash = (float)($_POST['counted_cash'] ?? 0);
    $counted_float = [];
    foreach ($providers as $p) {
        $counted_float[$p] = (float)($_POST['counted_float_' . $p] ?? 0);
    }
    $notes = trim((string)($_POST['notes'] ?? ''));

    $var_cash = $counted_cash - $expected_cash;
    $var = [];
    foreach ($providers as $p) $var[$p] = $counted_float[$p] - $expected_float[$p];

    $stmt = $conn->prepare("
        INSERT INTO money_daily_closing (
            closing_date, user_id,
            expected_cash, counted_cash, variance_cash,
            expected_float_mpesa, counted_float_mpesa, variance_float_mpesa,
            expected_float_mixx_by_yass, counted_float_mixx_by_yass, variance_float_mixx_by_yass,
            expected_float_airtelmoney, counted_float_airtelmoney, variance_float_airtelmoney,
            expected_float_halopesa, counted_float_halopesa, variance_float_halopesa,
            expected_float_azam_pesa, counted_float_azam_pesa, variance_float_azam_pesa,
            expected_float_bank_agency, counted_float_bank_agency, variance_float_bank_agency,
            expected_float_kingamuzi, counted_float_kingamuzi, variance_float_kingamuzi,
            expected_float_government, counted_float_government, variance_float_government,
            expected_float_other, counted_float_other, variance_float_other,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            user_id = VALUES(user_id),
            expected_cash = VALUES(expected_cash),
            counted_cash = VALUES(counted_cash),
            variance_cash = VALUES(variance_cash),
            expected_float_mpesa = VALUES(expected_float_mpesa),
            counted_float_mpesa = VALUES(counted_float_mpesa),
            variance_float_mpesa = VALUES(variance_float_mpesa),
            expected_float_mixx_by_yass = VALUES(expected_float_mixx_by_yass),
            counted_float_mixx_by_yass = VALUES(counted_float_mixx_by_yass),
            variance_float_mixx_by_yass = VALUES(variance_float_mixx_by_yass),
            expected_float_airtelmoney = VALUES(expected_float_airtelmoney),
            counted_float_airtelmoney = VALUES(counted_float_airtelmoney),
            variance_float_airtelmoney = VALUES(variance_float_airtelmoney),
            expected_float_halopesa = VALUES(expected_float_halopesa),
            counted_float_halopesa = VALUES(counted_float_halopesa),
            variance_float_halopesa = VALUES(variance_float_halopesa),
            expected_float_azam_pesa = VALUES(expected_float_azam_pesa),
            counted_float_azam_pesa = VALUES(counted_float_azam_pesa),
            variance_float_azam_pesa = VALUES(variance_float_azam_pesa),
            expected_float_bank_agency = VALUES(expected_float_bank_agency),
            counted_float_bank_agency = VALUES(counted_float_bank_agency),
            variance_float_bank_agency = VALUES(variance_float_bank_agency),
            expected_float_kingamuzi = VALUES(expected_float_kingamuzi),
            counted_float_kingamuzi = VALUES(counted_float_kingamuzi),
            variance_float_kingamuzi = VALUES(variance_float_kingamuzi),
            expected_float_government = VALUES(expected_float_government),
            counted_float_government = VALUES(counted_float_government),
            variance_float_government = VALUES(variance_float_government),
            expected_float_other = VALUES(expected_float_other),
            counted_float_other = VALUES(counted_float_other),
            variance_float_other = VALUES(variance_float_other),
            notes = VALUES(notes)
    ");

    $uid = (int)$_SESSION['user_id'];
    $stmt->bind_param(
        "siddddddddddddddddddddddddddddds",
        $closing_date, $uid,
        $expected_cash, $counted_cash, $var_cash,
        $expected_float['mpesa'], $counted_float['mpesa'], $var['mpesa'],
        $expected_float['mixx_by_yass'], $counted_float['mixx_by_yass'], $var['mixx_by_yass'],
        $expected_float['airtelmoney'], $counted_float['airtelmoney'], $var['airtelmoney'],
        $expected_float['halopesa'], $counted_float['halopesa'], $var['halopesa'],
        $expected_float['azam_pesa'], $counted_float['azam_pesa'], $var['azam_pesa'],
        $expected_float['bank_agency'], $counted_float['bank_agency'], $var['bank_agency'],
        $expected_float['kingamuzi'], $counted_float['kingamuzi'], $var['kingamuzi'],
        $expected_float['government'], $counted_float['government'], $var['government'],
        $expected_float['other'], $counted_float['other'], $var['other'],
        $notes
    );
    $stmt->execute();
    $stmt->close();

    $action = "Saved daily closing for " . $closing_date . " (cash variance: " . number_format($var_cash, 0) . ")";
    $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $_SESSION['user_id'], $action);
    $log->execute();
    $log->close();

    $message = "<div class='alert alert-success'>Daily closing saved for " . htmlspecialchars($closing_date) . ".</div>";
}

// Load existing closing for the date (if any)
$existing = null;
$exStmt = $conn->prepare("SELECT * FROM money_daily_closing WHERE closing_date = ? LIMIT 1");
$exStmt->bind_param("s", $closing_date);
$exStmt->execute();
$existing = $exStmt->get_result()->fetch_assoc();
$exStmt->close();

$counted_cash_val = $existing['counted_cash'] ?? '';
$counted_float_val = [
    'mpesa' => $existing['counted_float_mpesa'] ?? '',
    'mixx_by_yass' => $existing['counted_float_mixx_by_yass'] ?? '',
    'airtelmoney' => $existing['counted_float_airtelmoney'] ?? '',
    'halopesa' => $existing['counted_float_halopesa'] ?? '',
    'azam_pesa' => $existing['counted_float_azam_pesa'] ?? '',
    'bank_agency' => $existing['counted_float_bank_agency'] ?? '',
    'kingamuzi' => $existing['counted_float_kingamuzi'] ?? '',
    'government' => $existing['counted_float_government'] ?? '',
    'other' => $existing['counted_float_other'] ?? '',
];
$notes_val = $existing['notes'] ?? '';
?>

<div class="header-actions">
    <div>
        <h1>Daily Closing (Reconciliation)</h1>
        <p class="text-muted">Count cash and float, compare to expected system balances.</p>
    </div>
    <div class="actions">
        <a href="money_dashboard.php" class="btn btn-secondary">&larr; Dashboard</a>
    </div>
</div>

<?php echo $message; ?>

<div class="card" style="margin-bottom: 16px;">
    <form method="get" action="money_closing.php" class="filters" autocomplete="off">
        <div class="form-row">
            <div class="form-group">
                <label>Closing Date</label>
                <input type="date" name="date" class="form-control" value="<?php echo htmlspecialchars($closing_date); ?>">
            </div>
            <div class="form-group" style="align-self:end;">
                <button class="btn btn-primary" type="submit">Load</button>
            </div>
        </div>
    </form>
</div>

<div class="grid">
    <div class="card">
        <h2><i class="fas fa-calculator"></i> Expected Balances (System)</h2>
        <div class="kv">
            <div class="row"><span>Expected Cash</span><strong>TSh <?php echo number_format($expected_cash, 0); ?></strong></div>
        </div>
        <div class="table-container" style="margin-top: 12px;">
            <table>
                <thead>
                    <tr><th>Provider</th><th>Expected Float</th></tr>
                </thead>
                <tbody>
                    <?php foreach($providers as $p): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($p); ?></strong></td>
                            <td>TSh <?php echo number_format((float)$expected_float[$p], 0); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h2><i class="fas fa-clipboard-check"></i> Counted Balances</h2>
        <form method="post" action="money_closing.php?<?php echo http_build_query(['date' => $closing_date]); ?>" autocomplete="off">
            <div class="form-group">
                <label>Counted Cash (TSh)</label>
                <input type="number" step="0.01" name="counted_cash" class="form-control" required value="<?php echo htmlspecialchars((string)$counted_cash_val); ?>">
            </div>
            <div class="form-row" style="grid-template-columns: 1fr 1fr; gap: 12px;">
                <?php foreach($providers as $p): ?>
                    <div class="form-group">
                        <label>Counted Float - <?php echo htmlspecialchars($p); ?> (TSh)</label>
                        <input type="number" step="0.01" name="counted_float_<?php echo htmlspecialchars($p); ?>" class="form-control" required value="<?php echo htmlspecialchars((string)($counted_float_val[$p] ?? '')); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" class="form-control" value="<?php echo htmlspecialchars((string)$notes_val); ?>" placeholder="Any issue, shortage, extra cash, etc.">
            </div>
            <button class="btn btn-primary" type="submit" name="save_closing">Save Daily Closing</button>
        </form>

        <?php if($existing): ?>
            <div class="alert alert-success" style="margin-top: 12px;">This date already has a saved closing. Saving again will update it.</div>
        <?php endif; ?>
    </div>
</div>

<?php if($existing): ?>
<div class="card" style="margin-top: 16px;">
    <h2><i class="fas fa-balance-scale"></i> Variance Report</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Expected</th>
                    <th>Counted</th>
                    <th>Variance</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Cash</strong></td>
                    <td>TSh <?php echo number_format((float)$existing['expected_cash'], 0); ?></td>
                    <td>TSh <?php echo number_format((float)$existing['counted_cash'], 0); ?></td>
                    <td class="<?php echo ((float)$existing['variance_cash'] === 0.0) ? 'ok' : 'bad'; ?>">
                        TSh <?php echo number_format((float)$existing['variance_cash'], 0); ?>
                    </td>
                </tr>
                <?php
                    $map = [
                        'mpesa' => ['expected_float_mpesa','counted_float_mpesa','variance_float_mpesa'],
                        'mixx_by_yass' => ['expected_float_mixx_by_yass','counted_float_mixx_by_yass','variance_float_mixx_by_yass'],
                        'airtelmoney' => ['expected_float_airtelmoney','counted_float_airtelmoney','variance_float_airtelmoney'],
                        'halopesa' => ['expected_float_halopesa','counted_float_halopesa','variance_float_halopesa'],
                        'azam_pesa' => ['expected_float_azam_pesa','counted_float_azam_pesa','variance_float_azam_pesa'],
                        'bank_agency' => ['expected_float_bank_agency','counted_float_bank_agency','variance_float_bank_agency'],
                        'kingamuzi' => ['expected_float_kingamuzi','counted_float_kingamuzi','variance_float_kingamuzi'],
                        'government' => ['expected_float_government','counted_float_government','variance_float_government'],
                        'other' => ['expected_float_other','counted_float_other','variance_float_other'],
                    ];
                    foreach($display_providers as $p => $label):
                        $k = $map[$p];
                ?>
                    <tr>
                        <td><strong>Float - <?php echo htmlspecialchars($label); ?></strong></td>
                        <td>TSh <?php echo number_format((float)$existing[$k[0]], 0); ?></td>
                        <td>TSh <?php echo number_format((float)$existing[$k[1]], 0); ?></td>
                        <td class="<?php echo ((float)$existing[$k[2]] === 0.0) ? 'ok' : 'bad'; ?>">
                            TSh <?php echo number_format((float)$existing[$k[2]], 0); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<style>
    .header-actions { display:flex; justify-content:space-between; align-items:flex-end; gap: 16px; margin-bottom: 24px; }
    .text-muted { color:#64748b; font-size:14px; margin-top: 6px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { margin:0 0 14px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap: 10px; }
    .filters .form-row { display:flex; gap: 12px; align-items:flex-end; }
    .form-row { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display:block; font-size: 12px; font-weight: 800; color:#334155; margin-bottom: 6px; }
    .kv .row { display:flex; justify-content:space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; }
    .ok { color: #166534; font-weight: 800; }
    .bad { color: #991b1b; font-weight: 800; }
    @media (max-width: 1100px) { .grid { grid-template-columns: 1fr; } }
</style>

<?php
$conn->close();
include 'footer.php';
?>

