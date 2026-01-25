<?php
// admin_login.php
// --- Prevent Browser Caching ---
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once 'lib_common.php';

// --- Session Handling ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['email'])) {
    header("Location: dashboard.php");
    exit;
}

// --- Configuration ---
$LOGIN_ATTEMPT_LIMIT = intval(get_setting('login_attempt_limit', '10'));
$popup = null;

// --- Security: Rate Limiting ---
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$attempt_file = __DIR__ . '/login_attempts_admin.json';
$attempts = [];

if (file_exists($attempt_file)) {
    $attempts = json_decode(file_get_contents($attempt_file), true) ?: [];
}

$now = time();
if (!isset($attempts[$ip])) $attempts[$ip] = [];

// Remove attempts older than 15 minutes
$attempts[$ip] = array_filter($attempts[$ip], fn($t) => $t > $now - 900);

// --- Login Logic ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check Rate Limit
    if (count($attempts[$ip]) >= $LOGIN_ATTEMPT_LIMIT) {
        $popup = ['type' => 'error', 'message' => 'Too many attempts. Try after 15 minutes.'];
    } else {
        // Log this attempt
        $attempts[$ip][] = $now;
        file_put_contents($attempt_file, json_encode($attempts));

        $identifier = trim($_POST['identifier'] ?? '');
        $password   = trim($_POST['password'] ?? '');

        if (!$identifier || !$password) {
            $popup = ['type' => 'error', 'message' => 'Please enter email/phone and password.'];
        } else {
            // Check Credentials
            $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? OR phone = ? LIMIT 1");
            $stmt->bind_param("ss", $identifier, $identifier);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    // Success
                    $_SESSION['email'] = $row['email'];
                    $popup = ['type' => 'success', 'message' => 'Authentication Verified. Accessing Core...'];
                } else {
                    $popup = ['type' => 'error', 'message' => 'Invalid credentials provided.'];
                }
            } else {
                $popup = ['type' => 'error', 'message' => 'User identity not found.'];
            }
        }
    }
} else {
    // Save cleaned up attempts on load
    file_put_contents($attempt_file, json_encode($attempts));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Portal | JP Construction</title>
    
    <link rel="icon" href="./assets/jp_construction_logo.webp" type="image/webp">
    
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
            --accent: #f59e0b;        /* Construction Amber */
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
            filter: blur(80px);
            opacity: 0.5;
            z-index: 0;
            animation: float 10s infinite ease-in-out alternate;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: #1e3a8a; /* Blue */
            top: -100px; left: -100px;
        }
        .orb-2 {
            width: 300px; height: 300px;
            background: #b45309; /* Dark Amber */
            bottom: -50px; right: -50px;
            animation-delay: -5s;
        }

        @keyframes float {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(30px, 50px) scale(1.1); }
        }

        /* --- GRID OVERLAY --- */
        .grid-overlay {
            position: absolute;
            width: 100%; height: 100%;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
            background-size: 40px 40px;
            z-index: 1;
            pointer-events: none;
        }

        /* --- LOGIN CARD --- */
        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
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

        /* Shine effect on card top */
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
            padding: 16px 16px 16px 45px; /* Space for icon */
            color: white;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            outline: none;
        }

        .form-control-custom:focus {
            background: rgba(0, 0, 0, 0.3);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(245, 158, 11, 0.1);
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
            background: linear-gradient(135deg, var(--accent) 0%, #d97706 100%);
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
            box-shadow: 0 10px 20px rgba(245, 158, 11, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(245, 158, 11, 0.3);
            filter: brightness(1.1);
        }

        .btn-submit:active { transform: translateY(0); }

        /* --- FOOTER LINKS --- */
        .footer-links {
            margin-top: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
        }

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

        /* SweetAlert Customization */
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
                <img src="assets/jp_construction_logo.webp" alt="JP" class="logo-img">
            </div>
            
            <h2 class="brand-title">Admin Access</h2>
            <p class="brand-subtitle">Authorized Personnel Only</p>

            <form method="POST" autocomplete="off">
                <div class="input-group-custom">
                    <input type="text" class="form-control-custom" id="identifier" name="identifier" 
                           placeholder="Email or Phone ID" 
                           value="<?= htmlspecialchars($_POST['identifier'] ?? '', ENT_QUOTES); ?>" required>
                    <i class="fa-solid fa-user-shield input-icon"></i>
                </div>

                <div class="input-group-custom">
                    <input type="password" class="form-control-custom" id="password" name="password" 
                           placeholder="Secure Password" required>
                    <i class="fa-solid fa-key input-icon"></i>
                    
                    <button type="button" class="pwd-toggle" id="togglePwd" tabindex="-1">
                        <i class="fa-regular fa-eye-slash" id="eyeIcon"></i>
                    </button>
                </div>

                <button type="submit" class="btn-submit">
                    Initialize Session <i class="fa-solid fa-arrow-right ms-2"></i>
                </button>

                <div class="footer-links">
                    <span class="text-white-50 small">v2.4.0 Secure</span>
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>

            <a href="../index.php" class="back-link">
                <i class="fa-solid fa-power-off me-1"></i> Return to Main Site
            </a>

        </div>
    </div>

    <script>
        // --- SweetAlert Logic ---
        <?php if ($popup): ?>
            Swal.fire({
                icon: '<?= $popup['type'] ?>',
                title: '<?= $popup['message'] ?>',
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2500,
                timerProgressBar: true,
                background: '#1e293b',
                color: '#fff',
                iconColor: '<?= $popup['type'] == "success" ? "#10b981" : "#ef4444" ?>'
            }).then(() => {
                <?php if ($popup['type'] === 'success'): ?>
                    window.location.href = 'dashboard.php';
                <?php endif; ?>
            });
        <?php endif; ?>

        // --- Password Visibility Toggle ---
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
    </script>
</body>
</html>