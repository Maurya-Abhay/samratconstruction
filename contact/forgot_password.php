<?php
// forgot_password.php

require_once __DIR__ . '/../admin/lib_common.php';

// --- AJAX API HANDLER ---
// This block handles the background requests from JavaScript
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $response = ['status' => 'error', 'message' => 'Unknown error'];

    try {
        // ACTION 1: SEND OTP
        if ($_POST['ajax_action'] === 'send_otp') {
            $email = strtolower(trim($_POST['email'] ?? ''));

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('<script src=\'https://cdn.jsdelivr.net/npm/sweetalert2@11\'></script><script>Swal.fire({icon:\'warning\',title:\'Invalid Email\',text:\'Please enter a valid email address.\',showConfirmButton:false,timer:2000});</script>');
            }

            // Check if email exists
            $stmt = $conn->prepare("SELECT id FROM contacts WHERE LOWER(TRIM(email))=? LIMIT 1");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $res = $stmt->get_result();
            $stmt->close();

            if ($res && $res->num_rows) {
                $otp = random_int(100000, 999999);
                $expires_at = date('Y-m-d H:i:s', time() + 15 * 60);

                // Insert OTP
                $stmt2 = $conn->prepare("INSERT INTO password_resets (email, otp, expires_at, used, created_at) VALUES (?, ?, ?, 0, NOW())");
                $stmt2->bind_param('sis', $email, $otp, $expires_at);
                
                if ($stmt2->execute()) {
                    $stmt2->close();
                    
                    // --- SEND EMAIL (Brevo/SMTP) ---
                    // Assuming Brevo setup exists from your previous code
                    $apiKeyPath = __DIR__.'/../admin/brevo_api_key.txt';
                    $senderPath = __DIR__.'/../admin/brevo_sender.txt';

                    if (file_exists($apiKeyPath)) {
                        $apiKey = trim(file_get_contents($apiKeyPath));
                        $sender = trim(file_get_contents($senderPath));
                        
                        $subject = '🔐 Password Reset OTP';
                        $body = "<div style='font-family:sans-serif;padding:20px;border:1px solid #eee;border-radius:10px;'>"
                            . "<h2 style='color:#0d6efd;'>Reset Code</h2>"
                            . "<p>Your One-Time Password is:</p>"
                            . "<h1 style='color:#333;letter-spacing:5px;'>$otp</h1>"
                            . "<p>Valid for 15 minutes.</p></div>";

                        $data = [
                            'sender' => ['email' => $sender, 'name' => 'Security Team'],
                            'to' => [['email' => $email]],
                            'subject' => $subject,
                            'htmlContent' => $body
                        ];

                        $ch = curl_init('https://api.brevo.com/v3/smtp/email');
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'api-key: ' . $apiKey]);
                        curl_exec($ch);
                        curl_close($ch);
                    }
                    
                    $response = ['status' => 'success', 'message' => 'OTP sent to your email.'];
                } else {
                    throw new Exception('Database error.');
                }
            } else {
                throw new Exception('No account found with this email.');
            }
        } 
        
        // ACTION 2: VERIFY OTP & RESET PASSWORD
        elseif ($_POST['ajax_action'] === 'reset_password') {
            $email = trim($_POST['email'] ?? '');
            $otp = trim($_POST['otp'] ?? '');
            $pass = $_POST['new_password'] ?? '';
            $pass2 = $_POST['confirm_password'] ?? '';

            if (empty($otp)) throw new Exception('Please enter the OTP.');
            if (strlen($pass) < 6) throw new Exception('Password must be 6+ chars.');
            if ($pass !== $pass2) throw new Exception('Passwords do not match.');

            // Verify OTP
            $stmt = $conn->prepare("SELECT id, expires_at, used FROM password_resets WHERE email = ? AND otp = ? ORDER BY created_at DESC LIMIT 1");
            $stmt->bind_param('ss', $email, $otp);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res && $row = $res->fetch_assoc()) {
                if ($row['used'] == 1) throw new Exception('OTP already used.');
                if (strtotime($row['expires_at']) < time()) throw new Exception('OTP has expired.');

                // Update Password
                $hashed = password_hash($pass, PASSWORD_DEFAULT);
                $u = $conn->prepare("UPDATE contacts SET password = ? WHERE email = ? LIMIT 1");
                $u->bind_param('ss', $hashed, $email);
                
                if ($u->execute()) {
                    // Mark OTP Used
                    $m = $conn->prepare("UPDATE password_resets SET used = 1, used_at = NOW() WHERE id = ?");
                    $m->bind_param('i', $row['id']);
                    $m->execute();
                    
                    $response = ['status' => 'success', 'message' => 'Password changed successfully.'];
                } else {
                    throw new Exception('Failed to update password.');
                }
            } else {
                throw new Exception('Invalid OTP Code.');
            }
        }

    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => $e->getMessage()];
    }

    echo json_encode($response);
    exit; // Stop script execution here for AJAX calls
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Account Recovery | Samrat Construction</title>
    
    <link rel="icon" href="../admin/assets/smrticon.png" type="image/png">
    <meta name="theme-color" content="#0d6efd" />

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #4e73df;
            --primary-dark: #224abe;
            --bg-gradient: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        html, body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        /* Modern Glass Card */
        .auth-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
            overflow: hidden; /* Essential for sliding content */
        }

        .auth-header {
            text-align: center;
            padding: 30px 30px 10px;
        }

        .auth-icon {
            width: 60px;
            height: 60px;
            background: rgba(13, 110, 253, 0.1);
            color: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 28px;
        }

        .auth-body {
            padding: 20px 30px 40px;
            position: relative;
            min-height: 300px; /* Pre-allocate height */
        }

        /* Sliding Steps */
        .step-view {
            position: absolute;
            top: 20px;
            left: 30px;
            right: 30px;
            transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            opacity: 0;
            transform: translateX(110%);
            visibility: hidden;
        }

        .step-view.active {
            opacity: 1;
            transform: translateX(0);
            visibility: visible;
        }
        
        .step-view.prev {
            transform: translateX(-110%);
        }

        /* Inputs */
        .form-control {
            border-radius: 12px;
            padding: 12px 15px;
            border: 1px solid #e3e6f0;
            font-size: 15px;
            background: #f8f9fc;
        }
        .form-control:focus {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        /* Floating labels tweak */
        .form-floating > label {
            padding-top: 10px;
        }

        /* Buttons */
        .btn-modern {
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 600;
            width: 100%;
            font-size: 16px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.25);
        }
        .btn-modern:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(78, 115, 223, 0.35);
        }

        .otp-input {
            letter-spacing: 5px;
            font-size: 20px;
            text-align: center;
            font-weight: bold;
        }

        .back-btn {
            color: #858796;
            text-decoration: none;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            margin-top: 20px;
            transition: color 0.2s;
        }
        .back-btn:hover { color: var(--primary-color); }

        /* Mobile */
        @media (max-width: 576px) {
            .auth-card {
                max-width: 100%;
                border-radius: 16px;
                margin: 0 10px;
            }
            .auth-body { padding: 20px; }
            .step-view { left: 20px; right: 20px; }
        }
    </style>
