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

// Chairman is read-only (no manual stock adjustments)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'chairman' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Location: stock_movements.php?err=Access+Denied.+Chairman+is+read-only");
    exit;
}

// Handle Manual Stock Adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['adjust_stock'])) {
    $product_id = $_POST['product_id'];
    $quantity_change = $_POST['quantity_change'];
    $movement_type = $_POST['movement_type'];
    $movement_reason = ($movement_type === 'Other') ? trim($_POST['movement_reason']) : NULL;
    $user_id = $_SESSION['user_id'];

    // Validation for 'Other' reason
    if ($movement_type === 'Other' && empty($movement_reason)) {
        header("Location: stock_movements.php?err=Please+specify+a+reason+for+the+'Other'+adjustment.");
        exit;
    }

    $conn->begin_transaction();
    try {
        // 1. Update product quantity
        $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $stmt->bind_param("ii", $quantity_change, $product_id);
        $stmt->execute();
        $stmt->close();

        // 2. Log stock movement
        $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity_change, movement_type, movement_reason, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iissi", $product_id, $quantity_change, $movement_type, $movement_reason, $user_id);
        $stmt->execute();
        $stmt->close();

        // 3. Log activity
        $p_res = $conn->query("SELECT name FROM products WHERE id = $product_id");
        $p_name = $p_res->fetch_assoc()['name'];
        $reason_text = $movement_type === 'Other' ? $movement_reason : $movement_type;
        $action = "Adjusted stock for $p_name: " . ($quantity_change > 0 ? '+' : '') . $quantity_change . " ($reason_text)";
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $user_id, $action);
        $log->execute();
        $log->close();

        $conn->commit();
        header("Location: stock_movements.php?msg=Stock+adjusted+successfully");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: stock_movements.php?err=" . urlencode($e->getMessage()));
        exit;
    }
}

include 'header.php';

// Fetch products for dropdown
$products_list = $conn->query("SELECT id, name, quantity FROM products ORDER BY name ASC");

// Handle filtering by product_id if passed
$filter_product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$product_name_filter = "";

if ($filter_product_id > 0) {
    $p_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $p_stmt->bind_param("i", $filter_product_id);
    $p_stmt->execute();
    $res = $p_stmt->get_result();
    if ($res->num_rows > 0) {
        $product_name_filter = $res->fetch_assoc()['name'];
    }
    $p_stmt->close();

    $movements_query = "
        SELECT sm.id, p.name AS product_name, sm.quantity_change, sm.movement_type, sm.movement_reason, u.username AS employee_name, sm.movement_date
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN employees u ON sm.user_id = u.id
        WHERE sm.product_id = $filter_product_id
        ORDER BY sm.movement_date DESC
    ";
} else {
    $movements_query = "
        SELECT sm.id, p.name AS product_name, sm.quantity_change, sm.movement_type, sm.movement_reason, u.username AS employee_name, sm.movement_date
        FROM stock_movements sm
        JOIN products p ON sm.product_id = p.id
        JOIN employees u ON sm.user_id = u.id
        ORDER BY sm.movement_date DESC
    ";
}

$movements = $conn->query($movements_query);

// Summary Stats (for current filter)
$stats_query = "
    SELECT 
        SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as total_in,
        SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as total_out
    FROM stock_movements sm
    " . ($filter_product_id > 0 ? "WHERE sm.product_id = $filter_product_id" : "");

$stats = $conn->query($stats_query)->fetch_assoc();
$total_in = $stats['total_in'] ?? 0;
$total_out = $stats['total_out'] ?? 0;
$net_change = $total_in - $total_out;

?>

<div class="header-actions">
    <div class="header-left">
        <h1><i class="fas fa-history text-primary"></i> Stock History <?php echo $product_name_filter ? " - " . htmlspecialchars($product_name_filter) : ""; ?></h1>
        <p class="text-muted"><?php echo $product_name_filter ? "Full history for this specific product." : "Complete record of all inventory adjustments (Manual & Sales)"; ?></p>
    </div>
    <?php if ($product_name_filter): ?>
        <a href="products.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Products</a>
    <?php endif; ?>
</div>

