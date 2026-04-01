<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect Money Agent to their dedicated dashboard BEFORE any output.
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true && isset($_SESSION['role']) && $_SESSION['role'] === 'money_agent') {
    header('Location: money_dashboard.php');
    exit;
}

include 'header.php';
include 'dp.php';

// Fetch dashboard data
$total_products = $conn->query("SELECT COUNT(*) AS count FROM products")->fetch_assoc()['count'];
$total_sales_today = $conn->query("SELECT SUM(total_amount) AS total FROM sales WHERE DATE(sale_date) = CURDATE()")->fetch_assoc()['total'] ?? 0;

// Fetch today's profit - Admin/Chairman only
$profit_today = 0;
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman') {
    $profit_result = $conn->query("
        SELECT SUM((si.price_per_unit - p.cost_price) * si.quantity) AS profit
        FROM sale_items si
        JOIN products p ON si.product_id = p.id
        JOIN sales s ON si.sale_id = s.id
        WHERE DATE(s.sale_date) = CURDATE()
    ");
    if ($profit_result) {
        $profit_today = $profit_result->fetch_assoc()['profit'] ?? 0;
    }
}

// Fetch bill payment statistics for admin/chairman
$kingamuzi_payments = null;
$government_payments = null;
$today_bill_payments = ['count' => 0, 'total' => 0];

