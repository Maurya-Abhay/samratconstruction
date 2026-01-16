<?php
// worker_login.php

require_once __DIR__ . '/../admin/lib_common.php';

// --- Session Handling ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['worker_id'])) {
    header("Location: dashboard.php");
    exit();
}

// --- Configuration ---
$LOGIN_ATTEMPT_LIMIT = intval(get_setting('login_attempt_limit', '10'));
$popup = null;

// Check for session-based messages (e.g. from logout)
if (isset($_SESSION['popup'])) {
    $popup = $_SESSION['popup'];
    unset($_SESSION['popup']);
}

// --- Security: Rate Limiting ---
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$login_attempts_file = __DIR__ . '/login_attempts.json';
$attempts = [];

if (file_exists($login_attempts_file)) {
    $attempts = json_decode(file_get_contents($login_attempts_file), true) ?: [];
}

$now = time();
if (!isset($attempts[$ip])) $attempts[$ip] = [];

// Remove attempts older than 15 minutes
$attempts[$ip] = array_filter($attempts[$ip], function($ts) use ($now) { 
    return $ts > $now - 900; 
});

// --- Login Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Check Rate Limit
    if (count($attempts[$ip]) >= $LOGIN_ATTEMPT_LIMIT) {
        $popup = ['type' => 'error', 'message' => 'Too many login attempts. Please wait 15 minutes.'];
    } else {
        // Log this attempt
        $attempts[$ip][] = $now;
        file_put_contents($login_attempts_file, json_encode($attempts));

        $identifier = trim(filter_var($_POST['identifier'] ?? '', FILTER_SANITIZE_STRING));
        $password   = trim($_POST['password'] ?? '');

        // Validate Credentials
        if ($identifier === '' || $password === '') {
            $popup = ['type' => 'error', 'message' => 'Please enter both email/phone and password.'];
        } else {
            // Database Check (Workers Table)
            $stmt = $conn->prepare('SELECT id, name, email, phone, password FROM workers WHERE email = ? OR phone = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('ss', $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    if (password_verify($password, $row['password'])) {
                        // Success
                        session_regenerate_id(true); // Secure session
                        $_SESSION['worker_id'] = $row['id'];
                        $popup = ['type' => 'success', 'message' => 'Login successful! Redirecting...'];
                    } else {
                        $popup = ['type' => 'error', 'message' => 'Invalid password!'];
                    }
                } else {
                    $popup = ['type' => 'error', 'message' => 'Worker account not found!'];
                }
                $stmt->close();
            } else {
                $popup = ['type' => 'error', 'message' => 'Database connection failed.'];
            }
        }
    }
} else {
    // Save cleaned up attempts
    file_put_contents($login_attempts_file, json_encode($attempts));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Worker Login | Samrat Construction</title>
    
    <link rel="icon" href="../admin/assets/smrticon.png" type="image/png">
    <meta name="theme-color" content="#0d6efd" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #0d6efd;
            --primary-hover: #0b5ed7;
            --bg-gradient: linear-gradient(135deg, #f0f4f8 0%, #d9e2ec 100%);
        }

        html, body {
            height: 100%;
            margin: 0;
            /* FIX 1: Removed overflow: hidden */
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-gradient);
            /* FIX 2: Added min-height for reliable vertical centering */
            min-height: 100vh;
            display: flex;
            align-items: center;      /* Vertically Center */
            justify-content: center; /* Horizontally Center */
            padding: 20px;
        }

        /* Compact Modern Card */
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 360px; /* Compact width */
            position: relative;
            z-index: 10;
            /* FIX 3: Added margin: auto for better centering logic */
            margin: auto; 
        }

        .card-header-custom {
            text-align: center;
            padding: 25px 20px 5px;
        }

        .logo-img {
            width: 45px;
            height: 45px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .card-body-custom {
            padding: 15px 25px 30px;
        }

        /* Floating Input Styles */
        .form-floating > .form-control {
            border-radius: 10px;
            border: 1px solid #dee2e6;
            height: 50px;
            min-height: 50px;
            font-size: 15px;
        }
        
        .form-floating > label {
            padding-top: 0.8rem;
            padding-bottom: 0.8rem;
            font-size: 0.9rem;
        }

        .form-floating > .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(13, 110, 253, 0.15);
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 5;
            background: none;
            border: none;
            padding: 0;
        }

        /* Compact Button */
        .btn-primary-custom {
            background: var(--primary-color);
            border: none;
            border-radius: 10px;
            padding: 11px;
            font-weight: 600;
            font-size: 15px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s;
            box-shadow: 0 4px 10px rgba(13, 110, 253, 0.25);
        }

        .btn-primary-custom:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
        }

        .links-area {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            font-size: 0.8rem;
        }

        .links-area a {
            color: #6c757d;
            text-decoration: none;
        }
        
        .links-area a:hover { color: var(--primary-color); }

        .divider {
            margin: 20px 0 15px;
            border-top: 1px solid #e9ecef;
            position: relative;
            text-align: center;
        }

        .divider span {
            background: #fff;
            padding: 0 8px;
            color: #adb5bd;
            font-size: 11px;
            position: relative;
            top: -9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Mobile Adjustments - Ensuring Center */
        @media (max-width: 576px) {
            body {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                min-height: 100vh;
            }
            .login-card {
                margin: auto; 
                box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="card-header-custom">
            <img src="../admin/assets/smrticon.png" alt="Logo" class="logo-img">
            <h5 class="fw-bold text-dark mb-0">Worker Login</h5>
            <p class="text-muted small mb-0">Team Access</p>
        </div>

        <div class="card-body-custom">
            <form method="POST" autocomplete="off">
                
                <div class="form-floating mb-2">
                    <input type="text" class="form-control" id="identifier" name="identifier" 
                           placeholder="name@example.com" 
                           value="<?= htmlspecialchars($_POST['identifier'] ?? '', ENT_QUOTES); ?>" required>
                    <label for="identifier">Email or Phone</label>
                </div>

                <div class="position-relative mb-2">
                    <div class="form-floating">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password">Password</label>
                    </div>
                    <button type="button" class="password-toggle" id="togglePwd" tabindex="-1">
                        <i class="bi bi-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>

                <div class="links-area mb-3">
                    <span class="text-muted">Issue logging in?</span>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>

                <button type="submit" class="btn btn-primary btn-primary-custom text-white">
                    Access Dashboard
                </button>

                <div class="divider">
                    <span>Authorized Workers Only</span>
                </div>
                
                <div class="text-center">
                    <a href="../index.php" class="text-decoration-none small text-muted">
                        <i class="bi bi-arrow-left me-1"></i> Back to Home
                    </a>
                </div>

            </form>
        </div>
    </div>

    <script>
        // --- SweetAlert Feedback ---
        <?php if ($popup): ?>
            const popupData = <?= json_encode($popup); ?>;
            Swal.fire({
                icon: popupData.type,
                title: popupData.type === 'success' ? 'Login Successful!' : 'Login Failed',
                text: popupData.message,
                showConfirmButton: false,
                timer: popupData.type === 'success' ? 1800 : 2200,
                position: 'center',
                timerProgressBar: true,
                willClose: () => {
                    if (popupData.type === 'success') {
                        window.location.href = 'dashboard.php';
                    }
                }
            });
        <?php endif; ?>

        // --- Password Toggle Visibility ---
        document.getElementById('togglePwd').addEventListener('click', function() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.remove('bi-eye-slash');
                eyeIcon.classList.add('bi-eye');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.remove('bi-eye');
                eyeIcon.classList.add('bi-eye-slash');
            }
        });

        // --- Service Worker Registration (if applicable) ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/abhay/service-worker.js').catch(e => {});
            });
        }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>