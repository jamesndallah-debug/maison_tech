<?php
include 'dp.php';
include 'header.php';

// Access Control: Admins and Chairmen only
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'chairman'])) {
    echo "<div class='alert alert-danger'>Access Denied. Admins and Chairmen only.</div>";
    include 'footer.php';
    exit;
}

$message = '';

// Handle Company IP Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ip'])) {
    $company_ip = trim($_POST['company_ip'] ?? '');

    if ($company_ip !== '' && !filter_var($company_ip, FILTER_VALIDATE_IP)) {
        $message = "<div class='alert alert-danger'>Invalid IP address format.</div>";
    } else {
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'company_ip'");
        $stmt->bind_param("s", $company_ip);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Company IP address updated successfully.</div>";
        } else {
            $message = "<div class='alert alert-danger'>Failed to update IP address.</div>";
        }
        $stmt->close();
    }
}

// Fetch current IP setting
$settings_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'company_ip'");
$company_ip = $settings_res->fetch_assoc()['setting_value'] ?? '';

// Handle CSV Export
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $filename = "attendance_report_" . date('Y-m-d') . ".csv";
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Employee Name', 'Date', 'Sign In Time', 'Sign Out Time', 'IP Address']);

    $where_clauses = [];
    $params = [];
    $types = "";

    if (!empty($_GET['employee_id'])) {
        $where_clauses[] = "a.employee_id = ?";
        $params[] = (int)$_GET['employee_id'];
        $types .= "i";
    }
    if (!empty($_GET['date_from'])) {
        $where_clauses[] = "a.date >= ?";
        $params[] = $_GET['date_from'];
        $types .= "s";
    }
    if (!empty($_GET['date_to'])) {
        $where_clauses[] = "a.date <= ?";
        $params[] = $_GET['date_to'];
        $types .= "s";
    }

    $sql = "SELECT e.username, a.date, a.sign_in_time, a.sign_out_time, a.ip_address 
            FROM attendance a 
            JOIN employees e ON a.employee_id = e.id";

    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql .= " ORDER BY a.date DESC, a.sign_in_time DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['username'],
            $row['date'],
            $row['sign_in_time'],
            $row['sign_out_time'] ?? 'Not Signed Out',
            $row['ip_address']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch Records for View
$employee_id = (int)($_GET['employee_id'] ?? 0);
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where_clauses = [];
$params = [];
$types = "";