<!-- Summary Stats Cards -->
<div class="dashboard-cards mb-4">
    <div class="card card-stats">
        <div class="card-icon icon-blue"><i class="fas fa-plus-circle"></i></div>
        <div class="card-info">
            <div class="card-title">Stock In</div>
            <div class="card-value text-success">+<?php echo number_format($total_in); ?></div>
            <div class="card-sub">Total items added</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon icon-orange"><i class="fas fa-minus-circle"></i></div>
        <div class="card-info">
            <div class="card-title">Stock Out</div>
            <div class="card-value text-danger">-<?php echo number_format($total_out); ?></div>
            <div class="card-sub">Total items removed</div>
        </div>
    </div>
    <div class="card card-stats">
        <div class="card-icon <?php echo $net_change >= 0 ? 'icon-green' : 'icon-red'; ?>"><i class="fas fa-exchange-alt"></i></div>
        <div class="card-info">
            <div class="card-title">Net Change</div>
            <div class="card-value <?php echo $net_change >= 0 ? 'text-success' : 'text-danger'; ?>">
                <?php echo ($net_change > 0 ? '+' : '') . number_format($net_change); ?>
            </div>
            <div class="card-sub">Overall inventory shift</div>
        </div>
    </div>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success alert-dismissible"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<?php if(isset($_GET['err'])): ?>
    <div class="alert alert-danger alert-dismissible"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] !== 'chairman'): ?>
