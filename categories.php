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

// Role-based access control (Admin, Manager, and Chairman only)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager' && $_SESSION['role'] !== 'chairman') {
    header("Location: dashboard.php?err=Access+Denied");
    exit;
}

$message = '';

// Handle Add Category (Admin and Manager only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'manager') {
        header("Location: categories.php?err=Access+Denied.+Insufficient+permissions");
        exit;
    } else {
        $name = $_POST['category_name'];
        // Check if exists...
        $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Category already exists.</div>";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->bind_param("s", $name);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Category added successfully!</div>";
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Added new category: " . htmlspecialchars($name);
                $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                $log->bind_param("is", $user_id, $action);
                $log->execute();
                $log->close();
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Handle Delete Category (Admin Only)
if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] !== 'admin') {
        header("Location: categories.php?err=Access+Denied.+Only+admins+can+delete+categories");
        exit;
    } else {
        $id = $_GET['delete_id'];
        // Check if any products use this category...
        $check = $conn->prepare("SELECT id FROM products WHERE category_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $message = "<div class='alert alert-danger'>Cannot delete category. Products are still linked to it.</div>";
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $message = "<div class='alert alert-success'>Category deleted successfully!</div>";
                // Log activity
                $user_id = $_SESSION['user_id'];
                $action = "Deleted category ID #$id";
                $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                $log->bind_param("is", $user_id, $action);
                $log->execute();
                $log->close();
            }
            $stmt->close();
        }
        $check->close();
    }
}

include 'header.php';

// Fetch all categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

?>

<h1>Category Management</h1>

<?php echo $message; ?>

<!-- Add Category Form (Admin and Manager only) -->
<?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager'): ?>
<div class="form-card">
    <h2>Add New Category</h2>
    <form action="categories.php" method="post" autocomplete="off">
        <div class="form-group">
            <label for="category_name">Category Name</label>
            <input type="text" name="category_name" id="category_name" class="form-control" placeholder="e.g. Phones, Chargers, Smart Watches" required>
        </div>
        <button type="submit" name="add_category" class="btn btn-primary">Add Category</button>
    </form>
</div>
<?php endif; ?>

<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $categories->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td>
                    <?php if($_SESSION['role'] === 'admin'): ?>
                        <a href="categories.php?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this category?')">Delete</a>
                    <?php else: ?>
                        <span class="text-muted">No Actions</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php
$conn->close();
include 'footer.php'; 
?>