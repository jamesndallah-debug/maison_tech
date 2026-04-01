<?php
include 'header.php';
include 'dp.php';

// Role-based access control (Admin and Chairman only)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    include 'footer.php';
    exit;
}

// Default date range (last 30 days)
$start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_POST['end_date'] ?? date('Y-m-d');

// 1. Sales Over Time Data
$sales_data_query = $conn->prepare("
    SELECT DATE(sale_date) as date, SUM(total_amount) as total
    FROM sales
    WHERE DATE(sale_date) BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY date
");
$sales_data_query->bind_param("ss", $start_date, $end_date);
$sales_data_query->execute();
$sales_result = $sales_data_query->get_result();

$sales_labels = [];
$sales_points = [];
$total_revenue = 0;
while ($row = $sales_result->fetch_assoc()) {
    $sales_labels[] = date('M d', strtotime($row['date']));
    $sales_points[] = $row['total'];
    $total_revenue += $row['total'];
}

// 2. Best-Selling Products Data
$best_selling_query = $conn->prepare("
    SELECT p.name, SUM(si.quantity) as total_quantity
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    JOIN sales s ON si.sale_id = s.id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY p.name
    ORDER BY total_quantity DESC
    LIMIT 5
");
$best_selling_query->bind_param("ss", $start_date, $end_date);
$best_selling_query->execute();
$best_selling_result = $best_selling_query->get_result();

$best_labels = [];
$best_data = [];
while ($row = $best_selling_result->fetch_assoc()) {
    $best_labels[] = $row['name'];
    $best_data[] = $row['total_quantity'];
}

// 3. Category Distribution
$cat_query = $conn->query("
    SELECT c.name, COUNT(p.id) as count 
    FROM categories c 
    JOIN products p ON c.id = p.category_id 
    GROUP BY c.name
");
$cat_labels = [];
$cat_counts = [];
while($row = $cat_query->fetch_assoc()) {
    $cat_labels[] = $row['name'];
    $cat_counts[] = $row['count'];
}

?>

<div class="header-actions">
    <h1>Analytics & Reports</h1>
    <div class="filter-card">
        <form action="reports.php" method="post" class="filter-form" autocomplete="off">
            <div class="filter-group">
                <label>From</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>" class="form-control">
            </div>
            <div class="filter-group">
                <label>To</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Update Report</button>
        </form>
    </div>
</div>

<div class="report-summary-cards">
    <div class="summary-card">
        <div class="summary-label">Total Revenue</div>
        <div class="summary-value">TSh <?php echo number_format($total_revenue, 0); ?></div>
        <div class="summary-period"><?php echo date('M d', strtotime($start_date)) . ' - ' . date('M d', strtotime($end_date)); ?></div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Sales Count</div>
        <div class="summary-value">
            <?php 
                $count_q = $conn->prepare("SELECT COUNT(*) as count FROM sales WHERE DATE(sale_date) BETWEEN ? AND ?");
                $count_q->bind_param("ss", $start_date, $end_date);
                $count_q->execute();
                $sales_count_row = $count_q->get_result()->fetch_assoc();
                $sales_count = (int)($sales_count_row['count'] ?? 0);
                echo $sales_count;
            ?>
        </div>
        <div class="summary-period">Total transactions</div>
    </div>
    <div class="summary-card">
        <div class="summary-label">Avg. Transaction</div>
        <div class="summary-value">
            $<?php 
                $avg = ($sales_count > 0) ? ($total_revenue / $sales_count) : 0;
                echo number_format($avg, 2);
            ?>
        </div>
        <div class="summary-period">Revenue per sale</div>
    </div>
</div>

<div class="charts-grid">
    <div class="chart-container">
        <h2>Revenue Trend</h2>
        <canvas id="revenueChart"></canvas>
    </div>
    <div class="chart-container">
        <h2>Best Selling Products</h2>
        <canvas id="bestSellerChart"></canvas>
    </div>
    <div class="chart-container">
        <h2>Inventory by Category</h2>
        <canvas id="categoryChart"></canvas>
    </div>
    <div class="chart-container">
        <h2>Revenue by Payment Method</h2>
        <canvas id="paymentMethodChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue by Payment Method
    new Chart(document.getElementById('paymentMethodChart'), {
        type: 'pie',
        data: {
            labels: ['Cash', 'Mobile Money Wallet', 'Bank'],
            datasets: [{
                data: [
                    <?php 
                        $cash_q = $conn->prepare("SELECT SUM(total_amount) as total FROM sales WHERE payment_method = 'Cash' AND DATE(sale_date) BETWEEN ? AND ?");
                        $cash_q->bind_param("ss", $start_date, $end_date);
                        $cash_q->execute();
                        echo (float)($cash_q->get_result()->fetch_assoc()['total'] ?? 0);

                        echo ",";

                        $wallet_q = $conn->prepare("SELECT SUM(total_amount) as total FROM sales WHERE payment_method = 'Mobile Money Wallet' AND DATE(sale_date) BETWEEN ? AND ?");
                        $wallet_q->bind_param("ss", $start_date, $end_date);
                        $wallet_q->execute();
                        echo (float)($wallet_q->get_result()->fetch_assoc()['total'] ?? 0);

                        echo ",";

                        $bank_q = $conn->prepare("SELECT SUM(total_amount) as total FROM sales WHERE payment_method = 'Bank' AND DATE(sale_date) BETWEEN ? AND ?");
                        $bank_q->bind_param("ss", $start_date, $end_date);
                        $bank_q->execute();
                        echo (float)($bank_q->get_result()->fetch_assoc()['total'] ?? 0);
                    ?>
                ],
                backgroundColor: ['#10b981', '#6366f1', '#f59e0b']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } }
        }
    });

    // Revenue Chart
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($sales_labels); ?>,
            datasets: [{
                label: 'Revenue',
                data: <?php echo json_encode($sales_points); ?>,
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.05)',
                fill: true,
                tension: 0.4,
                pointRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, grid: { borderDash: [5, 5] } }, x: { grid: { display: false } } }
        }
    });

    // Best Sellers Chart
    new Chart(document.getElementById('bestSellerChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($best_labels); ?>,
            datasets: [{
                label: 'Units Sold',
                data: <?php echo json_encode($best_data); ?>,
                backgroundColor: '#10b981',
                borderRadius: 6
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } }
        }
    });

    // Category Chart
    new Chart(document.getElementById('categoryChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($cat_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($cat_counts); ?>,
                backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#6366f1']
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
});
</script>

<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
    .filter-card { background: white; padding: 12px 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .filter-form { display: flex; align-items: flex-end; gap: 16px; }
    .filter-group label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; color: #94a3b8; margin-bottom: 4px; }
    .filter-form .form-control { width: 150px; padding: 8px; }
    
    .report-summary-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
    .summary-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-left: 4px solid #007bff; }
    .summary-label { font-size: 14px; color: #64748b; font-weight: 500; }
    .summary-value { font-size: 28px; font-weight: 800; color: #1e293b; margin: 8px 0; }
    .summary-period { font-size: 12px; color: #94a3b8; }
    
    .charts-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 24px; }
    .chart-container { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; }
    .chart-container:first-child { grid-column: span 2; }
    .chart-container h2 { font-size: 16px; margin-bottom: 20px; color: #1e293b; }

    @media (max-width: 992px) {
        .charts-grid { grid-template-columns: 1fr; }
        .chart-container:first-child { grid-column: span 1; }
        .report-summary-cards { grid-template-columns: 1fr; }
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>