</head>
<body>

    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h4 class="fw-bold mb-1">Account Recovery</h4>
            <p class="text-muted small mb-0">Follow the steps to recover access</p>
        </div>

        <div class="auth-body">
            
            <div id="view-1" class="step-view active">
                <form id="formStep1" onsubmit="handleSendOtp(event)">
                    <div class="form-floating mb-4">
                        <input type="email" class="form-control" id="emailInput" placeholder="name@example.com" required>
                        <label>Registered Email</label>
                    </div>
                    <button type="submit" class="btn btn-modern" id="btn1">
                        Send OTP
                    </button>
                    <div class="text-center">
                        <a href="login.php" class="back-btn"><i class="bi bi-arrow-left me-1"></i> Back to Login</a>
                    </div>
                </form>
            </div>

            <div id="view-2" class="step-view">
                <form id="formStep2" onsubmit="handleReset(event)">
                    <p class="text-center small text-muted mb-3">
                        OTP Sent to <span id="displayEmail" class="fw-bold text-dark"></span>
                    </p>
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control otp-input" id="otpInput" placeholder="000000" maxlength="6" inputmode="numeric" required>
                        <label>Enter 6-Digit OTP</label>
                    </div>

                    <div class="row g-2 mb-4">
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="pass1" placeholder="New" required>
                                <label>New Pass</label>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-floating">
                                <input type="password" class="form-control" id="pass2" placeholder="Confirm" required>
                                <label>Confirm</label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-modern" id="btn2">
                        Change Password
                    </button>
                    <div class="text-center">
                         <button type="button" onclick="switchView(1)" class="btn btn-link back-btn p-0 border-0">Change Email</button>
                    </div>
                </form>
            </div>

            <div id="view-3" class="step-view text-center pt-3">
                <div class="mb-3 text-success">
                    <i class="bi bi-check-circle-fill" style="font-size: 60px;"></i>
                </div>
                <h4 class="fw-bold">Success!</h4>
                <p class="text-muted small mb-4">Your password has been updated securely.</p>
                <a href="login.php" class="btn btn-modern">
                    Login Now
                </a>
            </div>

        </div>
    </div>

    <script>
        let currentEmail = '';

        // Switch Logic
        function switchView(viewNumber) {
            // Reset all
            document.querySelectorAll('.step-view').forEach(el => {
                el.classList.remove('active', 'prev');
            });

            // Set specific classes
            const current = document.getElementById('view-' + viewNumber);
            current.classList.add('active');

            // Add 'prev' class to earlier views for styling if needed, 
            // mainly just handling 'active' is enough for basic slide
        }

        // Loading Helper
        function toggleLoading(btnId, isLoading) {
            const btn = document.getElementById(btnId);
            if(isLoading) {
                btn.dataset.text = btn.innerHTML;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                btn.disabled = true;
            } else {
                btn.innerHTML = btn.dataset.text;
                btn.disabled = false;
            }
        }

        // ACTION: Send OTP
        async function handleSendOtp(e) {
            e.preventDefault();
            const email = document.getElementById('emailInput').value;
            toggleLoading('btn1', true);

            const formData = new FormData();
            formData.append('ajax_action', 'send_otp');
            formData.append('email', email);

            try {
                const req = await fetch('forgot_password.php', { method: 'POST', body: formData });
                const res = await req.json();

                if (res.status === 'success') {
                    currentEmail = email;
                    document.getElementById('displayEmail').innerText = email;
                    
                    Swal.fire({
                        icon: 'success',
                        title: 'OTP Sent',
                        text: 'Please check your email inbox.',
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    switchView(2);
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('System Error', 'Could not connect to server.', 'error');
            }
            toggleLoading('btn1', false);
        }

        // ACTION: Reset Password
        async function handleReset(e) {
            e.preventDefault();
            const otp = document.getElementById('otpInput').value;
            const p1 = document.getElementById('pass1').value;
            const p2 = document.getElementById('pass2').value;

            if (p1 !== p2) {
                Swal.fire('Mismatch', 'Passwords do not match', 'warning');
                return;
            }

            toggleLoading('btn2', true);

            const formData = new FormData();
            formData.append('ajax_action', 'reset_password');
            formData.append('email', currentEmail);
            formData.append('otp', otp);
            formData.append('new_password', p1);
            formData.append('confirm_password', p2);

            try {
                const req = await fetch('forgot_password.php', { method: 'POST', body: formData });
                const res = await req.json();

                if (res.status === 'success') {
                    switchView(3);
                } else {
                    Swal.fire('Failed', res.message, 'error');
                }
            } catch (err) {
                Swal.fire('System Error', 'Connection failed.', 'error');
            }
            toggleLoading('btn2', false);
        }

        // Service Worker
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/abhay/service-worker.js').catch(e => {});
            });
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>