if ($employee_id > 0) {
    $where_clauses[] = "a.employee_id = ?";
    $params[] = $employee_id;
    $types .= "i";
}
if ($date_from !== '') {
    $where_clauses[] = "a.date >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to !== '') {
    $where_clauses[] = "a.date <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$sql = "SELECT a.*, e.username 
        FROM attendance a 
        JOIN employees e ON a.employee_id = e.id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY a.date DESC, a.sign_in_time DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$attendance_records = $stmt->get_result();

// Fetch Current Stats for Today
$today = date('Y-m-d');
$stats_res = $conn->query("SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN sign_out_time IS NULL THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN sign_out_time IS NOT NULL THEN 1 ELSE 0 END) as completed
    FROM attendance WHERE date = '$today'");
$stats = $stats_res->fetch_assoc();

// Fetch employees for dropdown
$employees = $conn->query("SELECT id, username FROM employees ORDER BY username ASC");
?>

<div class="header-actions">
    <div>
        <h1>Attendance Management</h1>
        <p class="text-muted">
            <?php if ($_SESSION['role'] === 'admin'): ?>
                Monitor real-time status, export records, and manage office location settings.
            <?php else: ?>
                View and filter employee attendance history.
            <?php endif; ?>
        </p>
    </div>
    <?php if (in_array($_SESSION['role'], ['admin', 'chairman'])): ?>
    <div class="actions">
        <a href="manage_attendance.php?export=csv&employee_id=<?php echo $employee_id; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>" class="btn btn-primary">
            <i class="fas fa-file-export"></i> Export Records (CSV)
        </a>
    </div>
    <?php endif; ?>
</div>

<?php echo $message; ?>

<?php if ($_SESSION['role'] === 'admin'): ?>
<div class="stats-row mb-4">
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-users"></i></div>
        <div class="stat-details">
            <span class="stat-label">Total Today</span>
            <span class="stat-value"><?php echo $stats['total'] ?? 0; ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="stat-details">
            <span class="stat-label">Currently Active</span>
            <span class="stat-value"><?php echo $stats['active'] ?? 0; ?></span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-details">
            <span class="stat-label">Completed Today</span>
            <span class="stat-value"><?php echo $stats['completed'] ?? 0; ?></span>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="settings-grid" <?php if ($_SESSION['role'] !== 'admin') echo 'style="grid-template-columns: 1fr;"'; ?>>
    <?php if ($_SESSION['role'] === 'admin'): ?>
    <div class="card">
        <h2><i class="fas fa-network-wired"></i> Office Location Setting</h2>
        <p class="text-muted" style="font-size: 13px;">Set the official IP address of your office. Employees will only be able to sign in/out when connected to this network.</p>
        <div class="alert alert-info" style="font-size: 12px; margin-bottom: 15px;">
            <i class="fas fa-info-circle"></i> <strong>Your current IP:</strong> <?php echo $_SERVER['REMOTE_ADDR']; ?><br>
            (Use this if you are currently at the office location)
        </div>
        <form action="manage_attendance.php" method="post" class="form">
            <div class="form-group">
                <label for="company_ip">Official Office IP</label>
                <input type="text" id="company_ip" name="company_ip" class="form-control" value="<?php echo htmlspecialchars($company_ip); ?>" placeholder="e.g., 192.168.1.100">
            </div>
            <button type="submit" name="update_ip" class="btn btn-primary w-100">Update Office Location</button>
        </form>
    </div>
    <?php endif; ?>

    <div class="card">
        <h2><i class="fas fa-filter"></i> Filter Records</h2>
        <form method="GET" class="form">
            <div class="form-row">
                <div class="form-group">
                    <label>Employee</label>
                    <select name="employee_id" class="form-control">
                        <option value="">All Employees</option>
                        <?php while($e = $employees->fetch_assoc()): ?>
                            <option value="<?php echo $e['id']; ?>" <?php echo $employee_id == $e['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($e['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                </div>
                <div class="form-group">
                    <label>To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                </div>
            </div>
            <div style="display: flex; gap: 10px; margin-top: 10px;">
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="manage_attendance.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card mt-4">
    <h2><i class="fas fa-list-alt"></i> Attendance History</h2>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Sign In</th>
                    <th>Sign Out</th>
                    <th>IP Address</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance_records->num_rows > 0): ?>
                    <?php while($row = $attendance_records->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['username']); ?></strong></td>
                            <td><?php echo date("M d, Y", strtotime($row['date'])); ?></td>
                            <td><?php echo date("h:i A", strtotime($row['sign_in_time'])); ?></td>
                            <td>
                                <?php if ($row['sign_out_time']): ?>
                                    <?php echo date("h:i A", strtotime($row['sign_out_time'])); ?>
                                <?php else: ?>
                                    <span class="badge badge-warning">Active</span>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo htmlspecialchars($row['ip_address']); ?></code></td>
                            <td>
                                <?php if ($row['sign_out_time']): ?>
                                    <span class="badge badge-success">Completed</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Working</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">No attendance records found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; }
    .stats-row { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 24px; }
    .stat-card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 15px; }
    .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    .stat-icon.info { background: #e0f2fe; color: #0369a1; }
    .stat-icon.warning { background: #fef9c3; color: #854d0e; }
    .stat-icon.success { background: #dcfce7; color: #166534; }
    .stat-label { display: block; font-size: 13px; color: #64748b; font-weight: 500; }
    .stat-value { display: block; font-size: 24px; font-weight: 700; color: #1e293b; }
    .settings-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 24px; }
    .card { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 24px; }
    .card h2 { margin: 0 0 16px; font-size: 16px; color: #1e293b; display: flex; align-items: center; gap: 10px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
    .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-success { background: #dcfce7; color: #166534; }
    .badge-warning { background: #fef9c3; color: #854d0e; }
    .badge-info { background: #e0f2fe; color: #0369a1; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .btn-secondary:hover { background: #e2e8f0; }
    code { background: #f1f5f9; padding: 2px 4px; border-radius: 4px; font-size: 12px; }

    @media (max-width: 992px) {
        .settings-grid { grid-template-columns: 1fr; }
        .form-row { grid-template-columns: 1fr; }
    }
</style>

<?php include 'footer.php'; ?>
