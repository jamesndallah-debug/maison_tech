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

$message = '';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// Role-based access control (Admin and Chairman only)
if (!in_array($_SESSION['role'], ['admin', 'chairman'])) {
    include 'header.php';
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to view this page.</div>";
    include 'footer.php';
    exit;
}

// Handle Add Salary Payment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $employee_id = (int)$_POST['employee_id'];
    $amount = (float)$_POST['amount'];
    $payment_date = $_POST['payment_date'];
    $payment_method = $_POST['payment_method'];
    $notes = trim($_POST['notes']);
    $processed_by = (int)$_SESSION['user_id'];

    if ($employee_id > 0 && $amount > 0 && !empty($payment_date) && !empty($payment_method)) {
        $stmt = $conn->prepare("INSERT INTO salary_payments (employee_id, amount, payment_date, payment_method, notes, processed_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("idsssi", $employee_id, $amount, $payment_date, $payment_method, $notes, $processed_by);
        
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Salary payment recorded successfully.</div>";
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Error recording payment: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Please fill in all required fields.</div>";
    }
    header("Location: salary_management.php");
    exit;
}

// Handle Delete Salary Payment (Admin Only)
if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Access Denied. Only admins can delete salary records.</div>";
    } else {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $conn->prepare("DELETE FROM salary_payments WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Salary record deleted successfully.</div>";
            // Log action
            $action = "Deleted salary payment record ID: $delete_id";
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $_SESSION['user_id'], $action);
            $log->execute();
            $log->close();
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Error deleting record: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    header("Location: salary_management.php");
    exit;
}

include 'header.php';

// Filter Logic
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filter_year = isset($_GET['year']) ? (int)$_GET['year'] : 0;
$filter_employee_id = isset($_GET['filter_employee_id']) ? (int)$_GET['filter_employee_id'] : 0;

$where_clauses = [];
$params = [];
$types = "";

if ($filter_month > 0 && $filter_month <= 12) {
    $where_clauses[] = "MONTH(sp.payment_date) = ?";
    $params[] = $filter_month;
    $types .= "i";
}
if ($filter_year > 0) {
    $where_clauses[] = "YEAR(sp.payment_date) = ?";
    $params[] = $filter_year;
    $types .= "i";
}
if ($filter_employee_id > 0) {
    $where_clauses[] = "sp.employee_id = ?";
    $params[] = $filter_employee_id;
    $types .= "i";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

// Fetch employees for dropdown (reusable array)
$employees_res = $conn->query("SELECT id, username, role FROM employees ORDER BY username ASC");
$employees_list = [];
while($emp = $employees_res->fetch_assoc()) {
    $employees_list[] = $emp;
}

// Fetch payment history with filters
$query = "
    SELECT sp.*, e.username as employee_name, p.username as processor_name
    FROM salary_payments sp
    JOIN employees e ON sp.employee_id = e.id
    JOIN employees p ON sp.processed_by = p.id
    $where_sql
    ORDER BY sp.payment_date DESC, sp.created_at DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();

?>

<div class="header-actions">
    <h1>Salary Management</h1>
    <p class="text-muted">Process and record salary payments for all staff members.</p>
</div>

<?php echo $message; ?>

<div class="row">
    <!-- Payment Form -->
    <div class="col-md-4">
        <div class="card form-card">
            <div class="card-header"><h2><i class="fas fa-money-bill-wave"></i> Process New Payment</h2></div>
            <div class="card-body">
                <form action="salary_management.php" method="post" autocomplete="off">
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" class="form-control" required>
                            <option value="">Select Employee</option>
                            <?php foreach($employees_list as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>">
                                    <?php echo htmlspecialchars($emp['username']); ?> (<?php echo ucfirst($emp['role']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="Enter amount" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Date</label>
                        <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" class="form-control" required>
                            <option value="Bank Transfer">Bank Transfer</option>
                            <option value="Cash">Cash</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="e.g., Monthly salary, bonus, etc."></textarea>
                    </div>
                    <button type="submit" name="add_payment" class="btn btn-primary btn-block">Record Payment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <div class="col-md-8">
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="salary_management.php" class="form-inline">
                    <div class="row w-100">
                        <div class="col-md-3">
                            <label class="mr-2">Employee</label>
                            <select name="filter_employee_id" class="form-control w-100">
                                <option value="">All Employees</option>
                                <?php foreach($employees_list as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo ($filter_employee_id == $emp['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mr-2">Month</label>
                            <select name="month" class="form-control w-100">
                                <option value="">All Months</option>
                                <?php
                                $months = [
                                    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                                    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                                    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                                ];
                                foreach ($months as $m => $name):
                                    $selected = ($filter_month == $m) ? 'selected' : '';
                                    echo "<option value='$m' $selected>$name</option>";
                                endforeach;
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="mr-2">Year</label>
                            <select name="year" class="form-control w-100">
                                <option value="">All Years</option>
                                <?php
                                $current_year = date('Y');
                                for ($y = $current_year; $y >= $current_year - 5; $y--):
                                    $selected = ($filter_year == $y) ? 'selected' : '';
                                    echo "<option value='$y' $selected>$y</option>";
                                endfor;
                                ?>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 mr-2"><i class="fas fa-filter"></i> Filter</button>
                            <a href="salary_management.php" class="btn btn-secondary w-100"><i class="fas fa-undo"></i> Reset</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card table-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-history"></i> Payment History</h2>
                <a href="export_salaries.php" class="btn btn-success btn-sm"><i class="fas fa-file-csv"></i> Export CSV</a>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th class="text-right">Amount</th>
                            <th>Payment Date</th>
                            <th>Method</th>
                            <th>Processed By</th>
                            <th>Notes</th>
                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                <th>Actions</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($payments->num_rows > 0): ?>
                            <?php while($row = $payments->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($row['employee_name']); ?></strong></td>
                                <td class="text-right font-bold"><?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['payment_date'])); ?></td>
                                <td><span class="badge"><?php echo htmlspecialchars($row['payment_method']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['processor_name']); ?></td>
                                <td><small class="text-muted"><?php echo htmlspecialchars($row['notes']); ?></small></td>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <td>
                                        <a href="salary_management.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this record?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                <?php endif; ?>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="<?php echo ($_SESSION['role'] === 'admin') ? '7' : '6'; ?>" class="text-center" style="padding: 40px;">No salary payments recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .font-bold { font-weight: 700; }
    .text-right { text-align: right; }
    .badge {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
</style>

<?php include 'footer.php'; ?>
