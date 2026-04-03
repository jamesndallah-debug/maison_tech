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
// Get the current page name for active link styling
$current_page = basename($_SERVER['PHP_SELF']);

// Check for low stock items for the badge
$low_stock_count = 0;
if (isset($conn)) {
    try {
        $res = $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity < 10");
        if ($res) {
            $low_stock_count = $res->fetch_assoc()['count'];
        }
    } catch (Throwable $e) {
        // If products table doesn't exist yet, keep badge at 0.
        $low_stock_count = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison Tech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 260px;
            background-color: #1c2536;
            color: white;
            height: 100vh;
            padding: 24px 16px;
            position: fixed;
            z-index: 1000;
            box-shadow: 4px 0 10px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease;
            overflow-y: auto;
        }
        /* Custom scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }
        .sidebar::-webkit-scrollbar-track {
            background: #1c2536;
        }
        .sidebar::-webkit-scrollbar-thumb {
            background: #2d3748;
            border-radius: 10px;
        }
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: #4a5568;
        }
        .sidebar-brand {
            font-size: 22px;
            font-weight: 800;
            text-align: left;
            padding: 0 12px;
            margin-bottom: 40px;
            color: white;
            letter-spacing: -0.5px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .close-sidebar {
            display: none;
            cursor: pointer;
            font-size: 20px;
        }
        .sidebar-nav {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .nav-item {
            margin-bottom: 4px;
        }
        .nav-link {
            color: #a9b4c9;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            padding: 12px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .nav-link i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
            font-size: 16px;
        }
        .nav-link:hover, .nav-link.active {
            background-color: rgba(255, 255, 255, 0.08);
            color: white;
        }
        .sidebar-badge {
            background-color: #ef4444;
            color: white;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            margin-left: auto;
        }
        .main-content {
            margin-left: 260px;
            padding: 32px 40px;
            width: calc(100% - 260px);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .mobile-toggle {
            display: none;
            font-size: 24px;
            cursor: pointer;
            color: #1c2536;
        }
        .header .user-info {
            display: flex;
            align-items: center;
            margin-left: auto;
        }
        .header .user-info span {
            margin-right: 15px;
            font-weight: 500;
            color: #4a5568;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            display: inline-block;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #007bff;
            color: white;
        }
        .btn-primary:hover {
            background-color: #0056b3;
        }
        .btn-danger {
            background-color: #e53e3e;
            color: white;
        }
        .btn-danger:hover {
            background-color: #c53030;
        }
        .table-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow-x: auto;
            margin-top: 20px;
            -webkit-overflow-scrolling: touch;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        th {
            background-color: #f8fafc;
            color: #64748b;
            text-transform: uppercase;
            font-size: 12px;
            font-weight: 600;
            padding: 12px 16px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #e2e8f0;
            color: #334155;
            font-size: 14px;
        }
        .form-card {
            background: white;
            padding: 24px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 24px;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            font-weight: 500;
            color: #4a5568;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .alert-success {
            background-color: #f0fff4;
            color: #276749;
            border: 1px solid #c6f6d5;
        }
        .alert-danger {
            background-color: #fff5f5;
            color: #9b2c2c;
            border: 1px solid #fed7d7;
        }

        /* Mobile Responsiveness */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 20px;
            }
            .mobile-toggle {
                display: block;
            }
            .close-sidebar {
                display: block;
            }
            .header .user-info span {
                display: none;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 15px;
            }
            .header {
                margin-bottom: 20px;
            }
            .form-card {
                padding: 15px;
            }
            .btn {
                padding: 6px 12px;
                font-size: 12px;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div style="display: flex; align-items: center; gap: 10px;">
                <img src="images/maison-tech-logo.png.jpeg" alt="Logo" style="width: 40px; height: auto;">
                <span>Maison Tech</span>
            </div>
            <i class="fas fa-times close-sidebar" onclick="toggleSidebar()"></i>
        </div>
        <ul class="sidebar-nav">
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php if($current_page == 'dashboard.php') echo 'active'; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <?php if($_SESSION['role'] === 'money_agent'): ?>
                <li class="nav-item">
                    <a href="money_dashboard.php" class="nav-link <?php if($current_page == 'money_dashboard.php') echo 'active'; ?>">
                        <i class="fas fa-wallet"></i> Mobile Money
                    </a>
                </li>
                <li class="nav-item">
                    <a href="money_transactions.php" class="nav-link <?php if($current_page == 'money_transactions.php') echo 'active'; ?>">
                        <i class="fas fa-receipt"></i> Transactions
                    </a>
                </li>
                <li class="nav-item">
                    <a href="payments.php" class="nav-link <?php if($current_page == 'payments.php') echo 'active'; ?>">
                        <i class="fas fa-credit-card"></i> Bill Payments
                    </a>
                </li>
                <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
                <li class="nav-item">
                    <a href="bank_agency_report.php" class="nav-link <?php if($current_page == 'bank_agency_report.php') echo 'active'; ?>">
                        <i class="fas fa-university"></i> Bank Agency Report
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="money_settings.php" class="nav-link <?php if($current_page == 'money_settings.php') echo 'active'; ?>">
                        <i class="fas fa-sliders-h"></i> Balances
                    </a>
                </li>
                <li class="nav-item">
                    <a href="money_closing.php" class="nav-link <?php if($current_page == 'money_closing.php') echo 'active'; ?>">
                        <i class="fas fa-clipboard-check"></i> Daily Closing
                    </a>
                </li>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link <?php if($current_page == 'attendance.php') echo 'active'; ?>">
                        <i class="fas fa-clock"></i> Attendance
                    </a>
                </li>
                <li class="nav-item">
                    <a href="expenses.php" class="nav-link <?php if($current_page == 'expenses.php') echo 'active'; ?>">
                        <i class="fas fa-receipt"></i> Expenses
                    </a>
                </li>
            <?php else: ?>
            <li class="nav-item">
                <a href="products.php" class="nav-link <?php if($current_page == 'products.php') echo 'active'; ?>">
                    <i class="fas fa-box"></i> Products
                    <?php if($low_stock_count > 0): ?>
                        <span class="sidebar-badge"><?php echo $low_stock_count; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
            <li class="nav-item">
                <a href="categories.php" class="nav-link <?php if($current_page == 'categories.php') echo 'active'; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="payments.php" class="nav-link <?php if($current_page == 'payments.php') echo 'active'; ?>">
                    <i class="fas fa-credit-card"></i> Bill Payments
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Expenses - Single menu item for all staff -->
            <li class="nav-item">
                <a href="expenses.php" class="nav-link <?php if($current_page == 'expenses.php') echo 'active'; ?>">
                    <i class="fas fa-receipt"></i> Expenses
                </a>
            </li>
            
            <?php if($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="expense_categories.php" class="nav-link <?php if($current_page == 'expense_categories.php') echo 'active'; ?>">
                    <i class="fas fa-tags"></i> Expense Categories
                </a>
            </li>
            <?php endif; ?>
            <?php if($_SESSION['role'] !== 'chairman'): ?>
                <li class="nav-item">
                    <a href="stock_movements.php" class="nav-link <?php if($current_page == 'stock_movements.php') echo 'active'; ?>">
                        <i class="fas fa-history"></i> Stock History
                    </a>
                </li>
                <li class="nav-item">
                    <a href="sales.php" class="nav-link <?php if($current_page == 'sales.php') echo 'active'; ?>">
                        <i class="fas fa-cash-register"></i> POS / Sales
                    </a>
                </li>
                <li class="nav-item">
                    <a href="return_management.php" class="nav-link <?php if($current_page == 'return_management.php') echo 'active'; ?>">
                        <i class="fas fa-undo"></i> Return Management
                    </a>
                </li>
            <?php endif; ?>
            <?php if($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'chairman'): ?>
                <li class="nav-item">
                    <a href="attendance.php" class="nav-link <?php if($current_page == 'attendance.php') echo 'active'; ?>">
                        <i class="fas fa-clock"></i> Attendance
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="receipts.php" class="nav-link <?php if($current_page == 'receipts.php') echo 'active'; ?>">
                    <i class="fas fa-file-invoice"></i> Receipts
                </a>
            </li>
            <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
            <li class="nav-item">
                <a href="employees.php" class="nav-link <?php if($current_page == 'employees.php') echo 'active'; ?>">
                    <i class="fas fa-users"></i> Employees
                </a>
            </li>
            <li class="nav-item">
                <a href="reports.php" class="nav-link <?php if($current_page == 'reports.php') echo 'active'; ?>">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a href="expense_categories.php" class="nav-link <?php if($current_page == 'expense_categories.php') echo 'active'; ?>">
                    <i class="fas fa-tags"></i> Expense Categories
                </a>
            </li>
            <li class="nav-item">
                <a href="money_agent_monitoring.php" class="nav-link <?php if($current_page == 'money_agent_monitoring.php') echo 'active'; ?>">
                    <i class="fas fa-users-cog"></i> Agent Monitoring
                </a>
            </li>
            <li class="nav-item">
                <a href="money_settings.php" class="nav-link <?php if($current_page == 'money_settings.php') echo 'active'; ?>">
                    <i class="fas fa-sliders-h"></i> Balances
                </a>
            </li>
            <li class="nav-item">
                <a href="salary_management.php" class="nav-link <?php if($current_page == 'salary_management.php') echo 'active'; ?>">
                    <i class="fas fa-wallet"></i> Salary Management
                </a>
            </li>
            <li class="nav-item">
                <a href="manage_attendance.php" class="nav-link <?php if($current_page == 'manage_attendance.php') echo 'active'; ?>">
                    <i class="fas fa-calendar-check"></i> Manage Attendance
                </a>
            </li>
            <li class="nav-item mt-3">
                <span class="text-muted small px-3">Client Side Management</span>
            </li>
            <li class="nav-item">
                <a href="manage_client_orders.php" class="nav-link <?php if($current_page == 'manage_client_orders.php') echo 'active'; ?>">
                    <i class="fas fa-shopping-cart"></i> Client Orders
                </a>
            </li>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="manage_about_us.php" class="nav-link <?php if($current_page == 'manage_about_us.php') echo 'active'; ?>">
                    <i class="fas fa-info-circle"></i> About Us Content
                </a>
            </li>
            <?php endif; ?>
            <?php endif; ?>
            <?php if($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'chairman'): ?>
            <li class="nav-item">
                <a href="manual_stock_report.php" class="nav-link <?php if($current_page == 'manual_stock_report.php') echo 'active'; ?>">
                    <i class="fas fa-chart-bar"></i> Stock Adjustments
                </a>
            </li>
            <?php endif; ?>
            <?php if($_SESSION['role'] === 'admin'): ?>
            <li class="nav-item">
                <a href="backup.php" class="nav-link">
                    <i class="fas fa-download"></i> Database Backup
                </a>
            </li>
            <li class="nav-item">
                <a href="admin_settings.php" class="nav-link <?php if($current_page == 'admin_settings.php') echo 'active'; ?>">
                    <i class="fas fa-gear"></i> Settings
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    <div class="main-content">
        <div class="header">
            <div class="mobile-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </div>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>

    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('active');
        }

        // Prevent form resubmission and clear forms on refresh
        if ( window.history.replaceState ) {
            window.history.replaceState( null, null, window.location.href );
        }

        // Force form reset on page load to clear browser-cached values
        window.addEventListener('load', function() {
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                if (form.getAttribute('autocomplete') === 'off' || form.id === 'login-form') {
                    form.reset();
                }
            });
        });
    </script>