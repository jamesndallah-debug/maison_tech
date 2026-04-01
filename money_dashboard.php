<?php
include 'header.php';
include 'dp.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_chairman = isset($_SESSION['role']) && $_SESSION['role'] === 'chairman';
$is_money_agent = isset($_SESSION['role']) && $_SESSION['role'] === 'money_agent';

// Admin and Chairman always have access
// Money agents have access by default (they need their dashboard)
if (!($is_admin || $is_chairman || $is_money_agent)) {
    echo "<div class='alert alert-danger'>Access Denied. Money Agents, Admins and Chairmen only.</div>";
    include 'footer.php';
    exit;
}

// Today stats
$today_in = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE tx_type='cash_in' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
$today_out = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE tx_type='cash_out' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
$today_commission = $conn->query("SELECT SUM(commission) AS total FROM money_transactions WHERE DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
$today_count = $conn->query("SELECT COUNT(*) AS cnt FROM money_transactions WHERE DATE(tx_time)=CURDATE()")->fetch_assoc()['cnt'] ?? 0;

// Today's expenses for money agent
$today_expenses = $conn->query("
    SELECT COUNT(*) as count, SUM(amount) as total 
    FROM expenses 
    WHERE DATE(expense_date) = CURDATE() AND recorded_by = " . $_SESSION['user_id'] . "
")->fetch_assoc();

$opening_cash = $conn->query("SELECT opening_cash FROM money_cash_opening WHERE id = 1")->fetch_assoc()['opening_cash'] ?? 0;
$cash_delta = $conn->query("
    SELECT SUM(
        CASE 
            WHEN tx_type='cash_in' THEN amount
            WHEN tx_type='cash_out' THEN -amount
            ELSE 0
        END
    ) AS total
    FROM money_transactions
")->fetch_assoc()['total'] ?? 0;
$cash_balance = (float)$opening_cash + (float)$cash_delta;

$float_opening_rs = $conn->query("SELECT provider, opening_float FROM money_float_opening");
$float_opening = [];
while($r = $float_opening_rs->fetch_assoc()) { $float_opening[$r['provider']] = (float)$r['opening_float']; }

$float_delta_rs = $conn->query("
    SELECT provider,
           SUM(CASE WHEN tx_type='cash_in' THEN -amount WHEN tx_type='cash_out' THEN amount ELSE 0 END) AS delta
    FROM money_transactions
    GROUP BY provider
");
$float_delta = [];
while($r = $float_delta_rs->fetch_assoc()) { $float_delta[$r['provider']] = (float)$r['delta']; }

$float_balance = [];
$total_float_balance = 0;

$display_providers = [
    'mpesa' => 'M-Pesa',
    'mixx_by_yass' => 'Mixx by Yass',
    'airtelmoney' => 'Airtel Money',
    'halopesa' => 'HaloPesa',
    'azam_pesa' => 'Azam Pesa',
    'bank_agency' => 'Bank Agency',
    'other' => 'Other'
];

foreach ($display_providers as $p => $label) {
    $bal = ($float_opening[$p] ?? 0) + ($float_delta[$p] ?? 0);
    $float_balance[$p] = $bal;
    $total_float_balance += $bal;
}

$provider_summary = $conn->query("
    SELECT provider,
           SUM(CASE WHEN tx_type='cash_in' THEN amount ELSE 0 END) AS cash_in_total,
           SUM(CASE WHEN tx_type='cash_out' THEN amount ELSE 0 END) AS cash_out_total,
           SUM(commission) AS commission_total
    FROM money_transactions
    WHERE tx_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    AND provider IN ('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'azam_pesa', 'bank_agency', 'other')
    GROUP BY provider
    ORDER BY commission_total DESC
");

$trend = $conn->query("
    SELECT DATE(tx_time) AS d,
           SUM(CASE WHEN tx_type='cash_in' THEN amount ELSE 0 END) AS cash_in_total,
           SUM(CASE WHEN tx_type='cash_out' THEN amount ELSE 0 END) AS cash_out_total,
           SUM(commission) AS commission_total
    FROM money_transactions
    WHERE tx_time >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    AND provider IN ('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'azam_pesa', 'bank_agency', 'other')
    GROUP BY DATE(tx_time)
    ORDER BY d ASC
");
$trend_labels = [];
$trend_in = [];
$trend_out = [];
$trend_commission = [];
while($r = $trend->fetch_assoc()) {
    $trend_labels[] = date('M d', strtotime($r['d']));
    $trend_in[] = (float)($r['cash_in_total'] ?? 0);
    $trend_out[] = (float)($r['cash_out_total'] ?? 0);
    $trend_commission[] = (float)($r['commission_total'] ?? 0);
}

$last_closing = $conn->query("SELECT closing_date, variance_cash, created_at FROM money_daily_closing ORDER BY closing_date DESC LIMIT 1")->fetch_assoc();

$recent = $conn->query("
    SELECT mt.*, e.username 
    FROM money_transactions mt 
    JOIN employees e ON e.id = mt.user_id
    WHERE mt.provider IN ('mpesa', 'mixx_by_yass', 'airtelmoney', 'halopesa', 'azam_pesa', 'bank_agency', 'other')
    ORDER BY mt.tx_time DESC
    LIMIT 10
");
?>

<div class="header-actions">
    <div>
        <h1>Mobile Money Dashboard</h1>
        <p class="text-muted">Cash-in / Cash-out operations overview (Tanzania).</p>
    </div>
    <div class="actions">
        <a href="money_transactions.php" class="btn btn-primary"><i class="fas fa-plus"></i> New Transaction</a>
    </div>
</div>

<?php if($last_closing): ?>
<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <h2>Last Daily Closing</h2>
        <a class="link" href="money_closing.php?date=<?php echo htmlspecialchars($last_closing['closing_date']); ?>">Open &rarr;</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Cash Variance</th>
                    <th>Saved</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong><?php echo date('M d, Y', strtotime($last_closing['closing_date'])); ?></strong></td>
                    <td class="<?php echo ((float)$last_closing['variance_cash'] === 0.0) ? 'ok' : 'bad'; ?>">
                        TSh <?php echo number_format((float)$last_closing['variance_cash'], 0); ?>
                    </td>
                    <td class="text-muted"><?php echo date('M d, H:i', strtotime($last_closing['created_at'])); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat">
        <div class="label">Today's Cash In</div>
        <div class="value">TSh <?php echo number_format((float)$today_in, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Cash Out</div>
        <div class="value">TSh <?php echo number_format((float)$today_out, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Commission</div>
        <div class="value">TSh <?php echo number_format((float)$today_commission, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Cash on Hand</div>
        <div class="value">TSh <?php echo number_format((float)$cash_balance, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Expenses</div>
        <div class="value"><?php echo $today_expenses['count']; ?> expenses</div>
        <div class="sub-value">TSh <?php echo number_format($today_expenses['total'], 0); ?></div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(3, minmax(0, 1fr));">
    <div class="stat">
        <div class="label">Total E-Float (All Providers)</div>
        <div class="value">TSh <?php echo number_format((float)$total_float_balance, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Transactions Today</div>
        <div class="value"><?php echo (int)$today_count; ?></div>
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <h2>7-Day Trend</h2>
        <span class="text-muted">Cash in/out and commission</span>
    </div>
    <div class="charts">
        <div class="chart-box">
            <div class="chart-title">Cash In vs Cash Out</div>
            <canvas id="txTrendChart" height="120"></canvas>
        </div>
        <div class="chart-box">
            <div class="chart-title">Commission</div>
            <canvas id="commissionChart" height="120"></canvas>
        </div>
    </div>
    <div class="help" style="margin-top: 10px;">
        <strong>Profit summary:</strong> Commission is your earnings. Fees are amounts collected/charged. Your business rule may differ, so both are tracked separately.
    </div>
</div>

<div class="card" style="margin-bottom: 24px;">
    <div class="card-head">
        <h2>Float Balance (per Provider)</h2>
        <a class="link" href="money_settings.php">Opening balances &rarr;</a>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Provider</th>
                    <th>Float Balance</th>
                    <th>7-day Cash In</th>
                    <th>7-day Cash Out</th>
                    <th>7-day Commission</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $sumMap = [];
                    while($s = $provider_summary->fetch_assoc()) { $sumMap[$s['provider']] = $s; }
                    foreach($display_providers as $p => $label):
                        $row = $sumMap[$p] ?? ['cash_in_total'=>0,'cash_out_total'=>0,'commission_total'=>0];
                ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($label); ?></strong></td>
                        <td><strong>TSh <?php echo number_format((float)$float_balance[$p], 0); ?></strong></td>
                        <td class="text-muted">TSh <?php echo number_format((float)$row['cash_in_total'], 0); ?></td>
                        <td class="text-muted">TSh <?php echo number_format((float)$row['cash_out_total'], 0); ?></td>
                        <td class="text-muted">TSh <?php echo number_format((float)$row['commission_total'], 0); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-head">
        <h2>Recent Transactions</h2>
        <a class="link" href="money_transactions.php">View all &rarr;</a>
    </div>
    <div class="table-container">
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
                <?php while($row = $recent->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <span class="pill <?php echo $row['tx_type'] === 'cash_in' ? 'pill-in' : 'pill-out'; ?>">
                                <?php echo $row['tx_type'] === 'cash_in' ? 'Cash In' : 'Cash Out'; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                                $p_code = $row['provider'];
                                echo htmlspecialchars($display_providers[$p_code] ?? $p_code);
                            ?>
                        </td>
                        <td><strong>TSh <?php echo number_format((float)$row['amount'], 0); ?></strong></td>
                        <td class="text-muted">TSh <?php echo number_format((float)$row['commission'], 0); ?></td>
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

<style>
    .header-actions { display:flex; justify-content:space-between; align-items:flex-end; gap: 16px; margin-bottom: 24px; }
    .text-muted { color:#64748b; font-size:14px; margin-top: 6px; }
    .stats-grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat { background:#fff; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .stat .label { font-size: 12px; color:#64748b; font-weight:700; text-transform: uppercase; letter-spacing: .4px; }
    .stat .value { font-size: 22px; color:#0f172a; font-weight: 800; margin-top: 8px; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom: 12px; }
    .card-head h2 { margin:0; font-size:16px; color:#0f172a; }
    .link { color:#007bff; text-decoration:none; font-weight:600; }
    .link:hover { text-decoration: underline; }
    .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
    .pill-in { background:#dcfce7; color:#166534; }
    .pill-out { background:#fee2e2; color:#991b1b; }
    .charts { display:grid; grid-template-columns: 2fr 1fr; gap: 16px; }
    .chart-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px; }
    .chart-title { font-size: 12px; font-weight: 900; text-transform: uppercase; color:#475569; letter-spacing: .4px; margin-bottom: 8px; }
    .help { font-size: 12px; color:#64748b; }
    .ok { color: #166534; font-weight: 800; }
    .bad { color: #991b1b; font-weight: 800; }
    @media (max-width: 992px) { .stats-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 992px) { .charts { grid-template-columns: 1fr; } }
    @media (max-width: 520px) { .stats-grid { grid-template-columns: 1fr; } }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels = <?php echo json_encode($trend_labels); ?>;
    const cashIn = <?php echo json_encode($trend_in); ?>;
    const cashOut = <?php echo json_encode($trend_out); ?>;
    const commission = <?php echo json_encode($trend_commission); ?>;

    const el1 = document.getElementById('txTrendChart');
    if (el1) {
        new Chart(el1, {
            type: 'line',
            data: {
                labels,
                datasets: [
                    { label: 'Cash In', data: cashIn, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,0.12)', fill: true, tension: 0.35, pointRadius: 3 },
                    { label: 'Cash Out', data: cashOut, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.10)', fill: true, tension: 0.35, pointRadius: 3 },
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });
    }

    const el2 = document.getElementById('commissionChart');
    if (el2) {
        new Chart(el2, {
            type: 'bar',
            data: {
                labels,
                datasets: [
                    { label: 'Commission', data: commission, backgroundColor: 'rgba(99,102,241,0.9)', borderRadius: 8 }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });
    }
});
</script>

<?php
$conn->close();
include 'footer.php';
?>

