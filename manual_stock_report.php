<?php
include 'header.php';
include 'dp.php';

// Role-based access control
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'chairman', 'manager'])) {
    header("Location: dashboard.php?err=Access+Denied");
    exit;
}

// Date range filtering
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch data for the report
$query = "
    SELECT 
        DATE(movement_date) as date,
        movement_type,
        COUNT(*) as adjustment_count,
        SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as stock_in,
        SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as stock_out
    FROM stock_movements
    WHERE DATE(movement_date) BETWEEN ? AND ?
    AND movement_type NOT IN ('Sale', 'New Stock')
    GROUP BY DATE(movement_date), movement_type
    ORDER BY date, movement_type
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

$report_data = [];
$chart_labels = [];
$chart_in_data = [];
$chart_out_data = [];

$period_data = [];
while ($row = $result->fetch_assoc()) {
    $report_data[] = $row;
    $date = date('M d', strtotime($row['date']));
    if (!isset($period_data[$date])) {
        $period_data[$date] = ['in' => 0, 'out' => 0];
    }
    $period_data[$date]['in'] += $row['stock_in'];
    $period_data[$date]['out'] += $row['stock_out'];
}

foreach ($period_data as $date => $values) {
    $chart_labels[] = $date;
    $chart_in_data[] = $values['in'];
    $chart_out_data[] = $values['out'];
}

$total_adjustments = count($report_data);
$total_stock_in = array_sum(array_column($report_data, 'stock_in'));
$total_stock_out = array_sum(array_column($report_data, 'stock_out'));

?>

<div class="header-actions">
    <div class="header-left">
        <h1><i class="fas fa-chart-bar text-primary"></i> Manual Stock Adjustment Report</h1>
        <p class="text-muted">Monitor trends in manual stock adjustments to ensure inventory accuracy.</p>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body" style="padding: 24px;">
        <form method="GET" action="manual_stock_report.php" autocomplete="off">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4">
                    <label for="start_date"><i class="far fa-calendar-alt"></i> Start Date</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>">
                </div>
                <div class="form-group col-md-4">
                    <label for="end_date"><i class="far fa-calendar-alt"></i> End Date</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>">
                </div>
                <div class="form-group col-md-2">
                    <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter"></i> Filter</button>
                </div>
                 <div class="form-group col-md-2">
                    <a href="manual_stock_report.php" class="btn btn-secondary btn-block"><i class="fas fa-undo"></i> Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Summary Cards -->
<div class="dashboard-cards">
    <div class="card card-stats">
        <div class="card-icon icon-blue"><i class="fas fa-history"></i></div>
        <div class="card-info">
            <div class="card-title">Total Adjustments</div>
            <div class="card-value"><?php echo $total_adjustments; ?></div>
            <div class="card-sub">In selected period</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon icon-green"><i class="fas fa-arrow-up"></i></div>
        <div class="card-info">
            <div class="card-title">Total Stock In</div>
            <div class="card-value text-success">+<?php echo number_format($total_stock_in); ?></div>
            <div class="card-sub">Items added manually</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon icon-orange"><i class="fas fa-arrow-down"></i></div>
        <div class="card-info">
            <div class="card-title">Total Stock Out</div>
            <div class="card-value text-danger">-<?php echo number_format($total_stock_out); ?></div>
            <div class="card-sub">Items removed manually</div>
        </div>
    </div>
</div>

<!-- Chart -->
<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-chart-line"></i> Adjustment Trends (Stock In vs. Stock Out)</h2>
        <span class="text-muted"><i class="fas fa-info-circle"></i> Daily aggregation of manual changes</span>
    </div>
    <div class="card-body" style="padding: 24px;">
        <canvas id="adjustmentChart" height="80"></canvas>
    </div>
</div>

<!-- Data Table -->
<div class="card mt-4 table-card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Adjustment Breakdown</h2>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Adjustment Type</th>
                    <th class="text-center">Action Count</th>
                    <th class="text-right">Stock In (+)</th>
                    <th class="text-right">Stock Out (-)</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($report_data)): ?>
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 60px;">
                            <i class="fas fa-search fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No manual adjustments found in the selected period.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($report_data as $row): ?>
                    <tr>
                        <td><strong><?php echo date('M d, Y', strtotime($row['date'])); ?></strong></td>
                        <td>
                            <span class="type-badge type-<?php echo strtolower(str_replace(' ', '-', $row['movement_type'])); ?>">
                                <?php echo htmlspecialchars($row['movement_type']); ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $row['adjustment_count']; ?></td>
                        <td class="text-right text-success font-bold">
                            <?php echo $row['stock_in'] > 0 ? '+' . number_format($row['stock_in']) : '-'; ?>
                        </td>
                        <td class="text-right text-danger font-bold">
                            <?php echo $row['stock_out'] > 0 ? '-' . number_format($row['stock_out']) : '-'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('adjustmentChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [
                    {
                        label: 'Stock In',
                        data: <?php echo json_encode($chart_in_data); ?>,
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Stock Out',
                        data: <?php echo json_encode($chart_out_data); ?>,
                        borderColor: '#f97316',
                        backgroundColor: 'rgba(249, 115, 22, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
</script>

<style>
    .text-success { color: #10b981 !important; }
    .text-danger { color: #ef4444 !important; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .font-bold { font-weight: 700; }
    
    .card-header { padding: 16px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .card-header h2 { margin: 0; font-size: 18px; color: #334155; display: flex; align-items: center; gap: 10px; }
    
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
    .icon-orange { background: #fff7ed; color: #f97316; }
    
    .card-value { font-size: 24px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; }
    .card-sub { font-size: 13px; color: #64748b; margin-top: 4px; }

    .type-badge {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .type-restock, .type-return { background-color: #eff6ff; color: #2563eb; }
    .type-damage, .type-usage { background-color: #fff7ed; color: #ea580c; }
    .type-manual-correction, .type-other { background-color: #f1f5f9; color: #475569; }

    .table-card { border-radius: 12px; overflow: hidden; }
    .table-container { margin-top: 0; }
    table thead th { background: #f8fafc; padding: 16px 20px; font-weight: 700; color: #64748b; font-size: 12px; }
    table tbody td { padding: 16px 20px; vertical-align: middle; }
</style>

<?php include 'footer.php'; ?>
