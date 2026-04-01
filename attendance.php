<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

// Admin and Chairman should only monitor, not sign in/out
if (isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'chairman'])) {
    header('Location: manage_attendance.php');
    exit;
}

include 'dp.php';
include 'header.php';

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d");
$message = '';

// Get Company IP from settings
$ip_res = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'company_ip'");
$company_ip = $ip_res->fetch_assoc()['setting_value'];

// Get user's real IP address
$user_ip = $_SERVER['REMOTE_ADDR'];

// Check if user is on the company network
$is_on_premise = (empty($company_ip) || $user_ip === $company_ip);

// Handle Sign In
if (isset($_POST['sign_in'])) {
    if ($is_on_premise) {
        $stmt = $conn->prepare("INSERT INTO attendance (employee_id, sign_in_time, date, ip_address) VALUES (?, NOW(), ?, ?)");
        $stmt->bind_param("iss", $user_id, $today, $user_ip);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Successfully signed in at " . date("h:i A") . ".</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>You can only sign in from the company network.</div>";
    }
}

// Handle Sign Out
if (isset($_POST['sign_out'])) {
    if ($is_on_premise) {
        $stmt = $conn->prepare("UPDATE attendance SET sign_out_time = NOW() WHERE employee_id = ? AND date = ? AND sign_out_time IS NULL");
        $stmt->bind_param("is", $user_id, $today);
        if ($stmt->execute()) {
            $message = "<div class='alert alert-success'>Successfully signed out at " . date("h:i A") . ".</div>";
        } else {
            $message = "<div class='alert alert-danger'>Error: " . $stmt->error . "</div>";
        }
        $stmt->close();
    } else {
        $message = "<div class='alert alert-danger'>You can only sign out from the company network.</div>";
    }
}

// Get today's attendance record for the user
$stmt = $conn->prepare("SELECT * FROM attendance WHERE employee_id = ? AND date = ?");
$stmt->bind_param("is", $user_id, $today);
$stmt->execute();
$attendance_today = $stmt->get_result()->fetch_assoc();
$stmt->close();

$has_signed_in = ($attendance_today !== null);
$has_signed_out = ($has_signed_in && $attendance_today['sign_out_time'] !== null);

?>

<div class="header-actions">
    <h1><i class="fas fa-user-clock"></i> Daily Attendance</h1>
    <p class="text-muted">Your IP: <?php echo $user_ip; ?> | Company IP: <?php echo empty($company_ip) ? 'Not Set' : $company_ip; ?></p>
</div>

<?php echo $message; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card form-card text-center p-4">
            <div class="card-body">
                <h2 class="mb-3">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <p class="text-muted mb-4">Today is <?php echo date("l, F j, Y"); ?></p>
                
                <?php if (!$is_on_premise): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        You are not connected to the company network. Attendance recording is disabled.
                    </div>
                <?php else: ?>
                    <form method="POST">
                        <?php if (!$has_signed_in): ?>
                            <button type="submit" name="sign_in" class="btn btn-primary btn-lg w-100">Sign In</button>
                        <?php elseif (!$has_signed_out): ?>
                            <p>You signed in at: <strong><?php echo date("h:i A", strtotime($attendance_today['sign_in_time'])); ?></strong></p>
                            <button type="submit" name="sign_out" class="btn btn-danger btn-lg w-100">Sign Out</button>
                        <?php else: ?>
                            <div class="alert alert-success">
                                You have completed your attendance for today.<br>
                                Sign In: <strong><?php echo date("h:i A", strtotime($attendance_today['sign_in_time'])); ?></strong><br>
                                Sign Out: <strong><?php echo date("h:i A", strtotime($attendance_today['sign_out_time'])); ?></strong>
                            </div>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>