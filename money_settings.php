<?php
include 'header.php';
include 'dp.php';

$is_admin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
$is_chairman = isset($_SESSION['role']) && $_SESSION['role'] === 'chairman';

// Check if user is admin or chairman (always allowed)
if ($is_admin || $is_chairman) {
    $allowed = true;
} else {
    // For money agents, check if admin has granted permission
    $allowed = false;
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'money_agent') {
        $perm_check = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'money_agent_balances_permission'");
        if ($perm_check && $perm_check->num_rows > 0) {
            $perm_value = $perm_check->fetch_assoc()['setting_value'];
            $allowed_agents = explode(',', $perm_value);
            $allowed = in_array((string)$_SESSION['user_id'], $allowed_agents);
        }
    }
}

if (!$allowed) {
    echo "<div class='alert alert-danger'>Access Denied. You do not have permission to access this page.</div>";
    include 'footer.php';
    exit;
}

$message = '';

// Allow Admin, Chairman, and permitted Money Agents to update balances
$can_edit = $is_admin || $is_chairman || ($allowed && isset($_SESSION['role']) && $_SESSION['role'] === 'money_agent');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_opening']) && $can_edit) {
    $opening_cash = (float)($_POST['opening_cash'] ?? 0);
    
    $display_providers = [
        'mpesa' => 'M-Pesa',
        'mixx_by_yass' => 'Mixx by Yass',
        'airtelmoney' => 'Airtel Money',
        'halopesa' => 'HaloPesa',
        'azam_pesa' => 'Azam Pesa',
        'bank_agency' => 'Bank Agency',
        'kingamuzi' => 'Kingamuzi (TV)',
        'government' => 'Govt Payments',
        'other' => 'Other'
    ];

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE money_cash_opening SET opening_cash = ? WHERE id = 1");
        $stmt->bind_param("d", $opening_cash);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("UPDATE money_float_opening SET opening_float = ? WHERE provider = ?");
        foreach ($display_providers as $provider => $label) {
            $val = (float)($_POST['float_' . $provider] ?? 0);
            $stmt->bind_param("ds", $val, $provider);
            $stmt->execute();
        }
        $stmt->close();

        $action = "Updated mobile money opening balances (cash + float per provider)";
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $_SESSION['user_id'], $action);
        $log->execute();
        $log->close();

        $conn->commit();
        $message = "<div class='alert alert-success'>Opening balances updated successfully.</div>";
    } catch (Throwable $e) {
        $conn->rollback();
        $message = "<div class='alert alert-danger'>Update failed: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

$opening_cash_row = $conn->query("SELECT opening_cash, updated_at FROM money_cash_opening WHERE id = 1")->fetch_assoc();
$opening_cash = $opening_cash_row['opening_cash'] ?? 0;

$float_rows = $conn->query("SELECT provider, opening_float, updated_at FROM money_float_opening ORDER BY provider ASC");
$opening_float = [];
while($r = $float_rows->fetch_assoc()) {
    $opening_float[$r['provider']] = $r['opening_float'];
}
?>

<div class="header-actions">
    <div>
        <h1>Mobile Money Balances</h1>
        <p class="text-muted">Opening balances for cash and float tracking</p>
    </div>
    <div class="actions">
        <a href="money_dashboard.php" class="btn btn-secondary">&larr; Back to Dashboard</a>
    </div>
</div>

<?php echo $message; ?>

<div class="card">
    <h2><i class="fas fa-sliders-h"></i> Opening Balances</h2>
    <?php if (!$can_edit): ?>
    <div class="alert alert-info" style="margin-bottom: 20px;">
        <i class="fas fa-info-circle"></i>
        <strong>Read-Only Access:</strong> You can view these balances but cannot edit them. Contact Admin to request editing permission.
    </div>
    <?php endif; ?>
    <form action="money_settings.php" method="post" autocomplete="off">
        <div class="grid">
            <div class="form-group">
                <label>Opening Cash (TSh)</label>
                <input <?php echo $can_edit ? '' : 'disabled'; ?> type="number" step="0.01" name="opening_cash" class="form-control" value="<?php echo htmlspecialchars((string)$opening_cash); ?>">
            </div>
            <?php 
            $display_providers = [
                'mpesa' => 'M-Pesa',
                'mixx_by_yass' => 'Mixx by Yass',
                'airtelmoney' => 'Airtel Money',
                'halopesa' => 'HaloPesa',
                'azam_pesa' => 'Azam Pesa',
                'bank_agency' => 'Bank Agency',
                'kingamuzi' => 'Kingamuzi (TV)',
                'government' => 'Govt Payments',
                'other' => 'Other'
            ];
            foreach($display_providers as $p => $label): 
            ?>
                <div class="form-group">
                    <label>Opening Float - <?php echo htmlspecialchars($label); ?></label>
                    <input <?php echo $can_edit ? '' : 'disabled'; ?> type="number" step="0.01" name="float_<?php echo $p; ?>" class="form-control" value="<?php echo htmlspecialchars((string)($opening_float[$p] ?? 0)); ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($can_edit): ?>
            <button type="submit" name="update_opening" class="btn btn-primary">
                <i class="fas fa-save"></i> Save Opening Balances
            </button>
        <?php else: ?>
            <div class="alert alert-warning" style="margin-top: 12px;">
                <i class="fas fa-lock"></i> <strong>Read-Only:</strong> You need Admin permission to edit these balances.
            </div>
        <?php endif; ?>
    </form>
</div>

<style>
    .header-actions { display:flex; justify-content:space-between; align-items:flex-end; gap: 16px; margin-bottom: 24px; }
    .text-muted { color:#64748b; font-size:14px; margin-top: 6px; }
    .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .card { background:#fff; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
    .card h2 { margin:0 0 14px; font-size:16px; color:#0f172a; display:flex; align-items:center; gap: 10px; }
    .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .form-group label { display:block; font-size: 12px; font-weight: 800; color:#334155; margin-bottom: 6px; }
    @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } }
</style>

<?php
$conn->close();
include 'footer.php';
?>

