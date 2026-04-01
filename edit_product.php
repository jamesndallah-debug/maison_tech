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

$allowed_roles = ['admin', 'manager'];
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], $allowed_roles, true)) {
    header("Location: products.php?err=Access+Denied.+Insufficient+permissions");
    exit;
}

$id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch the product to edit
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: products.php?err=Product+not+found");
    exit;
}

// Handle Update Product
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = $_POST['product_name'];
    $cost_price = $_POST['cost_price'];
    $price = $_POST['product_price'];
    $quantity = $_POST['quantity'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    
    // Handle Image Upload
    $image_path = $product['image_url']; // Default to current image
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] == 0) {
        $target_dir = "uploads/";
        $file_extension = pathinfo($_FILES["product_image"]["name"], PATHINFO_EXTENSION);
        $file_name = time() . "_" . preg_replace("/[^a-zA-Z0-9]/", "_", $name) . "." . $file_extension;
        $target_file = $target_dir . $file_name;
        
        if (move_uploaded_file($_FILES["product_image"]["tmp_name"], $target_file)) {
            // Delete old image if it exists
            if (!empty($product['image_url']) && file_exists($product['image_url'])) {
                unlink($product['image_url']);
            }
            $image_path = $target_file;
        }
    }

    // Calculate the quantity change
    $quantity_change = $quantity - $product['quantity'];

    // Update the product
    $stmt = $conn->prepare("UPDATE products SET name = ?, cost_price = ?, price = ?, quantity = ?, category_id = ?, description = ?, image_url = ? WHERE id = ?");
    $stmt->bind_param("sddiissi", $name, $cost_price, $price, $quantity, $category_id, $description, $image_path, $id);
    
    if ($stmt->execute()) {
        $stmt->close();

        // Log the stock movement if the quantity changed
        if ($quantity_change != 0) {
            $movement_type = $quantity_change > 0 ? 'Stock Correction (IN)' : 'Stock Correction (OUT)';
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity_change, movement_type, user_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $id, $quantity_change, $movement_type, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // Log activity
        $action = "Updated product details: " . htmlspecialchars($name);
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $user_id, $action);
        $log->execute();
        $log->close();

        header("Location: products.php?msg=Product+updated+successfully");
        exit;
    }
}

include 'header.php';

// Fetch categories for the dropdown
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

?>

<div class="header-actions">
    <h1>Edit Product</h1>
    <a href="products.php" class="btn btn-secondary">&larr; Back to Products</a>
</div>

<div class="form-card">
    <form action="edit_product.php?id=<?php echo $id; ?>" method="post" enctype="multipart/form-data" autocomplete="off">
        <div class="form-row">
            <div class="form-group">
                <label>Product Name</label>
                <input type="text" name="product_name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            <div class="form-group">
                <label>Cost Price ($)</label>
                <input type="number" step="0.01" name="cost_price" class="form-control" value="<?php echo $product['cost_price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Selling Price ($)</label>
                <input type="number" step="0.01" name="product_price" class="form-control" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Current Stock</label>
                <input type="number" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" required>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>Category</label>
                <select name="category_id" class="form-control" required>
                    <option value="">Select Category</option>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                        <option value="<?php echo $cat['id']; ?>" <?php if($cat['id'] == $product['category_id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Product Image</label>
                <?php if(!empty($product['image_url'])): ?>
                    <div style="margin-bottom: 10px;">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="Current Product Image" style="width: 80px; height: 80px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">
                        <p style="font-size: 12px; color: #666; margin-top: 5px;">Current Image</p>
                    </div>
                <?php endif; ?>
                <input type="file" name="product_image" class="form-control" accept="image/*">
            </div>
        </div>

        <div class="form-group">
            <label>Product Description</label>
            <textarea name="description" class="form-control" rows="4" placeholder="Enter product description..." required><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
            <a href="products.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<style>
    .header-actions { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .form-row { display: flex; gap: 20px; margin-bottom: 20px; }
    .form-row .form-group { flex: 1; }
    .form-actions { margin-top: 24px; display: flex; gap: 12px; }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>