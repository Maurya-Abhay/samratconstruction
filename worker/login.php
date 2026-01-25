<?php
// worker_login.php
// --- Prevent Browser Caching ---
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

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
        $popup = ['type' => 'error', 'message' => 'System Locked: Too many attempts. Wait 15m.'];
    } else {
        // Log this attempt
        $attempts[$ip][] = $now;
        file_put_contents($login_attempts_file, json_encode($attempts));

        $identifier = trim(filter_var($_POST['identifier'] ?? '', FILTER_SANITIZE_STRING));
        $password   = trim($_POST['password'] ?? '');

        // Validate Credentials
        if ($identifier === '' || $password === '') {
            $popup = ['type' => 'error', 'message' => 'Worker ID/Email and Password required.'];
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
                        $popup = ['type' => 'success', 'message' => 'Worker Verified. Accessing Site...'];
                    } else {
                        $popup = ['type' => 'error', 'message' => 'Authentication Failed.'];
                    }
                } else {
                    $popup = ['type' => 'error', 'message' => 'Worker account not found.'];
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
    <title>Worker Portal | JP Construction</title>
    
    <link rel="icon" href="../admin/assets/jp_construction_logo.webp" type="image/webp">
    <meta name="theme-color" content="#0f172a" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --bg-dark: #0f172a;       /* Deep Navy */
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --accent: #f97316;        /* Safety Orange for Workers */
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-dark);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            margin: 0;
            position: relative;
        }

        /* --- AMBIENT BACKGROUND ANIMATION --- */
        .ambient-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(90px);
            opacity: 0.35;
            z-index: 0;
            animation: float 11s infinite ease-in-out alternate;
        }
        .orb-1 {
            width: 360px; height: 360px;
            background: #f97316; /* Orange */
            top: -60px; right: -20px;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: #ea580c; /* Darker Orange/Red */
            bottom: -50px; left: -50px;
            animation-delay: -3s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-30px, 40px) scale(1.05); }
        }

        /* --- GRID OVERLAY --- */
        .grid-overlay {
            position: absolute;
            width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 45px 45px;
            z-index: 1;
            pointer-events: none;
        }

        /* --- LOGIN CARD --- */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }

        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .glass-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        }

        .logo-container {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .logo-img { width: 50px; height: 50px; object-fit: contain; }

        .brand-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            color: var(--text-main);
            font-size: 1.5rem;
            margin-bottom: 5px;
            letter-spacing: 0.5px;
        }

        .brand-subtitle {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-bottom: 30px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        /* --- MODERN INPUTS --- */
        .input-group-custom {
            position: relative;
            margin-bottom: 25px;
            text-align: left;
        }

        .form-control-custom {
            width: 100%;
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid var(--glass-border);
            border-radius: 12px;
            padding: 16px 16px 16px 45px;
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control-custom:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(249, 115, 22, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            transition: 0.3s;
            pointer-events: none;
        }

        .form-control-custom:focus + .input-icon { color: var(--accent); }

        /* Password Toggle */
        .pwd-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            background: none;
            border: none;
            padding: 0;
            transition: 0.3s;
        }
        .pwd-toggle:hover { color: white; }

        /* --- BUTTON --- */
        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--accent) 0%, #c2410c 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 12px;
            font-weight: 600;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(249, 115, 22, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(249, 115, 22, 0.3);
            filter: brightness(1.1);
        }

        /* --- FOOTER --- */
        .footer-links {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

        .footer-links span { color: var(--text-muted); opacity: 0.7; }
        .footer-links a {
            color: var(--text-muted);
            text-decoration: none;
            transition: 0.3s;
        }
        .footer-links a:hover { color: var(--accent); }

        .back-link {
            display: block;
            margin-top: 30px;
            color: rgba(255,255,255,0.3);
            font-size: 0.8rem;
            text-decoration: none;
            transition: 0.3s;
        }
        .back-link:hover { color: white; }

        /* SweetAlert Overrides */
        div:where(.swal2-container) h2:where(.swal2-title) { font-family: 'Outfit', sans-serif !important; }
        div:where(.swal2-popup) { background: #1e293b !important; color: white !important; border: 1px solid #334155; }
    </style>
</head>
<body>

    <div class="ambient-orb orb-1"></div>
    <div class="ambient-orb orb-2"></div>
    <div class="grid-overlay"></div>

    <div class="login-wrapper">
        <div class="glass-card">
            
            <div class="logo-container">
                <img src="../admin/assets/jp_construction_logo.webp" alt="JP" class="logo-img">
            </div>
            
            <h2 class="brand-title">Worker Portal</h2>
            <p class="brand-subtitle">Site & Payment Access</p>

            <form method="POST" autocomplete="off">
                <div class="input-group-custom">
                    <input type="text" class="form-control-custom" id="identifier" name="identifier" 
                           placeholder="Email or Phone Number" 
                           value="<?= htmlspecialchars($_POST['identifier'] ?? '', ENT_QUOTES); ?>" required>
                    <i class="fa-solid fa-helmet-safety input-icon"></i>
                </div>

                <div class="input-group-custom">
                    <input type="password" class="form-control-custom" id="password" name="password" 
                           placeholder="Password" required>
                    <i class="fa-solid fa-key input-icon"></i>
                    
                    <button type="button" class="pwd-toggle" id="togglePwd" tabindex="-1">
                        <i class="fa-regular fa-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>

                <button type="submit" class="btn-submit">
                    Enter Site <i class="fa-solid fa-person-digging ms-2"></i>
                </button>

                <div class="footer-links">
                    <span>JP Construction</span>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fa-solid fa-house me-1"></i> Back to Main Menu
            </a>

        </div>
    </div>

    <script>
        // --- SweetAlert Feedback ---
        <?php if ($popup): ?>
            const popupData = <?= json_encode($popup); ?>;
            Swal.fire({
                icon: popupData.type,
                title: popupData.type === 'success' ? 'Authorized' : 'Access Denied',
                text: popupData.message,
                toast: true,
                position: 'top',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#1e293b',
                color: '#fff',
                iconColor: popupData.type === 'success' ? '#10b981' : '#ef4444'
            }).then(() => {
                if (popupData.type === 'success') {
                    window.location.href = 'dashboard.php';
                }
            });
        <?php endif; ?>

        // --- Password Toggle ---
        document.getElementById('togglePwd').addEventListener('click', function() {
            const passInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passInput.type === 'password') {
                passInput.type = 'text';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            } else {
                passInput.type = 'password';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            }
        });

        // --- Service Worker ---
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                navigator.serviceWorker.register('/abhay/service-worker.js').catch(e => {});
            });
        }
    </script>
</body>
</html>