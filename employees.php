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

// Role-based access control (Admin and Chairman only)
if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman') {
    header("Location: dashboard.php?err=Access+Denied");
    exit;
}

$message = '';
if (isset($_SESSION['msg'])) {
    $message = $_SESSION['msg'];
    unset($_SESSION['msg']);
}

// Handle Add Employee (Admin Only)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_employee'])) {
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Access Denied. Only admins can add employees</div>";
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = strtolower(trim((string)($_POST['role'] ?? 'staff')));

        // Force role to match allowed values exactly (prevents silent DB fallback).
        $allowed_roles = ['staff', 'manager', 'chairman', 'money_agent', 'admin'];
        if (!in_array($role, $allowed_roles, true)) {
            $role = 'staff';
        }

        $stmt = $conn->prepare("INSERT INTO employees (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $password, $role);
        if ($stmt->execute()) {
            $_SESSION['msg'] = "<div class='alert alert-success'>Employee added successfully!</div>";
            // Log action
            $user_id = $_SESSION['user_id'];
            $action = "Created new employee account: " . htmlspecialchars($username) . " (role: " . htmlspecialchars($role) . ")";
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $user_id, $action);
            $log->execute();
            $log->close();
        } else {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Error adding employee: " . $stmt->error . "</div>";
        }
        $stmt->close();
    }
    header("Location: employees.php");
    exit;
}

// Handle Delete Employee (Admin Only)
if (isset($_GET['delete_id'])) {
    if ($_SESSION['role'] !== 'admin') {
        $_SESSION['msg'] = "<div class='alert alert-danger'>Access Denied. Only admins can delete employees</div>";
    } else {
        $id = $_GET['delete_id'];
        if ($id == $_SESSION['user_id']) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>You cannot delete your own account.</div>";
        } else {
            // Fetch name for logging
            $u_stmt = $conn->prepare("SELECT username FROM employees WHERE id = ?");
            $u_stmt->bind_param("i", $id);
            $u_stmt->execute();
            $u_res = $u_stmt->get_result();
            if ($u_res->num_rows > 0) {
                $u_name = $u_res->fetch_assoc()['username'];
                $u_stmt->close();

                // 1. Delete dependent records manually (to avoid foreign key errors)
                $conn->query("DELETE FROM activity_logs WHERE user_id = $id");
                $conn->query("DELETE FROM stock_movements WHERE user_id = $id");
                
                // Note: For sales, we delete sale items first
                $conn->query("DELETE FROM sale_items WHERE sale_id IN (SELECT id FROM sales WHERE user_id = $id)");
                $conn->query("DELETE FROM sales WHERE user_id = $id");

                // 2. Delete the employee
                $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->bind_param("i", $id);
                if ($stmt->execute()) {
                    $_SESSION['msg'] = "<div class='alert alert-success'>Employee deleted successfully!</div>";
                    // Log action
                    $user_id = $_SESSION['user_id'];
                    $action = "Deleted employee account: " . htmlspecialchars($u_name);
                    $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                    $log->bind_param("is", $user_id, $action);
                    $log->execute();
                    $log->close();
                }
                $stmt->close();
            }
        }
    }
    header("Location: employees.php");
    exit;
}

include 'header.php';

// Fetch all employees
$employees = $conn->query("SELECT * FROM employees ORDER BY username ASC");

