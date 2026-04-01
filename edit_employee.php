<?php
include 'header.php';
include 'dp.php';

$id = $_GET['id'];

// Handle Update Employee
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_employee'])) {
    $name = $_POST['employee_name'];
    $position = $_POST['employee_position'];

    $stmt = $conn->prepare("UPDATE employees SET name = ?, position = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $position, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: employees.php");
    exit;
}

// Fetch the employee to edit
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

?>

<h1>Edit Employee</h1>

<div class="edit-employee-form">
    <form action="edit_employee.php?id=<?php echo $id; ?>" method="post" autocomplete="off">
        <input type="text" name="employee_name" value="<?php echo htmlspecialchars($employee['name']); ?>" required>
        <input type="text" name="employee_position" value="<?php echo htmlspecialchars($employee['position']); ?>" required>
        <button type="submit" name="update_employee">Update Employee</button>
    </form>
</div>

<style>
    .edit-employee-form {
        background-color: #ffffff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .edit-employee-form input, .edit-employee-form button {
        padding: 10px;
        margin-right: 10px;
        border-radius: 4px;
        border: 1px solid #ddd;
    }
    .edit-employee-form button {
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