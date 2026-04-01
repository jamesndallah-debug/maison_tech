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

// Chairman is read-only (no management access)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'chairman') {
    header('Location: dashboard.php');
    exit;
}

$message = '';

// Handle Return Processing
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_return'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $reason = trim($_POST['reason']);
    $condition = $_POST['condition'];
    $refund_amount = (float)$_POST['refund_amount'];
    $user_id = $_SESSION['user_id'];
    $sale_id = !empty($_POST['sale_id']) ? (int)$_POST['sale_id'] : null;

    if ($product_id > 0 && $quantity > 0 && !empty($reason)) {
        $conn->begin_transaction();
        try {
            // 1. Insert into returns table
            $stmt = $conn->prepare("INSERT INTO returns (sale_id, product_id, quantity, return_reason, item_condition, refund_amount, processed_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissdi", $sale_id, $product_id, $quantity, $reason, $condition, $refund_amount, $user_id);
            $stmt->execute();
            $return_id = $stmt->insert_id;
            $stmt->close();

            // 2. Update product quantity if resellable
            if ($condition === 'resellable') {
                $stmt = $conn->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product_id);
                $stmt->execute();
                $stmt->close();

                // Log stock movement
                $movement_reason = "Return #$return_id - $reason";
                $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity_change, movement_type, movement_reason, user_id) VALUES (?, ?, 'Return', ?, ?)");
                $stmt->bind_param("iisi", $product_id, $quantity, $movement_reason, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // 3. Log expense if refund was issued
            if ($refund_amount > 0) {
                $expense_desc = "Refund for Return #$return_id";
                $stmt = $conn->prepare("INSERT INTO expenses (expense_category, description, amount, related_id, related_type, processed_by) VALUES ('Refund', ?, ?, ?, 'Return', ?)");
                $stmt->bind_param("sdii", $expense_desc, $refund_amount, $return_id, $user_id);
                $stmt->execute();
                $stmt->close();
            }

            // 4. Log activity
            $p_name_res = $conn->query("SELECT name FROM products WHERE id = $product_id");
            $p_name = $p_name_res->fetch_assoc()['name'];
            $action = "Processed return #$return_id for $p_name (Qty: $quantity) - Refund: TSh " . number_format($refund_amount, 0);
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $user_id, $action);
            $log->execute();
            $log->close();

            $conn->commit();
            $message = "<div class='alert alert-success'>Return processed successfully! (ID: #$return_id)</div>";
        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div class='alert alert-danger'>Error processing return: " . $e->getMessage() . "</div>";
        }
    } else {
        $message = "<div class='alert alert-danger'>Please fill in all required fields.</div>";
    }
}

include 'header.php';

// Fetch return history based on user role
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

$query = "
    SELECT r.*, p.name as product_name, e.username as processor_name
    FROM returns r
    JOIN products p ON r.product_id = p.id
    JOIN employees e ON r.processed_by = e.id
";

if ($user_role === 'staff') {
    $query .= " WHERE r.processed_by = ?";
}

$query .= " ORDER BY r.return_date DESC";

$stmt = $conn->prepare($query);

if ($user_role === 'staff') {
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$returns = $stmt->get_result();
$stmt->close();
?>

<div class="header-actions">
    <h1><i class="fas fa-undo"></i> Return Management</h1>
</div>

<?php echo $message; ?>

<div class="row">
    <!-- Return Form -->
    <div class="col-md-4">
        <div class="card form-card">
            <div class="card-header">
                <h2><i class="fas fa-plus-circle"></i> Record Return</h2>
            </div>
            <div class="card-body">
                <form action="return_management.php" method="POST" id="return-form" autocomplete="off">
                    <div class="form-group">
                        <label>Product Search</label>
                        <div class="search-box">
                            <input type="text" id="product-search-input" class="form-control" placeholder="Search product name..." required>
                            <div id="product-search-results" class="search-results"></div>
                        </div>
                        <input type="hidden" name="product_id" id="selected-product-id" required>
                        <div id="selected-product-info" class="mt-2 text-primary font-bold"></div>
                    </div>

                    <div class="form-group">
                        <label>Sale ID (Optional)</label>
                        <input type="number" name="sale_id" class="form-control" placeholder="Original Sale #">
                    </div>

                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="number" name="quantity" class="form-control" min="1" required>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>Refund Amount ($)</label>
                                <input type="number" step="0.01" name="refund_amount" class="form-control" value="0.00">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Item Condition</label>
                        <select name="condition" class="form-control" required>
                            <option value="resellable">Resellable (Return to Stock)</option>
                            <option value="damaged">Damaged (Do Not Return to Stock)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reason for Return</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="Defective, wrong item, etc." required></textarea>
                    </div>

                    <button type="submit" name="process_return" class="btn btn-primary btn-block">
                        <i class="fas fa-check"></i> Process Return
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Return History -->
    <div class="col-md-8">
        <div class="card table-card">
            <div class="card-header">
                <h2><i class="fas fa-history"></i> Return History</h2>
            </div>
            <div class="table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Condition</th>
                            <th>Refund</th>
                            <th>Processed By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($returns && $returns->num_rows > 0): ?>
                            <?php while($row = $returns->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $row['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['product_name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($row['return_reason']); ?></small>
                                    <?php if($row['sale_id']): ?>
                                        <br><small class="text-info">Sale: #<?php echo $row['sale_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['quantity']; ?></td>
                                <td>
                                    <span class="badge <?php echo $row['item_condition'] == 'resellable' ? 'alert-success' : 'alert-danger'; ?>">
                                        <?php echo ucfirst($row['item_condition']); ?>
                                    </span>
                                </td>
                                <td>TSh <?php echo number_format($row['refund_amount'], 0); ?></td>
                                <td><?php echo htmlspecialchars($row['processor_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($row['return_date'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-4">No returns recorded yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    .search-box { position: relative; }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 100;
        display: none;
        max-height: 250px;
        overflow-y: auto;
    }
    .search-item {
        padding: 10px 15px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .search-item:hover { background: #f8fafc; }
    .p-name { display: block; font-weight: 600; font-size: 14px; }
    .p-price { color: #007bff; font-size: 12px; }
    .btn-block { width: 100%; padding: 12px; font-weight: 700; margin-top: 10px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('product-search-input');
    const searchResults = document.getElementById('product-search-results');
    const selectedId = document.getElementById('selected-product-id');
    const selectedInfo = document.getElementById('selected-product-info');

    searchInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        fetch(`search_products.php?q=${encodeURIComponent(query)}&include_out_of_stock=1`)
            .then(res => res.json())
            .then(data => {
                searchResults.innerHTML = '';
                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="no-results p-3">No products found</div>';
                } else {
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'search-item';
                        div.innerHTML = `
                            <div>
                                <span class="p-name">${p.name}</span>
                                <span class="p-price">$${p.price}</span>
                            </div>
                        `;
                        div.onclick = () => {
                            selectedId.value = p.id;
                            searchInput.value = p.name;
                            selectedInfo.innerText = `Selected: ${p.name}`;
                            searchResults.style.display = 'none';
                        };
                        searchResults.appendChild(div);
                    });
                }
                searchResults.style.display = 'block';
            });
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target)) searchResults.style.display = 'none';
    });
});
</script>

<?php include 'footer.php'; ?>