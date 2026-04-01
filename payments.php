<?php
include 'header.php';
include 'dp.php';

// Payment services configuration - moved to global scope
$payment_services = [
    'kingamuzi' => [
        'azam_tv' => 'Azam TV',
        'dstv' => 'DSTV',
        'zuku' => 'Zuku',
        'startimes' => 'StarTimes'
    ],
    'government' => [
        'luku' => 'LUKU',
        'maji' => 'Maji',
        'tra' => 'TRA',
        'ada' => 'Ada',
        'others' => 'Others'
    ]
];

if (!isset($_SESSION['role']) || ($_SESSION['role'] !== 'money_agent' && $_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman')) {
    echo "<div class='alert alert-danger'>Access Denied. Money Agents, Admins and Chairman only.</div>";
    include 'footer.php';
    exit;
}

// Check if user can add payments (not chairman)
$can_add_payments = ($_SESSION['role'] === 'money_agent' || $_SESSION['role'] === 'admin');

$message = '';

function mt_bind_params($stmt, string $types, array $params): void {
    $refs = [];
    $refs[] = &$types;
    foreach ($params as $k => $v) {
        $refs[] = &$params[$k];
    }
    call_user_func_array([$stmt, 'bind_param'], $refs);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    $payment_service = strtolower(trim((string)($_POST['payment_service'] ?? '')));
    $provider = strtolower(trim((string)($_POST['provider'] ?? '')));
    $customer_paid = (float)($_POST['customer_paid'] ?? 0);
    $commission = (float)($_POST['commission'] ?? 0);
    $customer_name = trim((string)($_POST['customer_name'] ?? ''));
    $customer_msisdn = trim((string)($_POST['customer_msisdn'] ?? ''));
    $reference = trim((string)($_POST['reference'] ?? ''));
    $notes = trim((string)($_POST['notes'] ?? ''));

    $allowed_providers = ['kingamuzi', 'government'];

    if (!in_array($provider, $allowed_providers, true)) {
        $message = "<div class='alert alert-danger'>Invalid payment provider.</div>";
    } elseif (empty($payment_service) || !isset($payment_services[$provider][$payment_service])) {
        $message = "<div class='alert alert-danger'>Please select a valid payment service.</div>";
    } elseif ($customer_paid <= 0) {
        $message = "<div class='alert alert-danger'>Customer payment must be greater than 0.</div>";
    } elseif ($commission < 0) {
        $message = "<div class='alert alert-danger'>Commission cannot be negative.</div>";
    } else {
        // Record customer payment as cash_in (company income)
        $tx_type = 'cash_in';
        $amount = $customer_paid;
        
        // Only insert payment_service if column exists
        $check_column = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'payment_service'");
        $payment_service_exists = ($check_column->num_rows > 0);
        
        // Insert customer payment transaction
        if ($payment_service_exists) {
            $stmt = $conn->prepare("INSERT INTO money_transactions (user_id, tx_type, provider, amount, commission, customer_msisdn, customer_name, payment_service, reference, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssss", $_SESSION['user_id'], $tx_type, $provider, $amount, $commission, $customer_msisdn, $customer_name, $payment_service, $reference, $notes);
        } else {
            $stmt = $conn->prepare("INSERT INTO money_transactions (user_id, tx_type, provider, amount, commission, customer_msisdn, customer_name, reference, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $_SESSION['user_id'], $tx_type, $provider, $amount, $commission, $customer_msisdn, $customer_name, $reference, $notes);
        }
        $stmt->execute();
        $stmt->close();

        $service_name = $payment_service_exists ? $payment_services[$provider][$payment_service] : '';
        $action = "Recorded payment - " . ucfirst($provider) . ($service_name ? " (" . $service_name . ")" : "") . ": TSh " . number_format($amount, 0) . ", customer: " . htmlspecialchars($customer_name);
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $_SESSION['user_id'], $action);
        $log->execute();
        $log->close();

        $message = "<div class='alert alert-success'>Payment recorded successfully.</div>";
    }
}

// Filters (GET)
$start_date = (string)($_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')));
$end_date = (string)($_GET['end_date'] ?? date('Y-m-d'));
$filter_provider = strtolower(trim((string)($_GET['provider'] ?? 'all')));
$q = trim((string)($_GET['q'] ?? ''));

$allowed_providers = ['all', 'kingamuzi', 'government'];

$display_providers = [
    'kingamuzi' => 'Kingamuzi (TV)',
    'government' => 'Govt Payments'
];

if (!in_array($filter_provider, $allowed_providers, true)) $filter_provider = 'all';

$where = ["DATE(mt.tx_time) BETWEEN ? AND ?", "mt.provider IN ('kingamuzi', 'government')"];
$types = "ss";
$params = [$start_date, $end_date];

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
$payments = $stmt->get_result();
$stmt->close();

// Get statistics for each service
// First check if payment_service column exists
$check_column = $conn->query("SHOW COLUMNS FROM money_transactions LIKE 'payment_service'");
$payment_service_exists = ($check_column->num_rows > 0);

if ($payment_service_exists) {
    $today_azam_tv = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='kingamuzi' AND payment_service='azam_tv' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_dstv = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='kingamuzi' AND payment_service='dstv' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_zuku = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='kingamuzi' AND payment_service='zuku' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_startimes = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='kingamuzi' AND payment_service='startimes' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_kingamuzi_total = $today_azam_tv + $today_dstv + $today_zuku + $today_startimes;

    $today_luku = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND payment_service='luku' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_maji = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND payment_service='maji' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_tra = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND payment_service='tra' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_ada = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND payment_service='ada' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_others = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND payment_service='others' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_government_total = $today_luku + $today_maji + $today_tra + $today_ada + $today_others;
} else {
    // Fallback to provider-level statistics if payment_service column doesn't exist
    $today_kingamuzi_total = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='kingamuzi' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    $today_government_total = $conn->query("SELECT SUM(amount) AS total FROM money_transactions WHERE provider='government' AND DATE(tx_time)=CURDATE()")->fetch_assoc()['total'] ?? 0;
    
    // Set individual service totals to 0 for display
    $today_azam_tv = $today_dstv = $today_zuku = $today_startimes = 0;
    $today_luku = $today_maji = $today_tra = $today_ada = $today_others = 0;
}

$today_total = $today_kingamuzi_total + $today_government_total;
$today_count = $conn->query("SELECT COUNT(*) AS cnt FROM money_transactions WHERE provider IN ('kingamuzi', 'government') AND DATE(tx_time)=CURDATE()")->fetch_assoc()['cnt'] ?? 0;
?>

<div class="header-actions">
    <div>
        <h1>Bill Payments</h1>
        <p class="text-muted">Kingamuzi (TV) and Government payments processing.</p>
    </div>
    <div class="actions">
        <a href="money_dashboard.php" class="btn btn-secondary">&larr; Mobile Money Dashboard</a>
    </div>
</div>

<?php echo $message; ?>

<div class="stats-grid">
    <div class="stat">
        <div class="label">Today's Kingamuzi TV</div>
        <div class="value">TSh <?php echo number_format((float)$today_kingamuzi_total, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Government</div>
        <div class="value">TSh <?php echo number_format((float)$today_government_total, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Today's Total</div>
        <div class="value">TSh <?php echo number_format((float)$today_total, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Payments Today</div>
        <div class="value"><?php echo (int)$today_count; ?></div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 24px;">
    <div class="stat">
        <div class="label">Azam TV</div>
        <div class="value">TSh <?php echo number_format((float)$today_azam_tv, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">DSTV</div>
        <div class="value">TSh <?php echo number_format((float)$today_dstv, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Zuku</div>
        <div class="value">TSh <?php echo number_format((float)$today_zuku, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">StarTimes</div>
        <div class="value">TSh <?php echo number_format((float)$today_startimes, 0); ?></div>
    </div>
</div>

<div class="stats-grid" style="grid-template-columns: repeat(5, minmax(0, 1fr)); margin-bottom: 24px;">
    <div class="stat">
        <div class="label">LUKU</div>
        <div class="value">TSh <?php echo number_format((float)$today_luku, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Maji</div>
        <div class="value">TSh <?php echo number_format((float)$today_maji, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">TRA</div>
        <div class="value">TSh <?php echo number_format((float)$today_tra, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Ada</div>
        <div class="value">TSh <?php echo number_format((float)$today_ada, 0); ?></div>
    </div>
    <div class="stat">
        <div class="label">Others</div>
        <div class="value">TSh <?php echo number_format((float)$today_others, 0); ?></div>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2><i class="fas fa-plus-circle"></i> New Payment</h2>
        <?php if($can_add_payments): ?>
        <form action="payments.php" method="post" autocomplete="off">
            <div class="form-row">
                <div class="form-group">
                    <label>Payment Category</label>
                    <select name="provider" id="provider" class="form-control" required onchange="updateServices()">
                        <option value="">Select Category</option>
                        <?php foreach($display_providers as $p => $label): ?>
                            <option value="<?php echo $p; ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Payment Service</label>
                    <select name="payment_service" id="payment_service" class="form-control" required>
                        <option value="">Select service first</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Customer Paid (TSh)</label>
                    <input type="number" step="0.01" name="customer_paid" class="form-control" required placeholder="Amount customer paid">
                </div>
                <div class="form-group">
                    <label>Commission Earned (TSh)</label>
                    <input type="number" step="0.01" name="commission" class="form-control" value="0" placeholder="Your commission from this payment">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Profit (TSh)</label>
                    <div class="profit-display">
                        <span id="profit_amount">0</span>
                        <small class="text-muted">(Commission Earned)</small>
                    </div>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div class="help" style="margin-top:6px;">Commission is your earnings from this bill payment service.</div>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" class="form-control" placeholder="Customer full name">
                </div>
                <div class="form-group">
                    <label>Customer Phone (optional)</label>
                    <input type="text" name="customer_msisdn" class="form-control" placeholder="e.g. 2557xxxxxxxx">
                </div>
            </div>

            <div class="form-group">
                <label>Notes (optional)</label>
                <input type="text" name="notes" class="form-control" placeholder="Any payment details...">
            </div>

            <button type="submit" name="add_payment" class="btn btn-primary">Record Payment</button>
        </form>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> 
            As Chairman, you can only view bill payment history. To record new payments, please contact your Money Agent or Admin.
        </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head">
            <h2><i class="fas fa-list"></i> Recent Payments</h2>
            <span class="text-muted">Last 200</span>
        </div>
        
        <div class="card" style="margin-bottom: 16px; background: #f8fafc;">
            <div class="card-head">
                <h2><i class="fas fa-filter"></i> Filters</h2>
                <a class="btn btn-secondary" href="payments.php">Reset</a>
            </div>
            <form method="get" action="payments.php" class="filters" autocomplete="off">
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

        <div class="table-container" style="max-height: 400px; overflow:auto;">
            <table>
                <thead>
                    <tr>
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
                    <?php while($row = $payments->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="pill pill-payment">
                                    <?php 
                                        $p_code = $row['provider'];
                                        if ($payment_service_exists && isset($row['payment_service'])) {
                                            $service_code = $row['payment_service'];
                                            if ($p_code === 'kingamuzi') {
                                                echo 'Kingamuzi - ' . ($payment_services[$p_code][$service_code] ?? $service_code);
                                            } elseif ($p_code === 'government') {
                                                echo 'Govt - ' . ($payment_services[$p_code][$service_code] ?? $service_code);
                                            } else {
                                                echo htmlspecialchars($display_providers[$p_code] ?? $p_code);
                                            }
                                        } else {
                                            echo htmlspecialchars($display_providers[$p_code] ?? $p_code);
                                        }
                                    ?>
                                </span>
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
    .stats-grid { display:grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; margin-bottom: 24px; }
    .stat { background:#fff; border-radius: 12px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .stat .label { font-size: 12px; color:#64748b; font-weight:700; text-transform: uppercase; letter-spacing: .4px; }
    .stat .value { font-size: 22px; color:#0f172a; font-weight: 800; margin-top: 8px; }
    .grid { display:grid; grid-template-columns: 1fr 1.2fr; gap: 24px; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { margin:0 0 14px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap: 10px; }
    .card-head { display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px; }
    .filters { margin-top: 10px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .form-row { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group { margin-bottom: 12px; }
    .form-group label { display:block; font-size: 12px; font-weight: 800; color:#334155; margin-bottom: 6px; }
    .profit-display { 
        background: #f0fdf4; 
        border: 1px solid #bbf7d0; 
        border-radius: 8px; 
        padding: 12px; 
        text-align: center;
    }
    .profit-display #profit_amount { 
        font-size: 24px; 
        font-weight: 800; 
        color: #166534; 
        display: block;
    }
    .pill { display:inline-flex; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 800; }
    .pill-payment { background:#fbbf24; color:#78350f; }
    @media (max-width: 1100px) { 
        .grid { grid-template-columns: 1fr; }
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) { 
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>

<script>
const paymentServices = <?php echo json_encode($payment_services); ?>;

function updateServices() {
    console.log('Updating services...');
    const providerSelect = document.getElementById('provider');
    const serviceSelect = document.getElementById('payment_service');
    const selectedProvider = providerSelect.value;
    
    console.log('Selected provider:', selectedProvider);
    console.log('Available services:', paymentServices);
    
    // Clear current options
    serviceSelect.innerHTML = '<option value="">Select service</option>';
    
    if (selectedProvider && paymentServices[selectedProvider]) {
        console.log('Services for provider:', paymentServices[selectedProvider]);
        Object.entries(paymentServices[selectedProvider]).forEach(([key, value]) => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = value;
            serviceSelect.appendChild(option);
        });
    }
}

function calculateProfit() {
    const commission = parseFloat(document.querySelector('input[name="commission"]').value) || 0;
    
    const profitDisplay = document.getElementById('profit_amount');
    profitDisplay.textContent = commission.toFixed(2);
    
    // Update profit display color based on value
    if (commission > 0) {
        profitDisplay.style.color = '#166534';
    } else if (commission < 0) {
        profitDisplay.style.color = '#dc2626';
    } else {
        profitDisplay.style.color = '#6b7280';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateServices();
    
    // Add event listeners for profit calculation
    document.querySelector('input[name="commission"]').addEventListener('input', calculateProfit);
    
    // Provider change listener
    document.getElementById('provider').addEventListener('change', updateServices);
});

// Initialize form on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Page loaded, initializing...');
    updateServices();
    
    // Add change event listener for debugging
    document.getElementById('provider').addEventListener('change', function() {
        console.log('Provider changed to:', this.value);
        updateServices();
    });
    
    // Reset form on successful submission
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('success') === '1') {
        console.log('Form reset triggered');
        document.querySelector('form').reset();
        updateServices();
    }
});
</script>

<?php
$conn->close();
include 'footer.php';
?>
