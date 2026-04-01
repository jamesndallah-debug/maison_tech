<?php
include 'header.php';
include 'dp.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<div class='alert alert-danger'>Access Denied. Admins only.</div>";
    include 'footer.php';
    exit;
}

$message = '';

// Fetch all settings
$settings = [];
$settings_res = $conn->query("SELECT setting_key, setting_value FROM settings");
while ($row = $settings_res->fetch_assoc()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Get current money agent balances permissions
$current_balances_permissions = isset($settings['money_agent_balances_permission']) ? explode(',', $settings['money_agent_balances_permission']) : [];

// Fetch money agents for permission management
$money_agents = $conn->query("SELECT id, username FROM employees WHERE role = 'money_agent' ORDER BY username ASC");

function mt_clean_str($v) {
    return trim((string)$v);
}

// Update own account (Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_self'])) {
    $current_password = (string)($_POST['current_password'] ?? '');
    $new_username = mt_clean_str($_POST['new_username'] ?? '');
    $new_password = (string)($_POST['new_password'] ?? '');
    $confirm_password = (string)($_POST['confirm_password'] ?? '');

    // Fetch current admin record
    $stmt = $conn->prepare("SELECT id, username, password FROM employees WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$admin || !password_verify($current_password, $admin['password'])) {
        $message = "<div class='alert alert-danger'>Current password is incorrect.</div>";
    } else {
        $do_username = ($new_username !== '' && $new_username !== $admin['username']);
        $do_password = ($new_password !== '');

        if (!$do_username && !$do_password) {
            $message = "<div class='alert alert-danger'>Nothing to update.</div>";
        } elseif ($do_password && $new_password !== $confirm_password) {
            $message = "<div class='alert alert-danger'>New password confirmation does not match.</div>";
        } else {
            // Username uniqueness check
            if ($do_username) {
                $u = $conn->prepare("SELECT id FROM employees WHERE username = ? AND id <> ? LIMIT 1");
                $u->bind_param("si", $new_username, $_SESSION['user_id']);
                $u->execute();
                $exists = $u->get_result()->num_rows > 0;
                $u->close();
                if ($exists) {
                    $message = "<div class='alert alert-danger'>That username is already taken.</div>";
                }
            }

            if ($message === '') {
                $conn->begin_transaction();
                try {
                    $changes = [];

                    if ($do_username) {
                        $up = $conn->prepare("UPDATE employees SET username = ? WHERE id = ?");
                        $up->bind_param("si", $new_username, $_SESSION['user_id']);
                        $up->execute();
                        $up->close();
                        $_SESSION['username'] = $new_username;
                        $changes[] = "username";
                    }

                    if ($do_password) {
                        $hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $up = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
                        $up->bind_param("si", $hash, $_SESSION['user_id']);
                        $up->execute();
                        $up->close();
                        $changes[] = "password";
                    }

                    $action = "Updated own account settings (" . implode(', ', $changes) . ")";
                    $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                    $log->bind_param("is", $_SESSION['user_id'], $action);
                    $log->execute();
                    $log->close();

                    $conn->commit();
                    $message = "<div class='alert alert-success'>Account updated successfully.</div>";
                } catch (Throwable $e) {
                    $conn->rollback();
                    $message = "<div class='alert alert-danger'>Update failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
    }
}

// Reset employee password (Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_employee_password'])) {
    $target_id = (int)($_POST['employee_id'] ?? 0);
    $new_password = (string)($_POST['employee_new_password'] ?? '');

    if ($target_id <= 0 || $new_password === '') {
        $message = "<div class='alert alert-danger'>Select an employee and enter a new password.</div>";
    } elseif ($target_id === (int)$_SESSION['user_id']) {
        $message = "<div class='alert alert-danger'>Use the account form to change your own password.</div>";
    } else {
        // Fetch employee for logging
        $u = $conn->prepare("SELECT username, role FROM employees WHERE id = ? LIMIT 1");
        $u->bind_param("i", $target_id);
        $u->execute();
        $emp = $u->get_result()->fetch_assoc();
        $u->close();

        if (!$emp) {
            $message = "<div class='alert alert-danger'>Employee not found.</div>";
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE employees SET password = ? WHERE id = ?");
            $up->bind_param("si", $hash, $target_id);
            $up->execute();
            $up->close();

            $action = "Reset password for employee: " . htmlspecialchars($emp['username']) . " (role: " . htmlspecialchars($emp['role']) . ")";
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
            $log->bind_param("is", $_SESSION['user_id'], $action);
            $log->execute();
            $log->close();

            $message = "<div class='alert alert-success'>Password reset successfully for " . htmlspecialchars($emp['username']) . ".</div>";
        }
    }
}

// Manage Money Agent Balances Permission (Admin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balances_permission'])) {
    $allowed_agent_ids = isset($_POST['allowed_agents']) ? $_POST['allowed_agents'] : [];
    
    // Get current permissions for comparison
    $current_check = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'money_agent_balances_permission'");
    $current_permissions = [];
    if ($current_check && $current_check->num_rows > 0) {
        $current_value = $current_check->fetch_assoc()['setting_value'];
        if (!empty($current_value)) {
            $current_permissions = explode(',', $current_value);
        }
    }
    
    $permission_value = implode(',', $allowed_agent_ids);
    
    // Check if setting exists
    $check = $conn->query("SELECT id FROM settings WHERE setting_key = 'money_agent_balances_permission'");
    
    if ($check->num_rows > 0) {
        // Update existing setting
        $stmt = $conn->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = 'money_agent_balances_permission'");
        $stmt->bind_param("s", $permission_value);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert new setting
        $setting_key = 'money_agent_balances_permission';
        $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)");
        $stmt->bind_param("ss", $setting_key, $permission_value);
        $stmt->execute();
        $stmt->close();
    }
    
    // Calculate changes for detailed logging
    $newly_granted = array_diff($allowed_agent_ids, $current_permissions);
    $newly_revoked = array_diff($current_permissions, $allowed_agent_ids);
    
    // Log detailed actions
    $action_parts = [];
    if (count($newly_granted) > 0) {
        $action_parts[] = "Granted access to " . count($newly_granted) . " agent(s)";
    }
    if (count($newly_revoked) > 0) {
        $action_parts[] = "Revoked access from " . count($newly_revoked) . " agent(s)";
    }
    if (empty($action_parts)) {
        $action_parts[] = "No changes made";
    }
    
    $action = "Money Agent Balances - " . implode(', ', $action_parts);
    $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
    $log->bind_param("is", $_SESSION['user_id'], $action);
    $log->execute();
    $log->close();
    
    // Prepare success message with details
    $message_parts = [];
    if (count($newly_granted) > 0) {
        $message_parts[] = "✅ Access granted to " . count($newly_granted) . " agent(s)";
    }
    if (count($newly_revoked) > 0) {
        $message_parts[] = "🔒 Access revoked from " . count($newly_revoked) . " agent(s)";
    }
    if (empty($message_parts)) {
        $message_parts[] = "✓ No changes were necessary";
    }
    
    $message = "<div class='alert alert-success'><i class='fas fa-check-circle'></i> <strong>Permissions Updated Successfully!</strong><br>" . implode(' • ', $message_parts) . "</div>";
}

