<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

include 'dp.php';
include 'header.php';

// Role-based access control (Admin and Chairman only)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    include 'footer.php';
    exit;
}

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');
$agent_id = isset($_GET['agent_id']) ? (int)$_GET['agent_id'] : 0;

// Fetch Money Agents for filter
$agents = $conn->query("SELECT id, username FROM employees WHERE role = 'money_agent' ORDER BY username ASC");

// Build query conditions
$where_clauses = ["DATE(tx_time) BETWEEN ? AND ?"];
$params = [$start_date, $end_date];
$types = "ss";

if ($agent_id > 0) {
    $where_clauses[] = "user_id = ?";
    $params[] = $agent_id;
    $types .= "i";
}

$where_sql = implode(" AND ", $where_clauses);

// 1. Summary Metrics
$summary_query = "
    SELECT 
        COUNT(*) as total_tx,
        SUM(amount) as total_volume,
        SUM(commission) as total_commission,
        SUM(CASE WHEN tx_type = 'cash_in' THEN amount ELSE 0 END) as total_cash_in,
        SUM(CASE WHEN tx_type = 'cash_out' THEN amount ELSE 0 END) as total_cash_out
    FROM money_transactions
    WHERE $where_sql
";
$stmt = $conn->prepare($summary_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// 2. Transaction Trends (Chart Data)
$trend_query = "
    SELECT DATE(tx_time) as date, SUM(amount) as volume, SUM(commission) as commission
    FROM money_transactions
    WHERE $where_sql
    GROUP BY DATE(tx_time)
    ORDER BY date ASC
";
$stmt = $conn->prepare($trend_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$trend_result = $stmt->get_result();
$trend_labels = [];
$trend_volume = [];
$trend_commission = [];
while ($row = $trend_result->fetch_assoc()) {
    $trend_labels[] = date('M d', strtotime($row['date']));
    $trend_volume[] = $row['volume'];
    $trend_commission[] = $row['commission'];
}
$stmt->close();

// 3. Recent Transactions
$tx_query = "
    SELECT mt.*, e.username 
    FROM money_transactions mt
    JOIN employees e ON mt.user_id = e.id
    WHERE $where_sql
    ORDER BY mt.tx_time DESC
    LIMIT 50
";
$stmt = $conn->prepare($tx_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$transactions = $stmt->get_result();
$stmt->close();

// 4. Daily Closing Reports (Variances)
$closing_where = str_replace("tx_time", "closing_date", $where_sql);
$closing_query = "
    SELECT mdc.*, e.username
    FROM money_daily_closing mdc
    JOIN employees e ON mdc.user_id = e.id
    WHERE $closing_where
    ORDER BY mdc.closing_date DESC
    LIMIT 20
";
$stmt = $conn->prepare($closing_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$closings = $stmt->get_result();
$stmt->close();

?>

<div class="header-actions">
    <h1>Money Agent Monitoring</h1>
    <p class="text-muted">Overview of mobile money operations, transactions, and agent performance.</p>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="money_agent_monitoring.php" autocomplete="off">
            <div class="form-row align-items-end">
                <div class="form-group col-md-2">
                    <label>Start Date</label>
                    <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group col-md-2">
                    <label>End Date</label>
                    <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="form-group col-md-3">
                    <label>Agent</label>
                    <select name="agent_id" class="form-control">
                        <option value="">All Agents</option>
                        <?php while($agent = $agents->fetch_assoc()): ?>
                            <option value="<?php echo $agent['id']; ?>" <?php if($agent_id == $agent['id']) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($agent['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group col-md-2">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn btn-primary btn-block">Filter Report</button>
                </div>
                <div class="form-group col-md-3">
                    <label>&nbsp;</label>
                    <a href="money_export.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success btn-block">
                        <i class="fas fa-file-csv"></i> Export to CSV
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-cards">
    <div class="card card-stats">
        <div class="card-icon icon-blue"><i class="fas fa-chart-line"></i></div>
        <div class="card-info">
            <div class="card-title">Total Volume</div>
            <div class="card-value"><?php echo number_format($summary['total_volume'], 2); ?></div>
            <div class="card-sub"><?php echo $summary['total_tx']; ?> Transactions</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon icon-green"><i class="fas fa-hand-holding-usd"></i></div>
        <div class="card-info">
            <div class="card-title">Total Commission</div>
            <div class="card-value text-success"><?php echo number_format($summary['total_commission'], 2); ?></div>
            <div class="card-sub">Revenue earned</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon icon-purple"><i class="fas fa-exchange-alt"></i></div>
        <div class="card-info">
            <div class="card-title">Net Cash Flow</div>
            <div class="card-value">
                <div class="cash-flow-item">
                    <span class="label">In:</span>
                    <span class="value text-success"><?php echo number_format($summary['total_cash_in']); ?></span>
                </div>
                <div class="cash-flow-item">
                    <span class="label">Out:</span>
                    <span class="value text-warning"><?php echo number_format($summary['total_cash_out']); ?></span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card chart-card">
            <div class="card-header">
                <h2>Transaction Volume & Commission Trend</h2>
                <span class="text-muted"><i class="far fa-calendar-alt"></i> Last 30 Days</span>
            </div>
            <div class="card-body" style="padding: 24px;">
                <canvas id="trendChart" height="100"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Recent Transactions Table -->
<div class="card mt-4">
    <div class="card-header">
        <h2>Recent Transactions</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agent</th>
                    <th>Type</th>
                    <th>Provider</th>
                    <th>Amount</th>
                    <th>Commission</th>
                    <th>Reference</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions->num_rows > 0): ?>
                    <?php while($row = $transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('M d, H:i', strtotime($row['tx_time'])); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td>
                            <span class="badge badge-<?php echo $row['tx_type'] == 'cash_in' ? 'success' : 'warning'; ?>">
                                <?php echo $row['tx_type'] == 'cash_in' ? 'Cash In' : 'Cash Out'; ?>
                            </span>
                        </td>
                        <td>
                            <?php 
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
                                $p_code = $row['provider'];
                                echo htmlspecialchars($display_providers[$p_code] ?? ucfirst($p_code)); 
                            ?>
                        </td>
                        <td><strong><?php echo number_format($row['amount'], 2); ?></strong></td>
                        <td class="text-success">+<?php echo number_format($row['commission'], 2); ?></td>
                        <td><small><?php echo htmlspecialchars($row['reference']); ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="7" class="text-center">No transactions found for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Daily Closing Reports -->
<div class="card mt-4">
    <div class="card-header">
        <h2>Daily Closing Reports (Variances)</h2>
        <span class="text-muted"><i class="fas fa-exclamation-triangle"></i> Discrepancy Monitoring</span>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Agent</th>
                    <th class="text-right">Cash Variance</th>
                    <th class="text-right">Float Variance (Total)</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($closings->num_rows > 0): ?>
                    <?php while($row = $closings->fetch_assoc()): ?>
                    <?php 
                        $total_float_variance = 
                            ($row['variance_float_mpesa'] ?? 0) + 
                            ($row['variance_float_mixx_by_yass'] ?? 0) + 
                            ($row['variance_float_airtelmoney'] ?? 0) + 
                            ($row['variance_float_halopesa'] ?? 0) + 
                            ($row['variance_float_azam_pesa'] ?? 0) + 
                            ($row['variance_float_bank_agency'] ?? 0) + 
                            ($row['variance_float_kingamuzi'] ?? 0) + 
                            ($row['variance_float_government'] ?? 0) + 
                            ($row['variance_float_other'] ?? 0);
                        
                        $has_issue = ($row['variance_cash'] != 0 || $total_float_variance != 0);
                    ?>
                    <tr>
                        <td><strong><?php echo date('M d, Y', strtotime($row['closing_date'])); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td class="text-right <?php echo $row['variance_cash'] != 0 ? 'text-danger font-bold' : 'text-success'; ?>">
                            <?php echo $row['variance_cash'] > 0 ? '+' : ''; ?><?php echo number_format($row['variance_cash'], 2); ?>
                        </td>
                        <td class="text-right <?php echo $total_float_variance != 0 ? 'text-danger font-bold' : 'text-success'; ?>">
                            <?php echo $total_float_variance > 0 ? '+' : ''; ?><?php echo number_format($total_float_variance, 2); ?>
                        </td>
                        <td>
                            <?php if ($has_issue): ?>
                                <span class="badge badge-danger">Issue Detected</span>
                            <?php else: ?>
                                <span class="badge badge-success">Balanced</span>
                            <?php endif; ?>
                        </td>
                        <td><small class="text-muted"><?php echo !empty($row['notes']) ? htmlspecialchars($row['notes']) : 'No notes provided'; ?></small></td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="6" class="text-center" style="padding: 40px;">No closing reports found for this period.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('trendChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trend_labels); ?>,
            datasets: [{
                label: 'Volume',
                data: <?php echo json_encode($trend_volume); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                yAxisID: 'y',
                tension: 0.3,
                fill: true
            }, {
                label: 'Commission',
                data: <?php echo json_encode($trend_commission); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                yAxisID: 'y1',
                tension: 0.3,
                fill: true
            }]
        },
        options: {
            responsive: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: { display: true, text: 'Volume Amount' }
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    grid: { drawOnChartArea: false },
                    title: { display: true, text: 'Commission Earned' }
                }
            }
        }
    });
</script>

<style>
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-danger { background: #fee2e2; color: #991b1b; }
    .text-success { color: #16a34a; }
    .text-danger { color: #dc2626; }
    .text-warning { color: #d97706; }
    .text-primary { color: #2563eb; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 700; }
    
    .card-header { 
        padding: 16px 20px; 
        border-bottom: 1px solid #e2e8f0; 
        background: #f8fafc;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .card-header h2 { margin: 0; font-size: 18px; color: #334155; }
    
    .form-row { display: flex; gap: 15px; flex-wrap: wrap; }
    .form-group { flex: 1; min-width: 200px; margin-bottom: 0; }
    
    .card-stats {
        display: flex;
        flex-direction: row !important;
        align-items: center;
        padding: 24px !important;
        gap: 20px;
    }
    .card-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #f0fdf4; color: #10b981; }
    .icon-purple { background: #faf5ff; color: #a855f7; }
    
    .cash-flow-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: 14px;
        line-height: 1.4;
    }
    .cash-flow-item .label { color: #64748b; font-weight: 500; margin-right: 8px; }
    .cash-flow-item .value { font-weight: 700; }
    
    .card-value {
        font-size: 24px;
        font-weight: 800;
        color: #1e293b;
        letter-spacing: -0.5px;
    }
    .card-sub {
        font-size: 13px;
        color: #64748b;
        margin-top: 4px;
    }
    
    .table-container {
        margin-top: 0;
    }
</style>

<?php include 'footer.php'; ?>
