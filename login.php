<?php
session_start();
include 'dp.php'; 

$error_message = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!empty($_POST['username']) && !empty($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        try {
            $stmt = $conn->prepare("SELECT id, password, role FROM employees WHERE username = ?");
            if (!$stmt) {
                throw new Exception($conn->error);
            }
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                if (password_verify($password, $user['password'])) {
                    $_SESSION['loggedin'] = true;
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $user['role'];

                    // Log the login activity
                    $action = "Logged in";
                    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
                    $log_stmt->bind_param("is", $user['id'], $action);
                    $log_stmt->execute();
                    $log_stmt->close();

                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_message = 'Invalid username or password.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                $error_message = "<strong>Database Error:</strong> The system is not set up. Please contact the administrator.";
            } else {
                $error_message = "Error: " . $e->getMessage();
            }
        } catch (Exception $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    } else {
        $error_message = 'Please enter both username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Maison Tech</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --bg: #0b1220;
            --surface: rgba(255, 255, 255, 0.92);
            --surface-strong: #ffffff;
            --text: #0f172a;
            --muted: #64748b;
            --border: rgba(148, 163, 184, 0.35);
            --ring: rgba(99, 102, 241, 0.22);
            --primary: #6366f1;
            --primary-2: #22c55e;
            --danger: #ef4444;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: radial-gradient(1200px 800px at 20% 10%, rgba(99, 102, 241, 0.35), transparent 55%),
                        radial-gradient(900px 700px at 80% 30%, rgba(34, 197, 94, 0.22), transparent 60%),
                        linear-gradient(180deg, #0b1220, #050914);
            margin: 0;
            display: flex;
            height: 100vh;
            color: var(--text);
            align-items: center;
            justify-content: center;
        }
        .login-wrapper {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-branding {
            display: none;
        }
        .login-branding::before,
        .login-branding::after {
            content: "";
            position: absolute;
            width: 520px;
            height: 520px;
            border-radius: 999px;
            filter: blur(40px);
            opacity: 0.55;
            pointer-events: none;
        }
        .login-branding::before {
            left: -240px;
            top: -220px;
            background: radial-gradient(circle at 30% 30%, rgba(99, 102, 241, 0.9), rgba(99, 102, 241, 0.0) 60%);
        }
        .login-branding::after {
            right: -260px;
            bottom: -260px;
            background: radial-gradient(circle at 30% 30%, rgba(34, 197, 94, 0.75), rgba(34, 197, 94, 0.0) 60%);
        }
        .branding-logo {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            font-size: 46px;
            font-weight: 850;
            letter-spacing: -1.2px;
            margin-bottom: 14px;
        }
        .branding-logo .logo-mark {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.24), rgba(255, 255, 255, 0.06));
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 18px 50px rgba(0, 0, 0, 0.35);
            display: grid;
            place-items: center;
        }
        .branding-logo .logo-mark i {
            font-size: 20px;
            color: rgba(255, 255, 255, 0.95);
        }
        .branding-tagline {
            font-size: 18px;
            color: rgba(226, 232, 240, 0.78);
            max-width: 350px;
            line-height: 1.55;
        }
        .login-form-container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
            padding: 34px;
            border-radius: 18px;
            background: var(--surface);
            border: 1px solid rgba(148, 163, 184, 0.22);
            box-shadow: 0 20px 60px rgba(2, 6, 23, 0.45);
        }
        .form-title {
            font-size: 28px;
            font-weight: 700;
            color: var(--text);
            margin-bottom: 10px;
            text-align: left;
        }
        .form-subtitle {
            font-size: 16px;
            color: var(--muted);
            margin-bottom: 22px;
            text-align: left;
        }
        .input-group {
            position: relative;
            margin-bottom: 20px;
        }
        .input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(100, 116, 139, 0.9);
            pointer-events: none;
        }
        .form-control {
            width: 100%;
            padding: 14px 44px 14px 46px;
            border: 1px solid var(--border);
            border-radius: 12px;
            font-size: 16px;
            transition: border-color 0.2s, box-shadow 0.2s, background-color 0.2s;
            background: rgba(248, 250, 252, 0.75);
            color: var(--text);
        }
        .form-control:focus {
            outline: none;
            border-color: rgba(99, 102, 241, 0.65);
            box-shadow: 0 0 0 5px var(--ring);
            background: rgba(255, 255, 255, 0.98);
        }
        .field-action {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            width: 36px;
            height: 36px;
            border: 1px solid rgba(148, 163, 184, 0.28);
            background: rgba(255, 255, 255, 0.65);
            border-radius: 10px;
            cursor: pointer;
            display: grid;
            place-items: center;
            transition: transform 0.08s, background-color 0.2s, border-color 0.2s;
        }
        .field-action:hover {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(99, 102, 241, 0.35);
        }
        .field-action:active {
            transform: translateY(-50%) scale(0.98);
        }
        .field-action i {
            position: static;
            transform: none;
            color: rgba(71, 85, 105, 0.95);
            pointer-events: none;
        }
        .form-meta {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-top: 6px;
            margin-bottom: 18px;
            color: var(--muted);
            font-size: 14px;
        }
        .form-meta .hint {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: rgba(100, 116, 139, 0.95);
        }
        .form-meta .hint i {
            color: rgba(99, 102, 241, 0.9);
        }
        .helper-link {
            color: rgba(99, 102, 241, 0.95);
            text-decoration: none;
            font-weight: 600;
        }
        .helper-link:hover {
            text-decoration: underline;
        }
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--primary), #4f46e5);
            color: white;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.08s, filter 0.2s, box-shadow 0.2s;
            margin-top: 2px;
            box-shadow: 0 16px 40px rgba(79, 70, 229, 0.28);
        }
        .btn-login:hover {
            filter: brightness(1.05);
        }
        .btn-login:active {
            transform: translateY(1px);
        }
        .error-message {
            color: #991b1b;
            background: rgba(254, 242, 242, 0.95);
            border: 1px solid rgba(239, 68, 68, 0.22);
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: left;
        }
        .error-message a {
            color: rgba(99, 102, 241, 0.95);
            font-weight: 600;
        }
        .sr-only {
            position: absolute !important;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        @media (max-width: 768px) {
            .login-form {
                margin: 0;
                max-width: 100%;
                padding: 28px;
                border-radius: 12px;
            }
            .form-title {
                font-size: 24px;
            }
            .form-subtitle {
                font-size: 14px;
            }
        }
        @media (max-height: 500px) {
            body {
                height: auto;
                min-height: 100vh;
                padding: 20px 0;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-branding">
            <div class="branding-logo">
                <span class="logo-mark" aria-hidden="true"><i class="fas fa-store"></i></span>
                <span>Maison Tech</span>
            </div>
            <p class="branding-tagline">The complete inventory and point-of-sale solution for modern business.</p>
        </div>
        <div class="login-form-container">
            <div class="login-form">
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="images/maison-tech-logo.png.jpeg" alt="Maison Tech Logo" style="width: 150px; height: auto;">
                </div>
                <h1 class="form-title">Welcome Back</h1>
                <p class="form-subtitle">Please enter your credentials to sign in.</p>
                
                <?php if ($error_message): ?>
                    <div class="error-message"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <form action="login.php" method="post" autocomplete="new-password">
                    <input type="text" style="display:none">
                    <input type="password" style="display:none">
                    <div class="input-group">
                        <label class="sr-only" for="username">Username</label>
                        <i class="fas fa-user"></i>
                        <input id="username" type="text" name="username" class="form-control" placeholder="Username" autocomplete="off" required value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>">
                    </div>
                    <div class="input-group">
                        <label class="sr-only" for="password">Password</label>
                        <i class="fas fa-lock"></i>
                        <input id="password" type="password" name="password" class="form-control" placeholder="Password" autocomplete="new-password" required>
                        <button class="field-action" type="button" id="togglePassword" aria-label="Show password">
                            <i class="fas fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="form-meta">
                        <span class="hint"><i class="fas fa-shield-alt" aria-hidden="true"></i> Secure sign-in</span>
                    </div>
                    <button type="submit" class="btn-login">Sign In</button>
                </form>
            </div>
        </div>
    </div>
    <script>
        (function () {
            // Prevent form resubmission
            if ( window.history.replaceState ) {
                window.history.replaceState( null, null, window.location.href );
            }

            // Force form reset on page load to clear browser-cached values
            window.addEventListener('load', function() {
                const forms = document.querySelectorAll('form');
                forms.forEach(form => form.reset());
            });

            var btn = document.getElementById('togglePassword');
            var input = document.getElementById('password');
            if (!btn || !input) return;

            btn.addEventListener('click', function () {
                var isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                btn.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !isPassword);
                    icon.classList.toggle('fa-eye-slash', isPassword);
                }
            });
        })();
    </script>
</body>
</html>