// Fetch employees list (for reset + monitoring)
$employees = $conn->query("SELECT id, username, role, created_at, password FROM employees ORDER BY username ASC");
?>

<div class="header-actions">
    <div>
        <h1><i class="fas fa-cog"></i> Admin Settings</h1>
        <p class="text-muted">Manage system settings, permissions, and security configurations</p>
    </div>
</div>

<?php echo $message; ?>

<div class="settings-grid-modern">
    <!-- Account Management Card -->
    <div class="settings-card account-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="header-text">
                <h2>My Account</h2>
                <p>Update your username and password</p>
            </div>
        </div>
        <div class="card-body">
            <form action="admin_settings.php" method="post" class="modern-form" autocomplete="off">
                <div class="form-section">
                    <label class="section-label">
                        <i class="fas fa-user"></i> Account Information
                    </label>
                    <div class="form-group">
                        <label>New Username (optional)</label>
                        <input type="text" name="new_username" class="form-control-modern" placeholder="Leave blank to keep current username">
                    </div>
                </div>
                
                <div class="form-section">
                    <label class="section-label">
                        <i class="fas fa-lock"></i> Password Security
                    </label>
                    <div class="form-row-modern">
                        <div class="form-group-modern">
                            <label>New Password (optional)</label>
                            <input type="password" name="new_password" class="form-control-modern" placeholder="••••••••">
                        </div>
                        <div class="form-group-modern">
                            <label>Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control-modern" placeholder="••••••••">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <div class="form-group-modern">
                        <label>Current Password (required)</label>
                        <input type="password" name="current_password" class="form-control-modern" required placeholder="Enter current password">
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_self" class="btn-modern btn-primary-modern">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                </div>
        </form>
        </form>
        </div>
    </div>

    <!-- Permission Management Card -->
    <div class="settings-card permission-card">
        <div class="card-header">
            <div class="header-icon">
                <i class="fas fa-user-lock"></i>
            </div>
            <div class="header-text">
                <h2>Money Agent Balances Access</h2>
                <p>Control which agents can access balances page</p>
            </div>
        </div>
        <div class="card-body">
            <div class="info-banner">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Permission Control:</strong> By default, Money Agents cannot access the Balances page. Select agents below to grant them access. You can revoke access at any time.
                </div>
            </div>
            
            <?php if (!empty($current_balances_permissions) && is_array($current_balances_permissions)): ?>
            <div class="current-permissions-banner" style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 16px; border-radius: 8px; margin-bottom: 20px;">
                <i class="fas fa-users" style="color: #0284c7; margin-right: 8px;"></i>
                <strong style="color: #0c4a6e;">Currently Authorized:</strong>
                <span style="color: #0369a1; margin-left: 8px;">
                    <?php
                    $authorized_names = [];
                    foreach ($current_balances_permissions as $agent_id) {
                        $name_query = $conn->prepare("SELECT username FROM employees WHERE id = ? AND role = 'money_agent'");
                        $name_query->bind_param("i", $agent_id);
                        $name_query->execute();
                        $result = $name_query->get_result();
                        if ($result->num_rows > 0) {
                            $authorized_names[] = $result->fetch_assoc()['username'];
                        }
                        $name_query->close();
                    }
                    echo !empty($authorized_names) ? htmlspecialchars(implode(', ', $authorized_names)) : 'None';
                    ?>
                </span>
            </div>
            <?php endif; ?>
            
            <form action="admin_settings.php" method="post" class="modern-form" autocomplete="off">
                <div class="form-section">
                    <label class="section-label">
                        <i class="fas fa-users-cog"></i> Grant Access To
                    </label>
                    <div class="form-group">
                        <select name="allowed_agents[]" class="form-control-modern multi-select" multiple size="6">
                    <?php
                    $money_agents_for_select = $conn->query("SELECT id, username FROM employees WHERE role = 'money_agent' ORDER BY username ASC");
                    while($agent = $money_agents_for_select->fetch_assoc()):
                        $is_selected = in_array((string)$agent['id'], $current_balances_permissions);
                    ?>
                        <option value="<?php echo (int)$agent['id']; ?>" <?php echo $is_selected ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($agent['username']); ?>
                        </option>
                    <?php endwhile; ?>
                        </select>
                        <div class="help-text">
                            <i class="fas fa-keyboard"></i>
                            Hold <strong>Ctrl</strong> (Windows) or <strong>Cmd</strong> (Mac) to select multiple agents
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="update_balances_permission" class="btn-modern btn-success-modern">
                        <i class="fas fa-check-circle"></i> Update Permissions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Password Reset Card -->
