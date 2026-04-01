<?php
include 'header.php';
include 'dp.php';

$id = $_GET['id'];

// Handle Update Sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_sale'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];

    $stmt = $conn->prepare("UPDATE sales SET product_id = ?, quantity = ? WHERE id = ?");
    $stmt->bind_param("iii", $product_id, $quantity, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: sales.php");
    exit;
}

// Fetch the sale to edit
$stmt = $conn->prepare("SELECT * FROM sales WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$sale = $result->fetch_assoc();
$stmt->close();

?>

<h1>Edit Sale</h1>

<div class="edit-sale-form">
    <form action="edit_sale.php?id=<?php echo $id; ?>" method="post" autocomplete="off">
        <input type="number" name="product_id" value="<?php echo $sale['product_id']; ?>" required>
        <input type="number" name="quantity" value="<?php echo $sale['quantity']; ?>" required>
        <button type="submit" name="update_sale">Update Sale</button>
    </form>
</div>

<style>
    .edit-sale-form {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .edit-sale-form input, .edit-sale-form button {
        padding: 10px;
        margin-right: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .edit-sale-form button {
        background-color: #007bff;
        color: white;
        border-color: #007bff;
        cursor: pointer;
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>