<div class="form-card adjustment-form-card">
    <div class="form-header">
        <h2><i class="fas fa-edit"></i> Manual Stock Adjustment</h2>
        <p class="text-muted">Manually increase or decrease product quantities.</p>
    </div>
    <form action="stock_movements.php" method="post" id="adjustment-form" autocomplete="off">
        <div class="form-row">
            <div class="form-group col-md-6">
                <label><i class="fas fa-box"></i> Product</label>
                <select name="product_id" class="form-control select2" required>
                    <option value="">Search or Select Product...</option>
                    <?php 
                    // Reset products list for the form
                    $products_list->data_seek(0);
                    while($p = $products_list->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $p['id']; ?>" <?php echo ($filter_product_id == $p['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($p['name']); ?> (In Stock: <?php echo $p['quantity']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group col-md-6">
                <label><i class="fas fa-sort-numeric-up-alt"></i> Quantity Change</label>
                <input type="number" name="quantity_change" id="quantity_change" class="form-control" placeholder="Use + for adding, - for removing (e.g. 10 or -5)" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group col-md-6">
                <label><i class="fas fa-info-circle"></i> Adjustment Reason</label>
                <select name="movement_type" id="movement_type" class="form-control" required>
                    <optgroup label="Stock In">
                        <option value="Restock">Restock (New Supply)</option>
                        <option value="Return">Customer Return</option>
                    </optgroup>
                    <optgroup label="Stock Out">
                        <option value="Damage">Damage / Expired</option>
                        <option value="Usage">Internal Usage</option>
                    </optgroup>
                    <optgroup label="Other">
                        <option value="Correction">Manual Correction</option>
                        <option value="Other">Other (Please specify)</option>
                    </optgroup>
                </select>
            </div>
            <div class="form-group col-md-6" id="other_reason_group" style="display: none;">
                <label><i class="fas fa-comment-alt"></i> Specify Other Reason</label>
                <input type="text" name="movement_reason" id="movement_reason" class="form-control" placeholder="Why are you making this change?">
            </div>
        </div>
        <div class="form-footer">
            <button type="submit" name="adjust_stock" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Save Adjustment
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<div class="card table-card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h2><i class="fas fa-list"></i> Detailed Movement Logs</h2>
        <span class="text-muted"><i class="fas fa-clock"></i> Sorted by most recent</span>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Ref ID</th>
                    <th>Product Details</th>
                    <th class="text-center">Change</th>
                    <th>Reason & Type</th>
                    <th>Processed By</th>
                    <th class="text-right">Date & Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($movements->num_rows > 0): ?>
                    <?php while($row = $movements->fetch_assoc()): ?>
                    <tr>
                        <td><span class="text-muted">#<?php echo $row['id']; ?></span></td>
                        <td>
                            <div class="product-cell">
                                <span class="product-name"><?php echo htmlspecialchars($row['product_name']); ?></span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="qty-badge <?php echo $row['quantity_change'] > 0 ? 'qty-in' : 'qty-out'; ?>">
                                <?php echo ($row['quantity_change'] > 0 ? '+' : '') . $row['quantity_change']; ?>
                            </span>
                        </td>
                        <td>
                            <div class="type-cell">
                                <span class="type-badge type-<?php echo strtolower(str_replace(' ', '-', $row['movement_type'])); ?>">
                                    <?php echo htmlspecialchars($row['movement_type']); ?>
                                </span>
                                <?php if (!empty($row['movement_reason'])): ?>
                                    <div class="reason-text"><i class="fas fa-quote-left"></i> <?php echo htmlspecialchars($row['movement_reason']); ?></div>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div class="user-cell">
                                <i class="fas fa-user-circle text-muted"></i> <?php echo htmlspecialchars($row['employee_name']); ?>
                            </div>
                        </td>
                        <td class="text-right">
                            <div class="date-cell">
                                <strong><?php echo date('M d, Y', strtotime($row['movement_date'])); ?></strong><br>
                                <small class="text-muted"><?php echo date('H:i', strtotime($row['movement_date'])); ?></small>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 60px;">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No stock movements recorded yet.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
    /* Headers & Text */
    .header-actions { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px; }
    .header-left h1 { font-size: 28px; font-weight: 800; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 12px; }
    .text-muted { color: #64748b; font-size: 14px; margin-top: 6px; }
    .text-success { color: #10b981 !important; }
    .text-danger { color: #ef4444 !important; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .mb-4 { margin-bottom: 24px; }
    .mt-4 { margin-top: 24px; }

    /* Summary Cards */
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
    .icon-orange { background: #fff7ed; color: #f97316; }
    .icon-green { background: #f0fdf4; color: #10b981; }
    .icon-red { background: #fef2f2; color: #ef4444; }
    
    .card-value { font-size: 26px; font-weight: 800; color: #1e293b; letter-spacing: -0.5px; }
    .card-sub { font-size: 13px; color: #94a3b8; margin-top: 2px; }

    /* Form Styling */
    .adjustment-form-card { padding: 32px !important; border-top: 4px solid #3b82f6; }
    .form-header { margin-bottom: 24px; }
    .form-header h2 { font-size: 20px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
    .form-group label { font-weight: 600; color: #475569; margin-bottom: 10px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .form-control { padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 15px; background-color: #f8fafc; }
    .form-control:focus { background-color: #fff; border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1); }
    .form-footer { margin-top: 24px; padding-top: 24px; border-top: 1px solid #f1f5f9; }
    .btn-lg { padding: 12px 32px; font-size: 16px; font-weight: 700; border-radius: 8px; }

    /* Table Styling */
    .table-card { border-radius: 12px; overflow: hidden; }
    .table-card .card-header { padding: 20px 24px; background: #fff; border-bottom: 1px solid #f1f5f9; }
    .table-card .card-header h2 { font-size: 18px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 10px; }
    
    table thead th { background: #f8fafc; padding: 16px 24px; font-weight: 700; color: #64748b; font-size: 12px; }
    table tbody td { padding: 18px 24px; vertical-align: middle; }
    
    .product-name { font-weight: 700; color: #1e293b; font-size: 15px; }
    .qty-badge {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-weight: 800;
        font-size: 14px;
        min-width: 60px;
        text-align: center;
    }
    .qty-in { background: #dcfce7; color: #059669; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.1); }
    .qty-out { background: #fee2e2; color: #dc2626; box-shadow: 0 2px 4px rgba(239, 68, 68, 0.1); }
    
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
    .type-sale { background-color: #f0fdf4; color: #16a34a; }
    .type-manual-correction, .type-other { background-color: #f1f5f9; color: #475569; }
    
    .reason-text { font-size: 13px; color: #64748b; margin-top: 8px; font-style: italic; background: #f8fafc; padding: 6px 10px; border-radius: 4px; border-left: 3px solid #cbd5e1; }
    .user-cell { display: flex; align-items: center; gap: 8px; font-weight: 600; color: #475569; font-size: 14px; }
    .date-cell strong { color: #1e293b; font-size: 14px; }
    
    .alert { display: flex; align-items: center; gap: 12px; padding: 16px 20px; border-radius: 10px; border: none; font-weight: 600; }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const movementType = document.getElementById('movement_type');
    const otherReasonGroup = document.getElementById('other_reason_group');
    const otherReasonInput = document.getElementById('movement_reason');
    const adjustmentForm = document.getElementById('adjustment-form');
    const quantityChangeInput = document.getElementById('quantity_change');

    movementType.addEventListener('change', function () {
        if (this.value === 'Other') {
            otherReasonGroup.style.display = 'block';
            otherReasonInput.setAttribute('required', 'required');
        } else {
            otherReasonGroup.style.display = 'none';
            otherReasonInput.removeAttribute('required');
            otherReasonInput.value = '';
        }
    });

    adjustmentForm.addEventListener('submit', function (e) {
        const quantity = parseInt(quantityChangeInput.value, 10);
        if (quantity < 0) {
            if (!confirm('You are about to remove stock. Are you sure you want to proceed?')) {
                e.preventDefault();
            }
        }
    });
});
</script>