<div class="settings-card full-width password-card">
    <div class="card-header">
        <div class="header-icon">
            <i class="fas fa-key"></i>
        </div>
        <div class="header-text">
            <h2>Employee Password Reset</h2>
            <p>Reset passwords for other employees (not yourself)</p>
        </div>
    </div>
    <div class="card-body">
        <div class="warning-banner">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Important:</strong> Use this feature only to reset passwords for other employees. To change your own password, use the "My Account" section above.
            </div>
        </div>
        
        <form action="admin_settings.php" method="post" class="modern-form" autocomplete="off">
            <div class="form-row-modern">
                <div class="form-group-modern">
                    <label>Select Employee</label>
                    <select name="employee_id" class="form-control-modern" required>
                        <option value="">-- Choose an employee --</option>
                        <?php
                        $employees_for_reset = $conn->query("SELECT id, username, role FROM employees WHERE id != " . (int)$_SESSION['user_id'] . " ORDER BY username ASC");
                        if ($employees_for_reset && $employees_for_reset->num_rows > 0):
                            while($emp = $employees_for_reset->fetch_assoc()):
                        ?>
                            <option value="<?php echo (int)$emp['id']; ?>">
                                <?php echo htmlspecialchars($emp['username']); ?> (<?php echo htmlspecialchars($emp['role']); ?>)
                            </option>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <option disabled>No other employees found</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group-modern">
                    <label>New Password</label>
                    <input type="password" name="employee_new_password" class="form-control-modern" required placeholder="Enter new password">
                </div>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="reset_employee_password" class="btn-modern btn-danger-modern">
                    <i class="fas fa-redo-alt"></i> Reset Password
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Security Monitoring Card -->
<div class="settings-card full-width security-card">
    <div class="card-header">
        <div class="header-icon">
            <i class="fas fa-shield-alt"></i>
        </div>
        <div class="header-text">
            <h2>Password Security Monitoring</h2>
            <p>View password hash status for all employees</p>
        </div>
    </div>
    <div class="card-body">
        <div class="info-banner">
            <i class="fas fa-lock"></i>
            <div>
                <strong>Security Note:</strong> Passwords are stored as secure hashes. This table shows whether each account has a password set and if the hash algorithm needs updating.
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="modern-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Username</th>
                        <th><i class="fas fa-user-tag"></i> Role</th>
                        <th><i class="fas fa-check-circle"></i> Has Password</th>
                        <th><i class="fas fa-sync-alt"></i> Needs Rehash</th>
                        <th><i class="fas fa-calendar-alt"></i> Account Created</th>
                    </tr>
                </thead>
                <tbody>
                <?php while($row = $employees->fetch_assoc()): ?>
                    <?php
                        $hash = (string)($row['password'] ?? '');
                        $has = $hash !== '';
                        $needs = $has ? password_needs_rehash($hash, PASSWORD_DEFAULT) : false;
                    ?>
                        <tr class="<?php echo (!$has || $needs) ? 'table-warning' : ''; ?>">
                            <td>
                                <strong><?php echo htmlspecialchars($row['username']); ?></strong>
                            </td>
                            <td>
                                <span class="role-badge"><?php echo htmlspecialchars($row['role']); ?></span>
                            </td>
                            <td>
                                <?php if ($has): ?>
                                    <span class="status-badge status-success">
                                        <i class="fas fa-check"></i> Yes
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-danger">
                                        <i class="fas fa-times"></i> No
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!$has): ?>
                                    <span class="status-badge status-neutral">
                                        <i class="fas fa-minus"></i> N/A
                                    </span>
                                <?php elseif ($needs): ?>
                                    <span class="status-badge status-warning">
                                        <i class="fas fa-exclamation-triangle"></i> Yes
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-success">
                                        <i class="fas fa-check"></i> No
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted">
                                <?php echo !empty($row['created_at']) ? date('M d, Y', strtotime($row['created_at'])) : '<em>Unknown</em>'; ?>
                            </td>
                        </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        </div>
        
        <?php 
        // Check if there are any security issues
        $employees->data_seek(0);
        $has_issues = false;
        while($row = $employees->fetch_assoc()): 
            $hash = (string)($row['password'] ?? '');
            $has = $hash !== '';
            $needs = $has ? password_needs_rehash($hash, PASSWORD_DEFAULT) : false;
            if (!$has || $needs) $has_issues = true;
        endwhile;
        
        if ($has_issues): 
        ?>
        <div class="alert-banner alert-warning">
            <i class="fas fa-exclamation-circle"></i>
            <div>
                <strong>Attention Needed:</strong> Some accounts have security issues (missing passwords or outdated hash algorithms). Consider resetting passwords for affected accounts.
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Modern Settings Layout */
    .header-actions {
        margin-bottom: 32px;
        padding-bottom: 20px;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .header-actions h1 {
        font-size: 28px;
        color: #1e293b;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .header-actions h1 i {
        color: #3b82f6;
    }
    
    .text-muted {
        color: #64748b;
        font-size: 14px;
        margin: 0;
    }
    
    /* Modern Settings Grid */
    .settings-grid-modern {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
        gap: 24px;
        margin-bottom: 24px;
    }
    
    @media (max-width: 992px) {
        .settings-grid-modern {
            grid-template-columns: 1fr;
        }
    }
    
    /* Settings Cards */
    .settings-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07), 0 1px 3px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #f1f5f9;
    }
    
    .settings-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1), 0 6px 6px rgba(0, 0, 0, 0.06);
    }
    
    .full-width {
        grid-column: 1 / -1;
    }
    
    /* Card Header */
    .card-header {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 24px;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    
    .header-icon {
        width: 56px;
        height: 56px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .account-card .header-icon { background: linear-gradient(135deg, #dbeafe, #bfdbfe); color: #2563eb; }
    .permission-card .header-icon { background: linear-gradient(135deg, #dcfce7, #bbf7d0); color: #16a34a; }
    .password-card .header-icon { background: linear-gradient(135deg, #fef3c7, #fde68a); color: #d97706; }
    .security-card .header-icon { background: linear-gradient(135deg, #e0e7ff, #c7d2fe); color: #4f46e5; }
    
    .header-text h2 {
        margin: 0 0 4px 0;
        font-size: 18px;
        color: #1e293b;
        font-weight: 700;
    }
    
    .header-text p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }
    
    /* Card Body */
    .card-body {
        padding: 24px;
    }
    
    /* Info Banners */
    .info-banner,
    .warning-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 13px;
        line-height: 1.6;
    }
    
    .info-banner {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        color: #1e40af;
    }
    
    .warning-banner {
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        color: #92400e;
    }
    
    .info-banner i,
    .warning-banner i {
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    /* Forms */
    .modern-form {
        margin-top: 8px;
    }
    
    .form-section {
        margin-bottom: 24px;
        padding-bottom: 24px;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .form-section:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    
    .section-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 16px;
    }
    
    .section-label i {
        color: #3b82f6;
    }
    
    .form-group {
        margin-bottom: 16px;
    }
    
    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        margin-bottom: 8px;
    }
    
    .form-control-modern {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        font-size: 14px;
        transition: all 0.2s;
        background: white;
        box-sizing: border-box;
    }
    
    .form-control-modern:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
    }
    
    .form-control-modern::placeholder {
        color: #94a3b8;
    }
    
    .multi-select {
        min-height: 180px;
        cursor: pointer;
    }
    
    .form-row-modern {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }
    
    @media (max-width: 768px) {
        .form-row-modern {
            grid-template-columns: 1fr;
        }
    }
    
    .help-text {
        font-size: 12px;
        color: #64748b;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .help-text i {
        color: #3b82f6;
    }
    
    /* Form Actions */
    .form-actions {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #f1f5f9;
    }
    
    /* Modern Buttons */
    .btn-modern {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }
    
    .btn-primary-modern {
        background: linear-gradient(135deg, #3b82f6, #2563eb);
        color: white;
        box-shadow: 0 4px 6px rgba(59, 130, 246, 0.3);
    }
    
    .btn-primary-modern:hover {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(59, 130, 246, 0.4);
    }
    
    .btn-success-modern {
        background: linear-gradient(135deg, #10b981, #059669);
        color: white;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.3);
    }
    
    .btn-success-modern:hover {
        background: linear-gradient(135deg, #059669, #047857);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(16, 185, 129, 0.4);
    }
    
    .btn-danger-modern {
        background: linear-gradient(135deg, #ef4444, #dc2626);
        color: white;
        box-shadow: 0 4px 6px rgba(239, 68, 68, 0.3);
    }
    
    .btn-danger-modern:hover {
        background: linear-gradient(135deg, #dc2626, #b91c1c);
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(239, 68, 68, 0.4);
    }
    
    /* Modern Table */
    .modern-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        margin-top: 16px;
    }
    
    .modern-table thead th {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        color: #475569;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 16px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
    }
    
    .modern-table thead th i {
        margin-right: 6px;
        color: #3b82f6;
    }
    
    .modern-table tbody td {
        padding: 16px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
    }
    
    .modern-table tbody tr:hover {
        background: #f8fafc;
    }
    
    .table-warning {
        background: #fffbeb !important;
    }
    
    .table-warning:hover {
        background: #fef3c7 !important;
    }
    
    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-success {
        background: #dcfce7;
        color: #166534;
    }
    
    .status-warning {
        background: #fef3c7;
        color: #92400e;
    }
    
    .status-danger {
        background: #fee2e2;
        color: #991b1b;
    }
    
    .status-neutral {
        background: #f1f5f9;
        color: #475569;
    }
    
    /* Role Badge */
    .role-badge {
        display: inline-block;
        padding: 4px 10px;
        background: #e0e7ff;
        color: #3730a3;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    
    /* Alert Banner */
    .alert-banner {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 16px;
        border-radius: 10px;
        margin-top: 20px;
    }
    
    .alert-warning {
        background: #fffbeb;
        border-left: 4px solid #f59e0b;
        color: #92400e;
    }
    
    .alert-banner i {
        font-size: 20px;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    /* Responsive Design */
    @media (max-width: 768px) {
        .settings-grid-modern {
            grid-template-columns: 1fr;
        }
        
        .card-header {
            padding: 16px;
        }
        
        .card-body {
            padding: 16px;
        }
        
        .header-icon {
            width: 48px;
            height: 48px;
            font-size: 20px;
        }
        
        .header-text h2 {
            font-size: 16px;
        }
        
        .modern-table {
            font-size: 12px;
        }
        
        .modern-table thead th,
        .modern-table tbody td {
            padding: 12px 8px;
        }
        
        .btn-modern {
            width: 100%;
            justify-content: center;
        }
    }
</style>

<?php
$conn->close();
include 'footer.php';
?>

