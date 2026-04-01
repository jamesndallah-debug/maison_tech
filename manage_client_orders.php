<?php
include 'header.php';

// Only admins and chairman can manage client orders
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    echo "<div class='alert alert-danger'>Access Denied.</div>";
    include 'footer.php';
    exit;
}

$message = '';
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE client_orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $message = "<div class='alert alert-success'>Order status updated successfully.</div>";
    } else {
        $message = "<div class='alert alert-danger'>Error updating status: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

// Summary Statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) FROM client_orders")->fetch_row()[0],
    'pending' => $conn->query("SELECT COUNT(*) FROM client_orders WHERE status = 'pending'")->fetch_row()[0],
    'approved' => $conn->query("SELECT COUNT(*) FROM client_orders WHERE status = 'approved'")->fetch_row()[0],
    'shipped' => $conn->query("SELECT COUNT(*) FROM client_orders WHERE status = 'shipped'")->fetch_row()[0],
];

// Fetch orders
$orders = $conn->query("SELECT co.*, p.name as catalog_product_name FROM client_orders co LEFT JOIN products p ON co.product_id = p.id ORDER BY co.created_at DESC");

// Status color helper
function getStatusBadgeClass($status) {
    switch($status) {
        case 'pending': return 'bg-warning text-dark';
        case 'approved': return 'bg-info text-white';
        case 'paid': return 'bg-primary text-white';
        case 'shipped': return 'bg-info text-white';
        case 'delivered': return 'bg-success text-white';
        case 'cancelled': return 'bg-danger text-white';
        default: return 'bg-secondary text-white';
    }
}
?>

<style>
    .order-summary-row {
        margin-bottom: 30px;
    }
    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 15px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        border-left: 5px solid #ffd700;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .stat-card i {
        font-size: 2rem;
        color: #ffd700;
    }
    .stat-info h3 {
        margin: 0;
        font-weight: 800;
        font-size: 1.5rem;
    }
    .stat-info p {
        margin: 0;
        color: #64748b;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .orders-table-container {
        background: white;
        border-radius: 20px;
        padding: 25px;
        box-shadow: 0 10px 15px rgba(0,0,0,0.05);
    }
    
    .client-info-cell {
        min-width: 200px;
    }
    .product-info-cell {
        max-width: 300px;
    }
    
    .status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .update-status-form {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f8fafc;
        padding: 5px;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
    }
    .update-status-form select {
        border: none;
        background: transparent;
        font-size: 0.85rem;
        font-weight: 600;
        padding: 2px 5px;
    }
    .update-status-form button {
        padding: 4px 10px;
        font-size: 0.75rem;
        border-radius: 8px;
    }
    
    .fee-row {
        color: #f59e0b;
        font-size: 0.8rem;
        font-weight: 600;
    }
</style>

<div class="header d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold">Client <span>Orders</span></h1>
    <div class="text-muted small">Managing <?php echo $stats['total']; ?> total requests</div>
</div>

<?php echo $message; ?>

<!-- Summary Dashboard -->
<div class="row order-summary-row g-4">
    <div class="col-md-3">
        <div class="stat-card">
            <i class="fas fa-list-ul"></i>
            <div class="stat-info">
                <h3><?php echo $stats['total']; ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #f59e0b;">
            <i class="fas fa-clock" style="color: #f59e0b;"></i>
            <div class="stat-info">
                <h3><?php echo $stats['pending']; ?></h3>
                <p>Pending</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #3b82f6;">
            <i class="fas fa-check-circle" style="color: #3b82f6;"></i>
            <div class="stat-info">
                <h3><?php echo $stats['approved']; ?></h3>
                <p>Approved</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card" style="border-left-color: #10b981;">
            <i class="fas fa-truck" style="color: #10b981;"></i>
            <div class="stat-info">
                <h3><?php echo $stats['shipped']; ?></h3>
                <p>Shipped</p>
            </div>
        </div>
    </div>
</div>

<div class="orders-table-container">
    <div class="table-responsive">
        <table class="table align-middle custom-table">
            <thead>
                <tr>
                    <th style="width: 80px;">Order</th>
                    <th>Client Information</th>
                    <th>Product & Details</th>
                    <th>Location</th>
                    <th>Financials</th>
                    <th>Status</th>
                    <th>Update Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders && $orders->num_rows > 0): ?>
                    <?php while($order = $orders->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <div class="fw-bold">#<?php echo $order['id']; ?></div>
                            <small class="text-muted"><?php echo date('M d', strtotime($order['created_at'])); ?></small>
                        </td>
                        <td class="client-info-cell">
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm bg-light rounded-circle p-2" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-user text-muted small"></i>
                                </div>
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($order['client_name']); ?></div>
                                    <div class="text-muted small"><?php echo htmlspecialchars($order['client_phone']); ?></div>
                                    <?php if($order['client_email']): ?>
                                        <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($order['client_email']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="product-info-cell">
                            <div class="mb-1">
                                <span class="badge <?php echo $order['order_type'] == 'catalog' ? 'bg-soft-success text-success' : 'bg-soft-danger text-danger'; ?>" style="font-size: 0.65rem; background: <?php echo $order['order_type'] == 'catalog' ? '#ecfdf5' : '#fef2f2'; ?>;">
                                    <?php echo strtoupper($order['order_type']); ?>
                                </span>
                            </div>
                            <div class="fw-bold"><?php echo htmlspecialchars($order['catalog_product_name'] ?? $order['product_name']); ?></div>
                            <?php if ($order['product_description']): ?>
                                <div class="text-muted small text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($order['product_description']); ?>">
                                    <?php echo htmlspecialchars($order['product_description']); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1 text-muted small">
                                <i class="fas fa-map-marker-alt text-danger"></i>
                                <?php echo htmlspecialchars($order['region']); ?>
                            </div>
                        </td>
                        <td>
                            <div class="fw-bold">$<?php echo number_format($order['amount'], 2); ?></div>
                            <?php if($order['agency_fee'] > 0): ?>
                                <div class="fee-row">+ $<?php echo number_format($order['agency_fee'], 2); ?> Fee</div>
                            <?php endif; ?>
                            <div class="text-primary fw-bold mt-1" style="font-size: 1.1rem;">$<?php echo number_format($order['total_payable'], 2); ?></div>
                        </td>
                        <td>
                            <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                <?php echo $order['status']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" class="update-status-form">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <select name="status">
                                    <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="approved" <?php echo $order['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="paid" <?php echo $order['status'] == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="shipped" <?php echo $order['status'] == 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                    <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                    <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-dark btn-sm">Set</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3 d-block"></i>
                            <p class="text-muted">No client orders found in the system.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'footer.php'; ?>