if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman') {
    $kingamuzi_payments = $conn->query("
        SELECT COUNT(*) AS count, SUM(amount) AS total 
        FROM money_transactions 
        WHERE provider = 'kingamuzi' 
        AND DATE(tx_time) = CURDATE()
    ")->fetch_assoc();

    $government_payments = $conn->query("
        SELECT COUNT(*) AS count, SUM(amount) AS total 
        FROM money_transactions 
        WHERE provider = 'government' 
        AND DATE(tx_time) = CURDATE()
    ")->fetch_assoc();

    $today_bill_payments = [
        'kingamuzi_count' => $kingamuzi_payments['count'],
        'kingamuzi_total' => $kingamuzi_payments['total'],
        'government_count' => $government_payments['count'],
        'government_total' => $government_payments['total'],
        'count' => ($kingamuzi_payments['count'] + $government_payments['count']),
        'total' => ($kingamuzi_payments['total'] + $government_payments['total'])
    ];
}

// Fetch today's expenses - show personal for non-admin/chairman, all for admin/chairman
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman') {
    $today_expenses = $conn->query("
        SELECT COUNT(*) as count, SUM(amount) as total 
        FROM expenses 
        WHERE DATE(expense_date) = CURDATE()
    ")->fetch_assoc();
} else {
    // For regular staff, show only their expenses
    $today_expenses = $conn->query("
        SELECT COUNT(*) as count, SUM(amount) as total 
        FROM expenses 
        WHERE DATE(expense_date) = CURDATE() AND recorded_by = " . $_SESSION['user_id'] . "
    ")->fetch_assoc();
}

$low_stock_products = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < 10")->fetch_assoc()['count'];
// Fetch recent sales - role-based filtering
$recent_sales = null;
if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman') {
    // Admin/Chairman see all sales
    $recent_sales = $conn->query("SELECT s.*, u.username FROM sales s JOIN employees u ON s.user_id = u.id ORDER BY s.sale_date DESC LIMIT 5");
} elseif ($_SESSION['role'] === 'manager') {
    // Manager sees only their own sales
    $recent_sales = $conn->query("SELECT s.* FROM sales s WHERE s.user_id = " . (int)$_SESSION['user_id'] . " ORDER BY s.sale_date DESC LIMIT 5");
} else {
    // Regular staff see only their own sales
    $recent_sales = $conn->query("SELECT s.* FROM sales s WHERE s.user_id = " . (int)$_SESSION['user_id'] . " ORDER BY s.sale_date DESC LIMIT 5");
}

// Fetch recent activity for Chairman
$recent_activity = $conn->query("
    SELECT al.*, e.username, e.role 
    FROM activity_logs al 
    JOIN employees e ON al.user_id = e.id 
    ORDER BY al.log_date DESC 
    LIMIT 10
");

// Fetch 7-day sales trend for Chairman
$sales_trend = $conn->query("
    SELECT DATE(sale_date) as date, SUM(total_amount) as total 
    FROM sales 
    WHERE sale_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
    GROUP BY DATE(sale_date) 
    ORDER BY date ASC
");
$trend_labels = [];
$trend_data = [];
while($row = $sales_trend->fetch_assoc()) {
    $trend_labels[] = date('M d', strtotime($row['date']));
    $trend_data[] = $row['total'];
}

// Fetch top performing employees for Chairman
$top_employees = $conn->query("
    SELECT e.username, COUNT(s.id) as sales_count, SUM(s.total_amount) as total_revenue
    FROM employees e
    JOIN sales s ON e.id = s.user_id
    WHERE s.sale_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY e.id
    ORDER BY total_revenue DESC
    LIMIT 3
");

?>

<div class="dashboard-header">
    <h1><?php echo $_SESSION['role'] === 'chairman' ? 'Executive Dashboard' : 'Dashboard Overview'; ?></h1>
    <p class="text-muted">Welcome back, <strong><?php echo $_SESSION['role'] === 'chairman' ? 'Sylvester Ndallah' : htmlspecialchars($_SESSION['username']); ?></strong> (<?php echo ucfirst($_SESSION['role']); ?>). Here is the latest company activity.</p>
</div>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-icon icon-blue"><i class="fas fa-box"></i></div>
        <div class="card-info">
            <div class="card-title">Total Products</div>
            <div class="card-value"><?php echo $total_products; ?></div>
        </div>
    </div>
    <div class="card">
        <div class="card-icon icon-green"><i class="fas fa-money-bill-wave"></i></div>
        <div class="card-info">
            <div class="card-title">Today's Sales</div>
            <div class="card-value">TSh <?php echo number_format($total_sales_today, 0); ?></div>
        </div>
    </div>
    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
    <div class="card">
        <div class="card-icon icon-purple"><i class="fas fa-chart-line"></i></div>
        <div class="card-info">
            <div class="card-title">Today's Profit</div>
            <div class="card-value">TSh <?php echo number_format($profit_today, 0); ?></div>
        </div>
    </div>
    <?php endif; ?>
    <div class="card">
        <div class="card-icon icon-red"><i class="fas fa-receipt"></i></div>
        <div class="card-info">
            <div class="card-title">Today's Expenses</div>
            <div class="card-value"><?php echo $today_expenses['count']; ?> expenses</div>
            <div class="card-subtitle">TSh <?php echo number_format($today_expenses['total'], 0); ?></div>
        </div>
    </div>
    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
    <div class="card">
        <div class="card-icon icon-yellow"><i class="fas fa-credit-card"></i></div>
        <div class="card-info">
            <div class="card-title">Today's Bill Payments</div>
            <div class="card-value"><?php echo $today_bill_payments['count']; ?> payments</div>
            <div class="card-subtitle">TSh <?php echo number_format($today_bill_payments['total'], 0); ?></div>
        </div>
    </div>
    <?php endif; ?>
    <?php if($_SESSION['role'] !== 'chairman'): ?>
    <div class="card">
        <div class="card-icon icon-orange"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="card-info">
            <div class="card-title">Low Stock Alerts</div>
            <div class="card-value"><?php echo $low_stock_products; ?></div>
        </div>
    </div>
    <?php endif; ?>
    <?php if($_SESSION['role'] === 'chairman'): ?>
    <div class="card">
        <div class="card-icon icon-blue"><i class="fas fa-users"></i></div>
        <div class="card-info">
            <div class="card-title">Staff Activity</div>
            <div class="card-value"><?php echo $conn->query("SELECT COUNT(*) as count FROM activity_logs WHERE DATE(log_date) = CURDATE()")->fetch_assoc()['count']; ?></div>
        </div>
    </div>
    <?php endif; ?>
</div>

<div class="dashboard-grid <?php if($_SESSION['role'] === 'chairman') echo 'chairman-grid'; ?>">
    <?php if($_SESSION['role'] !== 'chairman'): ?>
    <div class="grid-item">
        <h2>Recent Sales</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
                        <th>User</th>
                        <?php endif; ?>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($recent_sales): while($row = $recent_sales->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <?php endif; ?>
                        <td>$<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($row['sale_date'])); ?></td>
                    </tr>
                    <?php endwhile; endif; ?>
                </tbody>
            </table>
        </div>
        <a href="reports.php" class="view-all">View all reports &rarr;</a>
    </div>
    <?php else: ?>
        <div class="grid-item">
            <h2>Employee Monitoring (Recent Actions)</h2>
            <div class="table-container">
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Action</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $recent_activity->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                                    <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td class="text-muted"><?php echo date('H:i', strtotime($row['log_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <a href="employees.php" class="view-all">Full activity logs &rarr;</a>
        </div>

        <div class="grid-item">
            <h2>Top Performers (30 Days)</h2>
            <div class="top-performers">
                <?php while($emp = $top_employees->fetch_assoc()): ?>
                    <div class="performer-item">
                        <div class="performer-info">
                            <div class="user-avatar small"><?php echo strtoupper(substr($emp['username'], 0, 1)); ?></div>
                            <span class="performer-name"><?php echo htmlspecialchars($emp['username']); ?></span>
                        </div>
                        <div class="performer-stats">
                            <span class="performer-rev">TSh <?php echo number_format($emp['total_revenue'], 0); ?></span>
                            <span class="performer-sales"><?php echo $emp['sales_count']; ?> sales</span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="grid-item">
            <h2>Today's Bill Payments</h2>
            <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr);">
                <div class="stat">
                    <div class="label">Kingamuzi Payments</div>
                    <div class="value"><?php echo $today_bill_payments['kingamuzi_count'] ?? 0; ?></div>
                    <div class="sub-value">TSh <?php echo number_format($today_bill_payments['kingamuzi_total'] ?? 0, 0); ?></div>
                </div>
                <div class="stat">
                    <div class="label">Government Payments</div>
                    <div class="value"><?php echo $today_bill_payments['government_count'] ?? 0; ?></div>
                    <div class="sub-value">TSh <?php echo number_format($today_bill_payments['government_total'] ?? 0, 0); ?></div>
                </div>
            </div>
            <a href="payments.php" class="view-all">View all bill payments &rarr;</a>
        </div>
    <?php endif; ?>

    <div class="grid-item">
        <h2><?php echo $_SESSION['role'] === 'chairman' ? 'Sales Trend (7 Days)' : 'Stock Status'; ?></h2>
        <canvas id="<?php echo $_SESSION['role'] === 'chairman' ? 'trendChart' : 'stockChart'; ?>" style="max-height: 250px;"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if($_SESSION['role'] !== 'chairman'): ?>
    const chartElement = document.getElementById('stockChart');
    if (chartElement) {
        const ctx = chartElement.getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{
                    data: [
                        <?php 
                            $in = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity >= 10")->fetch_assoc()['count'];
                            $low = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity > 0 AND quantity < 10")->fetch_assoc()['count'];
                            $out = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= 0")->fetch_assoc()['count'];
                            echo "$in, $low, $out";
                        ?>
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444'],
                    borderWidth: 0
                }]
            },
            options: {
                plugins: {
                    legend: { position: 'bottom' }
                },
                cutout: '70%'
            }
        });
    }
    <?php else: ?>
    const trendElement = document.getElementById('trendChart');
    if (trendElement) {
        const ctx = trendElement.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($trend_labels); ?>,
                datasets: [{
                    label: 'Daily Revenue',
                    data: <?php echo json_encode($trend_data); ?>,
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    <?php endif; ?>
});
</script>

<style>
    .dashboard-header { margin-bottom: 32px; }
    .text-muted { color: #718096; margin-top: 4px; }
    .dashboard-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    .card {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        transition: transform 0.2s;
    }
    .card:hover { transform: translateY(-4px); }
    .card-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-right: 16px;
    }
    .icon-blue { background: #ebf8ff; color: #3182ce; }
    .icon-green { background: #f0fff4; color: #38a169; }
    .icon-purple { background: #f3e8ff; color: #7e22ce; }
    .icon-orange { background: #fffaf0; color: #dd6b20; }
    .card-title { font-size: 14px; color: #718096; font-weight: 500; }
    .card-value { font-size: 24px; font-weight: 700; color: #2d3748; margin-top: 4px; }
    
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
    }
    .grid-item {
        background: white;
        padding: 24px;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .grid-item h2 { font-size: 18px; margin-bottom: 20px; color: #1a202c; }
    .view-all {
        display: block;
        margin-top: 16px;
        color: #007bff;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        transition: transform 0.2s;
    }
    .view-all:hover {
        text-decoration: underline;
        transform: translateX(3px);
    }

    /* Chairman Styles */
    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar { width: 32px; height: 32px; background: #e2e8f0; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
    .user-avatar.small { width: 24px; height: 24px; font-size: 10px; }
    .activity-table td { padding: 12px 8px; font-size: 13px; }

    .top-performers { display: flex; flex-direction: column; gap: 16px; }
    .performer-item { display: flex; justify-content: space-between; align-items: center; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
    .performer-item:last-child { border-bottom: none; }
    .performer-info { display: flex; align-items: center; gap: 10px; }
    .performer-name { font-weight: 600; color: #2d3748; font-size: 14px; }
    .performer-stats { text-align: right; display: flex; flex-direction: column; }
    .performer-rev { font-weight: 700; color: #10b981; font-size: 14px; }
    .performer-sales { font-size: 12px; color: #718096; }

    /* Dashboard Mobile Responsiveness */
    @media (max-width: 992px) {
        .dashboard-grid { 
            grid-template-columns: 1fr; 
        }
    }

    @media (max-width: 768px) {
        .dashboard-cards {
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .card {
            padding: 16px;
        }
        .card-icon {
            width: 40px;
            height: 40px;
            font-size: 16px;
            margin-right: 12px;
        }
        .card-value {
            font-size: 18px;
        }
        .grid-item {
            padding: 16px;
        }
    }

    @media (max-width: 480px) {
        .dashboard-cards {
            grid-template-columns: 1fr;
        }
        .dashboard-header h1 {
            font-size: 20px;
        }
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>