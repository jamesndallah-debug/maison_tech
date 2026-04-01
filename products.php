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

// Chairman is read-only (no product creation/edits/deletes)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'chairman' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Location: products.php?err=Access+Denied.+Chairman+is+read-only");
    exit;
}

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
        header("Location: products.php?err=Access+Denied.+Insufficient+permissions");
        exit;
    }
    $name = $_POST['product_name'];
    $cost_price = $_POST['cost_price'];
    $price = $_POST['product_price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];
    
    // Handle Image Upload
    $image_path = "";
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $name) . "." . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            $image_path = $target_file;
        }
    }

    // Insert the product
    $stmt = $conn->prepare("INSERT INTO products (name, cost_price, price, quantity, category_id, description, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sddiiss", $name, $cost_price, $price, $quantity, $category_id, $description, $image_path);
    if ($stmt->execute()) {
        $product_id = $stmt->insert_id;
        $stmt->close();

        // Log the stock movement
        $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity_change, movement_type, user_id) VALUES (?, ?, 'New Stock', ?)");
        $stmt->bind_param("iii", $product_id, $quantity, $user_id);
        $stmt->execute();
        $stmt->close();

        // Log activity
        $action = "Added new product: " . htmlspecialchars($name);
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $user_id, $action);
        $log->execute();
        $log->close();

        header("Location: products.php?msg=Product+added+successfully");
        exit;
    }
}

// Handle Delete Product
if (isset($_GET['delete_id']) && $_SESSION['role'] === 'admin') {
    $id = (int)$_GET['delete_id'];
    
    // Fetch product name for logging
    $p_stmt = $conn->prepare("SELECT name FROM products WHERE id = ?");
    $p_stmt->bind_param("i", $id);
    $p_stmt->execute();
    $p_res = $p_stmt->get_result();
    
    if ($p_res->num_rows > 0) {
        $p_name = $p_res->fetch_assoc()['name'];
        $p_stmt->close();

        // 1. Delete dependent records manually (to avoid foreign key errors)
        $conn->query("DELETE FROM stock_movements WHERE product_id = $id");
        $conn->query("DELETE FROM sale_items WHERE product_id = $id");

        // 2. Delete the product
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $user_id = $_SESSION['user_id'];
            $action = "Deleted product: " . htmlspecialchars($p_name);
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $user_id, $action);
            $log->execute();
            $log->close();
        }
        $stmt->close();
        header("Location: products.php?msg=Product+deleted+successfully");
        exit;
    } else {
        $p_stmt->close();
        header("Location: products.php?err=Product+not+found");
        exit;
    }
}

include 'header.php';

// Fetch categories for the dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Fetch all products with category names
$products = $conn->query("
    SELECT p.*, c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name ASC
");

?>

<div class="header-actions">
    <h1>Products</h1>
</div>

<?php if(isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($_GET['msg']); ?></div>
<?php endif; ?>

<!-- Add Product Form -->
<?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
<div class="form-card">
    <h2>Add New Product</h2>
    <form action="products.php" method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="form-row">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" class="form-control" placeholder="e.g. iPhone 15 Pro" required>
            </div>
            <div class="form-group">
                <label>Cost Price ($)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Selling Price ($)</label>
                <input type="number" step="0.01" name="product_price" class="form-control" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label>Initial Quantity</label>
                <input type="number" name="quantity" class="form-control" placeholder="0" required>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php 
                    // Reset category pointer
                    $categories->data_seek(0);
                    while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Product Image</label>
                <input type="file" name="product_image" class="form-control" accept="image/*">
            </div>
        </div>
        <div class="form-group">
            <label>Product Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Enter product description for the client side..." required></textarea>
        </div>
        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
    </form>
</div>
<?php endif; ?>

<!-- Product List -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Image</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $products->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if($row['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="product" class="product-thumb">
                    <?php else: ?>
                        <div class="no-img">No Img</div>
                    <?php endif; ?>
                </td>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td><span class="badge"><?php echo htmlspecialchars($row['category_name'] ?? 'Uncategorized'); ?></span></td>
                <td>TSh <?php echo number_format($row['price'], 0); ?></td>
                <td><?php echo $row['quantity']; ?></td>
                <td>
                    <?php if($row['quantity'] <= 0): ?>
                        <span class="status-badge status-out">Out of Stock</span>
                    <?php elseif($row['quantity'] < 10): ?>
                        <span class="status-badge status-low">Low Stock</span>
                    <?php else: ?>
                        <span class="status-badge status-in">In Stock</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="stock_movements.php?product_id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm" title="View History">History</a>
                    <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
                        <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                    <?php endif; ?>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="products.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this product?')">Delete</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<style>
    .form-row {
        display: flex;
        gap: 16px;
        margin-bottom: 16px;
    }
    .form-row .form-group {
        flex: 1;
        margin-bottom: 0;
    }
    .product-thumb {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
        border: 1px solid #e2e8f0;
    }
    .no-img {
        width: 40px;
        height: 40px;
        background: #f1f5f9;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        color: #94a3b8;
        border-radius: 4px;
    }
    .badge {
        background: #f1f5f9;
        color: #475569;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
    }
    .status-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .status-in { background: #dcfce7; color: #166534; }
    .status-low { background: #fef9c3; color: #854d0e; }
    .status-out { background: #fee2e2; color: #991b1b; }
    .btn-sm { padding: 4px 8px; font-size: 12px; }

    /* Mobile Responsiveness for Forms */
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        .form-row .form-group {
            margin-bottom: 16px;
        }
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>