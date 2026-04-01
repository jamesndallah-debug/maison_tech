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

// Role-based filtering: Staff and Manager only see their own receipts
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// Filter parameters
$filter_name = isset($_GET['employee_name']) ? trim($_GET['employee_name']) : '';
$filter_month = isset($_GET['month']) ? (int)$_GET['month'] : 0;
$filter_amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

$where_clauses = [];
$params = [];
$types = "";

// Base filtering by role - Manager and Staff only see their own receipts
if ($role !== 'admin' && $role !== 'chairman') {
    $where_clauses[] = "s.user_id = ?";
    $params[] = $user_id;
    $types .= "i";
} else {
    // Admin/Chairman can filter by employee name
    if (!empty($filter_name)) {
        $where_clauses[] = "e.username LIKE ?";
        $params[] = "%$filter_name%";
        $types .= "s";
    }
}

// Common filters for all roles
if ($filter_month > 0 && $filter_month <= 12) {
    $where_clauses[] = "MONTH(s.sale_date) = ?";
    $params[] = $filter_month;
    $types .= "i";
}

if ($filter_amount > 0) {
    $where_clauses[] = "s.total_amount >= ?";
    $params[] = $filter_amount;
    $types .= "d";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = "WHERE " . implode(" AND ", $where_clauses);
}

$query = "
    SELECT s.id, s.total_amount, s.sale_date, e.username 
    FROM sales s 
    JOIN employees e ON s.user_id = e.id 
    $where_sql
    ORDER BY s.sale_date DESC
";

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$receipts = $stmt->get_result();

?>

<div class="header-actions">
    <h1><?php echo ($role === 'staff' || $role === 'manager') ? 'My Sales Receipts' : 'All Sales Receipts'; ?></h1>
    <p class="text-muted">
        <?php echo ($role === 'staff' || $role === 'manager') ? 'A list of your personal sales records.' : 'Company-wide record of all generated receipts.'; ?>
    </p>
</div>

<!-- Filter Form -->
<div class="filter-card card mb-4">
    <form method="GET" action="receipts.php" class="filter-form" autocomplete="off">
        <div class="form-row">
            <?php if ($role === 'admin' || $role === 'chairman'): ?>
            <div class="form-group col-md-4">
                <label for="employee_name">Employee Name</label>
                <input type="text" name="employee_name" id="employee_name" class="form-control" value="<?php echo htmlspecialchars($filter_name); ?>" placeholder="Search by name...">
            </div>
            <?php endif; ?>

            <div class="form-group col-md-3">
                <label for="month">Month</label>
                <select name="month" id="month" class="form-control">
                    <option value="">All Months</option>
                    <?php
                    $months = [
                        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
                        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
                        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
                    ];
                    foreach ($months as $num => $name):
                        $selected = ($filter_month == $num) ? 'selected' : '';
                        echo "<option value='$num' $selected>$name</option>";
                    endforeach;
                    ?>
                </select>
            </div>

            <div class="form-group col-md-3">
                <label for="amount">Min Amount ($)</label>
                <input type="number" step="0.01" name="amount" id="amount" class="form-control" value="<?php echo $filter_amount > 0 ? htmlspecialchars($filter_amount) : ''; ?>" placeholder="Min amount...">
            </div>

            <div class="form-group col-md-2 align-self-end">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </div>
        <?php if (!empty($filter_name) || $filter_month > 0 || $filter_amount > 0): ?>
            <div class="mt-2">
                <a href="receipts.php" class="btn btn-link btn-sm text-danger">
                    <i class="fas fa-times"></i> Clear Filters
                </a>
            </div>
        <?php endif; ?>
    </form>
</div>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Receipt ID</th>
                <th>Date & Time</th>
                <th>Employee</th>
                <th>Total Amount</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($receipts && $receipts->num_rows > 0): ?>
                <?php while($row = $receipts->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $row['id']; ?></td>
                    <td><?php echo date('M d, Y - H:i', strtotime($row['sale_date'])); ?></td>
                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                    <td><strong>TSh <?php echo number_format($row['total_amount'], 0); ?></strong></td>
                    <td>
                        <a href="receipt.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View
                        </a>
                        <a href="receipt.php?id=<?php echo $row['id']; ?>&autoprint=1" class="btn btn-secondary btn-sm" target="_blank">
                            <i class="fas fa-print"></i> Print
                        </a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px;">No receipts found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<style>
    .btn-sm { padding: 6px 12px; font-size: 13px; margin-right: 5px; }
    .header-actions { margin-bottom: 24px; }
    
    .filter-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 24px;
    }
    .filter-form .form-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: flex-end;
    }
    .filter-form .form-group {
        flex: 1;
        min-width: 200px;
        margin-bottom: 0;
    }
    .filter-form label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 8px;
        text-transform: uppercase;
    }
    .filter-form .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s;
    }
    .filter-form .form-control:focus {
        outline: none;
        border-color: #3b82f6;
    }
    .filter-form .btn-block {
        width: 100%;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    .filter-form .btn-link {
        padding: 0;
        background: none;
        border: none;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .filter-form .btn-link:hover {
        text-decoration: underline;
    }
</style>

<?php include 'footer.php'; ?>
