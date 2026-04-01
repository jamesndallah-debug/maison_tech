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

// Handle Add Expense
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_expense'])) {
    $category_id = (int)($_POST['expense_category_id'] ?? 0);
    $description = trim((string)($_POST['description'] ?? ''));
    $amount = (float)($_POST['amount'] ?? 0);
    $expense_date = $_POST['expense_date'] ?? date('Y-m-d');
    $notes = trim((string)($_POST['notes'] ?? ''));
    $recorded_by = $_SESSION['user_id'];
    
    if ($category_id <= 0) {
        $message = "<div class='alert alert-danger'>Please select an expense category.</div>";
    } elseif (empty($description)) {
        $message = "<div class='alert alert-danger'>Description is required.</div>";
    } elseif ($amount <= 0) {
        $message = "<div class='alert alert-danger'>Amount must be greater than 0.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO expenses (expense_category_id, description, amount, expense_date, recorded_by, notes) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isdsis", $category_id, $description, $amount, $expense_date, $recorded_by, $notes);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Expense recorded successfully!</div>";
            // Log action
            $action = "Recorded expense: " . htmlspecialchars($description) . " - TSh " . number_format($amount, 0);
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $recorded_by, $action);
            $log->execute();
            $log->close();
        } else {
            $message = "<div class='alert alert-danger'>Error recording expense: " . $stmt->error . "</div>";
        }
        $stmt->close();
        header("Location: expenses.php");
        exit;
    }
}

// Handle Delete Expense (Admin Only)
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    // Check if user is admin
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Access denied. Only admin can delete expenses.</div>";
    } else {
        $expense = $conn->prepare("SELECT * FROM expenses WHERE id = ?");
        $expense->bind_param("i", $id);
        $expense->execute();
        $expense_data = $expense->get_result()->fetch_assoc();
        $expense->close();
        
        if (!$expense_data) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Expense not found.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $_SESSION['msg'] = "<div class='alert alert-success'>Expense deleted successfully!</div>";
                // Log action
                $action = "Deleted expense: " . htmlspecialchars($expense_data['description']);
                $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                $log->bind_param("is", $_SESSION['user_id'], $action);
                $log->execute();
                $log->close();
            } else {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Error deleting expense: " . $stmt->error . "</div>";
            }
            $stmt->close();
        }
    }
    header("Location: expenses.php");
    exit;
}

// Fetch expense categories
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY name ASC");

// Fetch recent expenses (with filtering)
$where_clause = "";
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    $where_clause = "WHERE e.recorded_by = " . $_SESSION['user_id'];
}

$filter_category = $_GET['filter_category'] ?? '';
$filter_date = $_GET['filter_date'] ?? '';

if (!empty($filter_category)) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ") . "e.expense_category_id = " . (int)$filter_category;
}

if (!empty($filter_date)) {
    $where_clause .= ($where_clause ? " AND " : "WHERE ") . "e.expense_date = '" . $conn->real_escape_string($filter_date) . "'";
}

$expenses = $conn->query("
    SELECT e.*, ec.name as category_name, emp.username as recorded_by_name
    FROM expenses e
    JOIN expense_categories ec ON e.expense_category_id = ec.id
    JOIN employees emp ON e.recorded_by = emp.id
    $where_clause
    ORDER BY e.expense_date DESC, e.created_at DESC
");

include 'header.php';
?>

<div class="page-header">
    <h1>Company Expenses</h1>
    <p class="text-muted">Record and track company expenses</p>
</div>

<?php echo $message; ?>

<div class="expenses-grid">
    <div class="left-col">
        <div class="form-card">
            <h2>Record New Expense</h2>
            <form action="expenses.php" method="post" autocomplete="off">
                <div class="form-group">
                    <label>Expense Category *</label>
                    <select name="expense_category_id" class="form-control" required>
                        <option value="">Select category</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Description *</label>
                    <input type="text" name="description" class="form-control" required placeholder="e.g., Office rent for March">
                </div>
                <div class="form-group">
                    <label>Amount (TSh) *</label>
                    <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                </div>
                <div class="form-group">
                    <label>Expense Date</label>
                    <input type="date" name="expense_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="form-group">
                    <label>Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Additional details about this expense"></textarea>
                </div>
                <button type="submit" name="add_expense" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Record Expense
                </button>
            </form>
        </div>
    </div>
    
    <div class="right-col">
        <div class="table-card">
            <div class="card-header">
                <h2>Recent Expenses</h2>
                <span class="record-count"><?php echo $expenses->num_rows; ?> expenses</span>
            </div>
            
            <!-- Filters -->
            <div class="filters-section">
                <form method="get" class="filter-form">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Category</label>
                            <select name="filter_category" class="form-control">
                                <option value="">All Categories</option>
                                <?php 
                                $categories->data_seek(0);
                                while($cat = $categories->fetch_assoc()): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ($filter_category == $cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Date</label>
                            <input type="date" name="filter_date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
                        </div>
                        <div class="filter-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-secondary">Filter</button>
                            <a href="expenses.php" class="btn btn-outline">Clear</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="table-container">
                <table class="expenses-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Recorded By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($expenses->num_rows > 0): ?>
                            <?php while($row = $expenses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($row['expense_date'])); ?></td>
                                <td>
                                    <span class="category-badge"><?php echo htmlspecialchars($row['category_name']); ?></span>
                                </td>
                                <td>
                                    <div class="expense-desc">
                                        <strong><?php echo htmlspecialchars($row['description']); ?></strong>
                                        <?php if (!empty($row['notes'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($row['notes']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="amount-cell">
                                    <strong>TSh <?php echo number_format($row['amount'], 0); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($row['recorded_by_name']); ?></td>
                                <td>
                                    <?php if ($_SESSION['role'] === 'admin'): ?>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['description']); ?>')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">No expenses found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirm Delete</h3>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <p>Are you sure you want to delete the expense "<span id="expenseName"></span>"?</p>
            <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete Expense</button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('expenseName').textContent = name;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'expenses.php?delete_id=' + id;
    };
    document.getElementById('deleteModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.expenses-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 24px;
    margin-top: 20px;
}

.form-card, .table-card {
    background: #fff;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.card-header h2 {
    margin: 0;
    font-size: 18px;
    color: #0f172a;
}

.record-count {
    background: #f1f5f9;
    color: #64748b;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.filters-section {
    margin-bottom: 20px;
    padding: 16px;
    background: #f8fafc;
    border-radius: 8px;
}

.filter-form {
    margin: 0;
}

.filter-row {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 12px;
    align-items: end;
}

.filter-group {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.filter-group label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}

.expenses-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 14px;
    text-align: left;
    padding: 12px;
}

.category-badge {
    background: #e0f2fe;
    color: #0369a1;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.expense-desc {
    max-width: 200px;
}

.amount-cell {
    color: #dc2626;
    font-weight: 600;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: #fff;
    margin: 15% auto;
    padding: 0;
    border-radius: 8px;
    width: 400px;
    max-width: 90%;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h3 {
    margin: 0;
    color: #0f172a;
}

.close {
    color: #6b7280;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover {
    color: #374151;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e5e7eb;
    display: flex;
    gap: 12px;
    justify-content: flex-end;
}

@media (max-width: 768px) {
    .expenses-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>