// Fetch activity logs and performance stats if requested
$activity_logs = null;
$employee_stats = null;
if (isset($_GET['view_logs'])) {
    $target_id = (int)($_GET['view_logs'] ?? 0);
    if ($target_id <= 0) {
        $message = "<div class='alert alert-danger'>Invalid employee selected.</div>";
    } else {
        // Ensure employee exists (and fetch name for header)
        $emp_stmt = $conn->prepare("SELECT username FROM employees WHERE id = ? LIMIT 1");
        $emp_stmt->bind_param("i", $target_id);
        $emp_stmt->execute();
        $emp_row = $emp_stmt->get_result()->fetch_assoc();
        $emp_stmt->close();

        if (!$emp_row) {
            $message = "<div class='alert alert-danger'>Employee not found.</div>";
        } else {
    
            // Activity logs
            $stmt = $conn->prepare("SELECT * FROM activity_logs WHERE user_id = ? ORDER BY log_date DESC LIMIT 50");
            $stmt->bind_param("i", $target_id);
            $stmt->execute();
            $activity_logs = $stmt->get_result();
            $stmt->close();

            // Employee Performance Stats
            $stats_stmt = $conn->prepare("
                SELECT 
                    COUNT(id) as total_sales, 
                    SUM(total_amount) as total_revenue
                FROM sales 
                WHERE user_id = ?
            ");
            $stats_stmt->bind_param("i", $target_id);
            $stats_stmt->execute();
            $employee_stats = $stats_stmt->get_result()->fetch_assoc();
            $stats_stmt->close();

            $employee_stats['employee_name'] = $emp_row['username'];
        }
    }
}

?>

<div class="header-actions">
    <h1>Employee Management</h1>
    <p class="text-muted">Manage staff accounts and track their activities</p>
</div>

<?php echo $message; ?>

<div class="employee-grid">
    <div class="left-col">
        <?php if($_SESSION['role'] === 'admin'): ?>
        <div class="form-card">
            <h2>Add New Employee</h2>
            <form action="employees.php" method="post" autocomplete="off">
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" placeholder="Enter username" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                </div>
                <div class="form-group">
                    <label>Role</label>
                    <select name="role" class="form-control" required>
                        <option value="staff">Staff (Limited Access)</option>
                        <option value="manager">Manager (Manage Inventory)</option>
                        <option value="money_agent">Money Agent (Mobile Money)</option>
                        <option value="chairman">Chairman (Monitoring & Reports)</option>
                        <option value="admin">Admin (Full Access)</option>
                    </select>
                </div>
                <button type="submit" name="add_employee" class="btn btn-primary">Create Account</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $employees->fetch_assoc()): ?>
                    <tr class="employee-row <?php echo (isset($_GET['view_logs']) && (int)$_GET['view_logs'] === (int)$row['id']) ? 'row-active' : ''; ?>" data-employee-id="<?php echo (int)$row['id']; ?>">
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar"><?php echo strtoupper(substr($row['username'], 0, 1)); ?></div>
                                <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                            </div>
                        </td>
                        <td>
                            <?php
                                $role = (string)($row['role'] ?? 'staff');
                                $roleClass = 'role-staff';
                                if ($role === 'admin') $roleClass = 'role-admin';
                                elseif ($role === 'chairman') $roleClass = 'role-chairman';
                                elseif ($role === 'manager') $roleClass = 'role-manager';
                            ?>
                            <span class="role-badge <?php echo $roleClass; ?>">
                                <?php echo htmlspecialchars(ucfirst($role ?: 'staff')); ?>
                            </span>
                        </td>
                        <td>
                            <a href="employees.php?view_logs=<?php echo $row['id']; ?>" class="btn-icon" title="View Logs"><i class="fas fa-history"></i></a>
                            <?php if($_SESSION['role'] === 'admin'): ?>
                                <a href="employees.php?delete_id=<?php echo $row['id']; ?>" class="btn-icon btn-icon-danger" onclick="return confirm('Delete this account?')" title="Delete"><i class="fas fa-trash"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="right-col">
        <?php if ($activity_logs): ?>
            <div class="activity-card">
                <div class="card-header">
                    <h2>Monitoring: <?php echo htmlspecialchars($employee_stats['employee_name'] ?? 'Employee'); ?></h2>
                    <a href="employees.php" class="close-logs">&times;</a>
                </div>
                
                <!-- Performance Stats Bar -->
                <div class="performance-stats">
                    <div class="stat-item">
                        <span class="stat-label">Total Sales</span>
                        <span class="stat-value"><?php echo $employee_stats['total_sales'] ?? 0; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Revenue Generated</span>
                        <span class="stat-value">TSh <?php echo number_format($employee_stats['total_revenue'] ?? 0, 0); ?></span>
                    </div>
                </div>

                <div class="logs-list">
                    <h3>Recent Activities</h3>
                    <?php if ($activity_logs->num_rows > 0): ?>
                        <?php while($log = $activity_logs->fetch_assoc()): ?>
                            <div class="log-item">
                                <div class="log-dot"></div>
                                <div class="log-content">
                                    <div class="log-action"><?php echo htmlspecialchars($log['action']); ?></div>
                                    <div class="log-time"><?php echo date('M d, H:i', strtotime($log['log_date'])); ?></div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-logs">No activity recorded yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-user-clock"></i>
                <p>Select an employee to view their activity history</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .employee-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
    .user-cell { display: flex; align-items: center; gap: 12px; }
    .user-avatar { width: 32px; height: 32px; background: #e2e8f0; color: #475569; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
    .role-badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .role-admin { background: #fee2e2; color: #991b1b; }
    .role-chairman { background: #f3e8ff; color: #7e22ce; }
    .role-manager { background: #fef9c3; color: #854d0e; }
    .role-staff { background: #e0f2fe; color: #0369a1; }
    
    .btn-icon { color: #64748b; font-size: 16px; margin-right: 12px; transition: color 0.2s; }
    .btn-icon:hover { color: #007bff; }
    .btn-icon-danger:hover { color: #ef4444; }
    
    .row-active { background-color: #f8fafc; }
    .employee-row { cursor: pointer; }
    .employee-row:hover { background-color: #f8fafc; }
    
    .activity-card { background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); display: flex; flex-direction: column; max-height: calc(100vh - 160px); }
    .card-header { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .close-logs { font-size: 24px; color: #94a3b8; text-decoration: none; }
    
    .performance-stats { display: flex; gap: 24px; padding: 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
    .stat-item { flex: 1; display: flex; flex-direction: column; }
    .stat-label { font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
    .stat-value { font-size: 20px; color: #1e293b; font-weight: 700; margin-top: 4px; }
    
    .logs-list { padding: 20px; overflow-y: auto; }
    .logs-list h3 { font-size: 12px; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
    .log-item { display: flex; gap: 16px; margin-bottom: 20px; position: relative; }
    .log-item:not(:last-child):before { content: ''; position: absolute; left: 5px; top: 20px; bottom: -20px; width: 2px; background: #f1f5f9; }
    .log-dot { width: 12px; height: 12px; border-radius: 50%; background: #007bff; border: 2px solid white; box-shadow: 0 0 0 2px #ebf8ff; flex-shrink: 0; margin-top: 4px; z-index: 1; }
    .log-action { font-size: 14px; color: #1e293b; font-weight: 500; }
    .log-time { font-size: 12px; color: #94a3b8; margin-top: 2px; }
    
    .empty-state { height: 300px; display: flex; flex-direction: column; align-items: center; justify-content: center; background: #f8fafc; border: 2px dashed #e2e8f0; border-radius: 12px; color: #94a3b8; }
    .empty-state i { font-size: 48px; margin-bottom: 16px; }

    /* Mobile Responsiveness for Employees */
    @media (max-width: 992px) {
        .employee-grid {
            grid-template-columns: 1fr;
        }
        .activity-card {
            max-height: none;
            height: auto;
        }
    }

    @media (max-width: 576px) {
        .performance-stats {
            flex-direction: column;
            gap: 12px;
        }
        .user-cell strong {
            font-size: 13px;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('tr.employee-row').forEach(function (row) {
        row.addEventListener('click', function (e) {
            // Don't hijack clicks on action buttons/links
            if (e.target.closest('a, button')) return;
            var id = row.getAttribute('data-employee-id');
            if (id) window.location.href = 'employees.php?view_logs=' + encodeURIComponent(id);
        });
    });
});
</script>

<?php
$conn->close();
include 'footer.php'; 
?>