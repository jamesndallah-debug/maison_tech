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

// Role-based access control (Admin only)
if ($_SESSION['role'] !== 'admin') {
    header("Location: dashboard.php?err=Access+Denied");
    exit;
}

$message = '';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// Handle Add Expense Category
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim((string)($_POST['name'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    
    if (empty($name)) {
        $message = "<div class='alert alert-danger'>Category name is required.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO expense_categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Expense category added successfully!</div>";
            // Log action
            $user_id = $_SESSION['user_id'];
            $action = "Created new expense category: " . htmlspecialchars($name);
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $user_id, $action);
            $log->execute();
            $log->close();
        } else {
            $message = "<div class='alert alert-danger'>Error adding category: " . $stmt->error . "</div>";
        }
        $stmt->close();
        header("Location: expense_categories.php");
        exit;
    }
}

// Handle Delete Expense Category
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    
    // Check if category is being used in expenses
    $check = $conn->prepare("SELECT COUNT(*) as count FROM expenses WHERE expense_category_id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $count = $check->get_result()->fetch_assoc()['count'];
    $check->close();
    
    if ($count > 0) {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Cannot delete category. It is being used in expenses.</div>";
    } else {
        // Get category name for logging
        $cat_stmt = $conn->prepare("SELECT name FROM expense_categories WHERE id = ?");
        $cat_stmt->bind_param("i", $id);
        $cat_stmt->execute();
        $cat_name = $cat_stmt->get_result()->fetch_assoc()['name'];
        $cat_stmt->close();
        
        $stmt = $conn->prepare("DELETE FROM expense_categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Expense category deleted successfully!</div>";
            // Log action
            $user_id = $_SESSION['user_id'];
            $action = "Deleted expense category: " . htmlspecialchars($cat_name);
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $user_id, $action);
            $log->execute();
            $log->close();
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Error deleting category: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    header("Location: expense_categories.php");
    exit;
}

// Fetch all categories
$categories = $conn->query("SELECT * FROM expense_categories ORDER BY name ASC");

include 'header.php';
?>

<div class="page-header">
    <h1>Expense Categories Management</h1>
    <p class="text-muted">Manage expense categories for company expenses tracking</p>
</div>

<?php echo $message; ?>

<div class="expense-categories-grid">
    <div class="left-col">
        <div class="form-card">
            <h2>Add New Category</h2>
            <form action="expense_categories.php" method="post" autocomplete="off">
                <div class="form-group">
                    <label>Category Name *</label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g., Office Supplies">
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description of this expense category"></textarea>
                </div>
                <button type="submit" name="add_category" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Category
                </button>
            </form>
        </div>
    </div>
    
    <div class="right-col">
        <div class="table-card">
            <div class="card-header">
                <h2>Existing Categories</h2>
                <span class="record-count"><?php echo $categories->num_rows; ?> categories</span>
            </div>
            <div class="table-container">
                <table class="categories-table">
                    <thead>
                        <tr>
                            <th>Category Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($categories->num_rows > 0): ?>
                            <?php while($row = $categories->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="category-name">
                                        <i class="fas fa-tag"></i>
                                        <strong><?php echo htmlspecialchars($row['name']); ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span class="category-desc"><?php echo htmlspecialchars($row['description'] ?? 'No description'); ?></span>
                                </td>
                                <td>
                                    <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4">No expense categories found.</td>
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
            <p>Are you sure you want to delete the expense category "<span id="categoryName"></span>"?</p>
            <p class="text-danger">This action cannot be undone.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
            <button class="btn btn-danger" id="confirmDeleteBtn">Delete Category</button>
        </div>
    </div>
</div>

<script>
function confirmDelete(id, name) {
    document.getElementById('categoryName').textContent = name;
    document.getElementById('confirmDeleteBtn').onclick = function() {
        window.location.href = 'expense_categories.php?delete_id=' + id;
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
.expense-categories-grid {
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

.categories-table th {
    background: #f8fafc;
    color: #475569;
    font-weight: 600;
    font-size: 14px;
    text-align: left;
    padding: 12px;
}

.category-name {
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-name i {
    color: #3b82f6;
    font-size: 14px;
}

.category-desc {
    color: #64748b;
    font-size: 14px;
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
    .expense-categories-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'footer.php'; ?>
