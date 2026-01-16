<?php
// settings_new.php
// Complete settings page with all features, error-free
require_once __DIR__ . '/lib_common.php';
include 'topheader.php';
include 'sidenavbar.php';
require_once __DIR__ . '/upi_config.php';

// --- FETCH ADMIN DATA ---
$admin = [];
$admin_email = $_SESSION['email'] ?? '';
if ($admin_email && isset($conn)) {
    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $admin_email);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $admin = $result->fetch_assoc();
    }
    $stmt->close();
}

// --- Feature Handlers (profile, professional, security, advanced, control panels, reminders, localization, UPI, compliance, delete account) ---
$adv_ok = $adv_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_advanced'])) {
    $policy_min_length = intval($_POST['policy_min_length'] ?? 8);
    $policy_special = isset($_POST['policy_special']) ? '1' : '0';
    $admin_session_timeout = intval($_POST['admin_session_timeout'] ?? 1440);
    $panels_session_timeout = intval($_POST['panels_session_timeout'] ?? 1440);
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $maintenance_msg = trim($_POST['maintenance_msg'] ?? 'Site under maintenance.');
    $notif_email = trim($_POST['notif_email'] ?? '');
    $notif_sms_api = trim($_POST['notif_sms_api'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $ip_list = trim($_POST['ip_list'] ?? '');
    $dashboard_widgets = trim($_POST['dashboard_widgets'] ?? '');
    $settings = [
        'policy_min_length' => $policy_min_length,
        'policy_special' => $policy_special,
        'admin_session_timeout' => $admin_session_timeout,
        'panels_session_timeout' => $panels_session_timeout,
        'global_session_timeout' => $admin_session_timeout,
        'maintenance_mode' => $maintenance_mode,
        'maintenance_msg' => $maintenance_msg,
        'notif_email' => $notif_email,
        'notif_sms_api' => $notif_sms_api,
        'api_key' => $api_key,
        'ip_list' => $ip_list,
        'dashboard_widgets' => $dashboard_widgets
    ];
    try {
        set_settings($settings);
        $adv_ok = 'Advanced settings updated.';
    } catch (Throwable $e) {
        $adv_err = 'Failed to update advanced settings.';
    }
}
$prof_ok = $prof_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $aadhaar = trim($_POST['aadhaar'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $photo_path = $admin['photo'] ?? '';
    if (!empty($_FILES['profilePhoto']['name'])) {
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (in_array($_FILES['profilePhoto']['type'], $allowed)) {
            $dir = __DIR__ . '/uploads/';
            if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
            $ext = pathinfo($_FILES['profilePhoto']['name'], PATHINFO_EXTENSION);
            $fname = 'admin_photo_' . time() . '_' . rand(1000,9999) . '.' . $ext;
            $path = $dir . $fname;
            if (move_uploaded_file($_FILES['profilePhoto']['tmp_name'], $path)) {
                $photo_path = 'uploads/' . $fname;
            } else {
                $prof_err = 'Failed to upload photo.';
            }
        } else {
            $prof_err = 'Photo must be JPG, PNG, or WEBP.';
        }
    }
    if ($name === '') {
        $prof_err = 'Name is required.';
    }
    if (!$prof_err) {
        $upd = $conn->prepare("UPDATE admin SET name=?, aadhaar=?, dob=?, gender=?, city=?, state=?, address=?, photo=? WHERE email=? LIMIT 1");
        $upd->bind_param("sssssssss", $name, $aadhaar, $dob, $gender, $city, $state, $address, $photo_path, $admin_email);
        if ($upd->execute()) {
            $prof_ok = 'Profile updated.';
            $admin['name'] = $name;
            $admin['aadhaar'] = $aadhaar;
            $admin['dob'] = $dob;
            $admin['gender'] = $gender;
            $admin['city'] = $city;
            $admin['state'] = $state;
            $admin['address'] = $address;
            $admin['photo'] = $photo_path;
        } else {
            $prof_err = 'Failed to update profile.';
        }
        $upd->close();
    }
}
// Professional tab handler
$pro_ok = $pro_err = null;
$pro_edit_mode = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_professional'])) {
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $joining_date = trim($_POST['joining_date'] ?? '');
    if ($designation === '' || $department === '') {
        $pro_err = 'Designation and Department are required.';
        $pro_edit_mode = true;
    } else {
        $update = $conn->prepare("UPDATE admin SET designation=?, department=?, joining_date=? WHERE email=? LIMIT 1");
        $update->bind_param("ssss", $designation, $department, $joining_date, $admin_email);
        if ($update->execute()) {
            $pro_ok = 'Professional info updated.';
            $admin['designation'] = $designation;
            $admin['department'] = $department;
            $admin['joining_date'] = $joining_date;
        } else {
            $pro_err = 'Failed to update info.';
            $pro_edit_mode = true;
        }
        $update->close();
    }
}
if (isset($_POST['btnProEnter']) || isset($_POST['save_professional'])) {
    $pro_edit_mode = true;
}

// Security & Prefs tab handler
$login_limit_ok = $login_limit_err = null;
$LOGIN_ATTEMPT_LIMIT = get_setting('login_attempt_limit', '10');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_login_limit'])) {
    $new_limit = intval($_POST['login_attempt_limit'] ?? '10');
    if ($new_limit < 3 || $new_limit > 50) {
        $login_limit_err = 'Limit must be between 3 and 50.';
    } else {
        set_settings(['login_attempt_limit' => $new_limit]);
        $LOGIN_ATTEMPT_LIMIT = $new_limit;
        $login_limit_ok = 'Login attempt limit updated.';
    }
}

$sec_ok = $sec_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_2fa'])) {
    // Only update 2FA setting if user submitted the form
    $admin_2fa = isset($_POST['admin_2fa']) ? '1' : '0';
    set_settings(['admin_2fa' => $admin_2fa]);
    $sec_ok = '2FA setting updated.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($new !== $confirm) {
        $sec_err = 'New passwords do not match.';
    } elseif (strlen($new) < 6) {
        $sec_err = 'Password must be at least 6 characters.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM admin WHERE email=? LIMIT 1");
        $stmt->bind_param("s", $admin_email);
        $stmt->execute();
        $stmt->bind_result($hash);
        $stmt->fetch();
        $stmt->close();
        if (!password_verify($current, $hash)) {
            $sec_err = 'Current password incorrect.';
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE admin SET password=? WHERE email=? LIMIT 1");
            $upd->bind_param("ss", $new_hash, $admin_email);
            if ($upd->execute()) {
                $sec_ok = 'Password changed.';
            } else {
                $sec_err = 'Failed to change password.';
            }
            $upd->close();
        }
    }
}

$pref_ok = $pref_err = null;
$existing=[]; if ($res=$conn->query("SHOW COLUMNS FROM admin")) { while($r=$res->fetch_assoc()){ $existing[$r['Field']]=true; } $res->close(); }
$pref_dark = '0'; $pref_email_notif='1';
if (isset($existing['pref_dark']) || isset($existing['pref_email_notif'])) {
    if (isset($existing['pref_dark'])) { $pref_dark = (string)($admin['pref_dark'] ?? '0'); }
    if (isset($existing['pref_email_notif'])) { $pref_email_notif = (string)($admin['pref_email_notif'] ?? '1'); }
} else {
    $pref_dark = get_setting('pref_'.$admin_email.'_dark','0');
    $pref_email_notif = get_setting('pref_'.$admin_email.'_email_notif','1');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_prefs'])) {
    $dark = isset($_POST['pref_dark']) ? '1' : '0';
    $notif = isset($_POST['pref_email_notif']) ? '1' : '0';
    if (isset($existing['pref_dark'])) {
        $upd = $conn->prepare("UPDATE admin SET pref_dark=?, pref_email_notif=? WHERE email=? LIMIT 1");
        $upd->bind_param("sss", $dark, $notif, $admin_email);
        if ($upd->execute()) {
            $pref_ok = 'Preferences updated.';
            $admin['pref_dark'] = $dark;
            $admin['pref_email_notif'] = $notif;
        } else {
            $pref_err = 'Failed to update preferences.';
        }
        $upd->close();
    } else {
        set_settings([
            'pref_'.$admin_email.'_dark' => $dark,
            'pref_'.$admin_email.'_email_notif' => $notif
        ]);
        $pref_ok = 'Preferences updated.';
    }
    $pref_dark = $dark;
    $pref_email_notif = $notif;
}
// Emergency Lock handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_emergency_lock'])) {
    $msg = trim($_POST['emergency_lock_msg'] ?? '');
    $active = isset($_POST['enable_lock']) ? '1' : '0';
    set_settings(['emergency_lock' => $active, 'emergency_lock_msg' => $msg]);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['release_lock_now'])) {
    set_settings(['emergency_lock' => '0']);
}

// Reminders handler
$rem_ok = $rem_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reminders'])) {
    $brevoKey = trim($_POST['brevo_key'] ?? '');
    $waToken  = trim($_POST['wa_token'] ?? '');
    $waPhone  = trim($_POST['wa_phone'] ?? '');
    $tplEmailSub = trim($_POST['tpl_email_sub'] ?? 'Payment Reminder');
    $tplEmailBody = trim($_POST['tpl_email_body'] ?? 'Dear {{name}}, your due amount is ₹{{amount}}.');
    $tplWa = trim($_POST['tpl_wa'] ?? 'Hi {{name}}, ₹{{amount}} is due.');
    if ($tplEmailSub === '' || $tplEmailBody === '') {
        $rem_err = 'Email subject and body are required.';
    } else {
        set_settings([
            'rem_brevo_key' => $brevoKey,
            'rem_wa_token' => $waToken,
            'rem_wa_phone' => $waPhone,
            'tpl_email_sub' => $tplEmailSub,
            'tpl_email_body' => $tplEmailBody,
            'tpl_wa' => $tplWa
        ]);
        $rem_ok = 'Reminder settings updated.';
    }
}

// Localization handler
$loc_ok = $loc_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_localization'])) {
    $currency = trim($_POST['currency'] ?? '₹');
    $datefmt = trim($_POST['date_format'] ?? 'DD-MM-YYYY');
    $numfmt = trim($_POST['number_format'] ?? 'IN');
    if ($currency === '') {
        $loc_err = 'Currency symbol is required.';
    } else {
        set_settings(['loc_currency' => $currency, 'loc_date_format' => $datefmt, 'loc_number_format' => $numfmt]);
        $loc_ok = 'Localization settings updated.';
    }
}

$upi_ok = $upi_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_upi'])) {
    $upi_id = trim($_POST['upi_id'] ?? '');
    $upi_name = trim($_POST['upi_name'] ?? '');
    if ($upi_id === '') {
        $upi_err = 'UPI ID is required.';
    } else {
        set_settings(['upi_id' => $upi_id, 'upi_name' => $upi_name]);
        $upi_ok = 'UPI settings updated.';
    }
}

// UPI Settings handler
$upi_ok = $upi_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_upi'])) {
    $vpa   = trim($_POST['vpa'] ?? '');
    $payee = trim($_POST['payee'] ?? '');
    $mobile= trim($_POST['mobile'] ?? '');
    if ($vpa === '' || $payee === '') {
        $upi_err = 'VPA and Payee Name are required.';
    } else {
        try {
            $conn->query("CREATE TABLE IF NOT EXISTS app_settings (`key` VARCHAR(64) PRIMARY KEY, `value` TEXT NOT NULL, `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $stmt = $conn->prepare("INSERT INTO app_settings (`key`,`value`) VALUES
                ('upi_vpa', ?), ('upi_payee', ?), ('upi_mobile', ?)
                ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            $stmt->bind_param('sss', $vpa, $payee, $mobile);
            $stmt->execute();
            $stmt->close();
            $upi_ok = 'UPI details saved.';
        } catch (Throwable $e) {
            $upi_err = 'Failed to save UPI details: ' . $e->getMessage();
        }
        // Handle QR upload (optional)
        if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg','image/png','image/webp'];
            if (!in_array($_FILES['qr_image']['type'], $allowed)) {
                $upi_err = 'QR image must be JPG/PNG/WEBP';
            } else {
                $dir = __DIR__ . '/uploads/';
                if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
                $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
                $fname = 'upi_admin_qr_'.time().'.'.$ext;
                $path = $dir.$fname;
                if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $path)) {
                    $rel = 'uploads/'.$fname;
                    try {
                        $stmt = $conn->prepare("INSERT INTO app_settings (`key`,`value`) VALUES ('upi_qr_path', ?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
                        $stmt->bind_param('s', $rel);
                        $stmt->execute();
                        $stmt->close();
                        $upi_ok = ($upi_ok ? $upi_ok.' ' : '') . 'QR image uploaded.';
                    } catch (Throwable $e) { $upi_err = 'Saved details, but failed QR path: '.$e->getMessage(); }
                } else {
                    $upi_err = 'Failed to save QR image.';
                }
            }
        }
    }
}

// DELETE ACCOUNT handler
$del_ok = $del_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_account'])) {
    $del_pass = $_POST['del_password'] ?? '';
    $del_text = $_POST['del_confirm_text'] ?? '';
    if ($del_pass==='' || $del_text==='') {
        $del_err = 'Please enter your password and type the confirmation text.';
    } elseif (strtolower(trim($del_text)) !== 'delete my account') {
        $del_err = 'Confirmation text must be: DELETE MY ACCOUNT';
    } else {
        $stmt = $conn->prepare('SELECT password FROM admin WHERE email=? LIMIT 1');
        $stmt->bind_param('s',$admin_email); $stmt->execute(); $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            if (!password_verify($del_pass, $row['password'])) {
                $del_err = 'Incorrect password.';
            } else {
                $stmt2 = $conn->prepare('DELETE FROM admin WHERE email=? LIMIT 1');
                $stmt2->bind_param('s',$admin_email);
                if ($stmt2->execute()) {
                    $del_ok = 'Your account has been deleted.';
                    session_destroy();
                    echo '<script>setTimeout(function(){ window.location.href="/index.php"; }, 2000);</script>';
                } else { $del_err = 'Failed to delete account: '.$stmt2->error; }
                $stmt2->close();
            }
        } else { $del_err = 'User record not found.'; }
        $stmt->close();
    }
}
$brevo_ok = $brevo_err = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_brevo'])) {
    $new_api = trim($_POST['brevo_api_key'] ?? '');
    $new_sender = trim($_POST['brevo_sender'] ?? '');
    if ($new_api === '' || $new_sender === '') {
        $brevo_err = 'API key and sender email are required.';
    } else {
        set_settings(['brevo_sender' => $new_sender, 'brevo_api_key' => $new_api]);
        $brevo_ok = 'Brevo email settings updated.';
    }
}

// Fix: Initialize all user count and active user variables to avoid undefined warnings
$contact_count = 0;
$attendence_count = 0;
$active_workers = 0;
$active_contacts = 0;
$active_admins = 0;
$active_attendence = 0;

// User counts per main page (initialize to avoid undefined warnings)
$total_users = 0;
$active_total = 0;

// Total users count
try { $user_count = $conn->query("SELECT COUNT(*) FROM workers")->fetch_row()[0]; } catch (Throwable $e) {}
try { $contact_count = $conn->query("SELECT COUNT(*) FROM contact")->fetch_row()[0]; } catch (Throwable $e) {}
try { $admin_count = $conn->query("SELECT COUNT(*) FROM admin")->fetch_row()[0]; } catch (Throwable $e) {}
try { $attendence_count = $conn->query("SELECT COUNT(*) FROM attendence")->fetch_row()[0]; } catch (Throwable $e) {}

// Active users count (last 10 min)
try { $active_workers = $conn->query("SELECT COUNT(*) FROM workers WHERE last_active >= NOW() - INTERVAL 10 MINUTE")->fetch_row()[0]; } catch (Throwable $e) {}
try { $active_contacts = $conn->query("SELECT COUNT(*) FROM contact WHERE last_active >= NOW() - INTERVAL 10 MINUTE")->fetch_row()[0]; } catch (Throwable $e) {}
try { $active_admins = $conn->query("SELECT COUNT(*) FROM admin WHERE last_active >= NOW() - INTERVAL 10 MINUTE")->fetch_row()[0]; } catch (Throwable $e) {}
try { $active_attendence = $conn->query("SELECT COUNT(*) FROM attendence WHERE last_active >= NOW() - INTERVAL 10 MINUTE")->fetch_row()[0]; } catch (Throwable $e) {}

// Calculate total and active users for the dashboard
$total_users = $user_count + $contact_count + $admin_count + $attendence_count;
$active_total = $active_workers + $active_contacts + $active_admins + $active_attendence;

// --- Render Settings Page ---
?>
<style>
.settings-container { max-width: 1000px; }
.settings-card { min-height: 500px; background: #fff; }
.nav-pills .nav-link { text-align: left; padding: 0.5rem 1rem; border-radius: 0.25rem; margin-bottom: 5px; }
.nav-pills .nav-link.active, .nav-pills .nav-link:hover { background-color: var(--bs-primary); color: white; }
.profile-photo-area { position: relative; display: inline-block; }
.profile-photo-area img { border: 3px solid #f8f9fa; box-shadow: 0 0 10px rgba(0,0,0,0.05); }
.form-label { font-size: 0.85rem; font-weight: 500; margin-bottom: 0.2rem; }
</style>
<div class="container py-4 settings-container">
    <h2 class="fw-bold mb-4"><i class="fas fa-cog me-2 text-primary"></i>New Settings</h2>
    <div class="card shadow settings-card">
        <div class="card-body p-0">
            <div class="row g-0">
                <div class="col-md-3 border-end p-3" style="background:#f8f9fa;">
                    <ul class="nav nav-pills flex-column" id="settingsTabs" role="tablist">
                        <li class="nav-item" role="presentation"><button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button" role="tab"><i class="fas fa-user-circle me-2"></i> Profile Info</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="professional-tab" data-bs-toggle="pill" data-bs-target="#professional" type="button" role="tab"><i class="fas fa-briefcase me-2"></i> Professional</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="account-tab" data-bs-toggle="pill" data-bs-target="#account" type="button" role="tab"><i class="fas fa-lock me-2"></i> Security & Prefs</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="system-tab" data-bs-toggle="pill" data-bs-target="#system" type="button" role="tab"><i class="fas fa-server me-2"></i> System Tools</button></li>
                        <!-- Hidden tabs for now -->
                        <li class="nav-item" role="presentation"><button class="nav-link" id="emergency-tab" data-bs-toggle="pill" data-bs-target="#emergency" type="button" role="tab"><i class="fas fa-lock me-2 text-danger"></i> Emergency Lock</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="reminders-tab" data-bs-toggle="pill" data-bs-target="#reminders" type="button" role="tab"><i class="bi bi-megaphone me-2"></i> Reminders</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="localization-tab" data-bs-toggle="pill" data-bs-target="#localization" type="button" role="tab"><i class="bi bi-translate me-2"></i> Localization</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="brevo-tab" data-bs-toggle="pill" data-bs-target="#brevo" type="button" role="tab"><i class="bi bi-envelope-at me-2"></i> Brevo Email Settings</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="upi-tab" data-bs-toggle="pill" data-bs-target="#upi" type="button" role="tab"><i class="bi bi-qr-code me-2"></i> UPI Settings</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="delete-tab" data-bs-toggle="pill" data-bs-target="#deleteaccount" type="button" role="tab"><i class="bi bi-trash me-2"></i> DELETE ACCOUNT</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="controlpanels-tab" data-bs-toggle="pill" data-bs-target="#controlpanels" type="button" role="tab"><i class="fas fa-tools me-2"></i> Control Pnals</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="sessionmanagement-tab" data-bs-toggle="pill" data-bs-target="#sessionmanagement" type="button" role="tab"><i class="bi bi-clock-history me-2"></i> Session Manag.</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="performance-tab" data-bs-toggle="pill" data-bs-target="#performance" type="button" role="tab"><i class="fas fa-chart-line me-2"></i> System Performance</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="advanced-tab" data-bs-toggle="pill" data-bs-target="#advanced" type="button" role="tab"><i class="fas fa-sliders-h me-2"></i> Advanced Settings</button></li>
                        <li class="nav-item" role="presentation"><button class="nav-link" id="attendance-tab" data-bs-toggle="pill" data-bs-target="#attendance" type="button" role="tab"><i class="bi bi-calendar-check me-2"></i> Attendance Setting</button></li>
                    </ul>
                </div>

                <div class="col-md-9 p-4">
                    <div class="tab-content">
                        <div class="tab-pane fade" id="advanced" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="fas fa-sliders-h me-2"></i>Advanced Settings</h5>
                            <form method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_advanced" value="1">
                                <!-- 1. Password Policy -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Password Policy</label>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <input type="number" class="form-control" name="policy_min_length" min="6" max="32" value="<?php echo htmlspecialchars(get_setting('policy_min_length','8')); ?>" placeholder="Min Length">
                                        </div>
                                        <div class="col-md-3">
                                            <input type="checkbox" name="policy_special" value="1" <?php echo get_setting('policy_special','0')==='1'?'checked':''; ?>> Require Special Character
                                        </div>
                                    </div>
                                </div>
                                <!-- 2. Session Timeout -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Session Timeout (minutes)</label>
                                    <div class="mb-2">Set admin session timeout</div>
                                    <input type="number" class="form-control mb-2" name="admin_session_timeout" min="5" max="525600" value="<?php echo htmlspecialchars(get_setting('admin_session_timeout', get_setting('global_session_timeout','1440'))); ?>">
                                    <div class="mb-2">Set timeout for Worker / Attendence / Contact panels</div>
                                    <input type="number" class="form-control" name="panels_session_timeout" min="5" max="525600" value="<?php echo htmlspecialchars(get_setting('panels_session_timeout', get_setting('global_session_timeout','1440'))); ?>">
                                    <small class="text-muted">Two separate values: admin panel timeout, and a shared timeout for worker/contact/attendence.</small>
                                </div>
                                <!-- 3. Maintenance Mode -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Maintenance Mode</label>
                                    <input type="checkbox" name="maintenance_mode" value="1" <?php echo get_setting('maintenance_mode','0')==='1'?'checked':''; ?>> Enable
                                    <input type="text" class="form-control mt-2" name="maintenance_msg" value="<?php echo htmlspecialchars(get_setting('maintenance_msg','Site under maintenance.')); ?>" placeholder="Maintenance Message">
                                </div>
                                <!-- 4. Email/SMS Notification Settings -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Notification Settings</label>
                                    <input type="email" class="form-control mb-2" name="notif_email" value="<?php echo htmlspecialchars(get_setting('notif_email','')); ?>" placeholder="Sender Email">
                                    <input type="text" class="form-control mb-2" name="notif_sms_api" value="<?php echo htmlspecialchars(get_setting('notif_sms_api','')); ?>" placeholder="SMS Gateway API">
                                    <button type="button" class="btn btn-outline-primary btn-sm">Test Notification</button>
                                </div>
                                <!-- 5. Data Export/Import Tools -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Data Export/Import</label>
                                    <button type="button" class="btn btn-outline-success btn-sm">Export Users CSV</button>
                                    <input type="file" class="form-control mt-2" name="import_csv" accept=".csv">
                                </div>
                                <!-- 6. Audit Log Viewer -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Audit Log Viewer</label>
                                    <button type="button" class="btn btn-outline-secondary btn-sm">View Logs</button>
                                </div>
                                <!-- 7. Theme/Appearance Settings -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Theme/Appearance</label>
                                    <select class="form-select" name="theme">
                                        <option value="light" <?php echo get_setting('theme','light')==='light'?'selected':''; ?>>Light</option>
                                        <option value="dark" <?php echo get_setting('theme','light')==='dark'?'selected':''; ?>>Dark</option>
                                        <option value="custom" <?php echo get_setting('theme','light')==='custom'?'selected':''; ?>>Custom</option>
                                    </select>
                                </div>
                                <!-- 8. API Key Management -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">API Key Management</label>
                                    <input type="text" class="form-control mb-2" name="api_key" value="<?php echo htmlspecialchars(get_setting('api_key','')); ?>" placeholder="API Key">
                                    <button type="button" class="btn btn-outline-primary btn-sm">Generate New Key</button>
                                </div>
                                <!-- 9. IP Whitelist/Blacklist -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">IP Whitelist/Blacklist</label>
                                    <textarea class="form-control" name="ip_list" rows="2" placeholder="One IP per line..."><?php echo htmlspecialchars(get_setting('ip_list','')); ?></textarea>
                                </div>
                                <!-- 10. Custom Dashboard Widgets -->
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Custom Dashboard Widgets</label>
                                    <textarea class="form-control" name="dashboard_widgets" rows="2" placeholder="Widget names, comma separated..."><?php echo htmlspecialchars(get_setting('dashboard_widgets','')); ?></textarea>
                                </div>
                                <button class="btn btn-primary btn-sm mt-3" type="submit"><i class="bi bi-check2 me-1"></i> Save Advanced Settings</button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="attendance" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-calendar-check me-2"></i>Attendance Setting</h5>
                            <?php
                            $attendance_ok = $attendance_err = null;
                            $attendance_mode = get_setting('attendance_mode', 'simple');
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance_setting'])) {
                                $mode = $_POST['attendance_mode'] ?? 'simple';
                                if (!in_array($mode, ['simple','twotime'])) {
                                    $attendance_err = 'Invalid mode.';
                                } else {
                                    set_settings(['attendance_mode' => $mode]);
                                    $attendance_ok = 'Attendance setting updated.';
                                    $attendance_mode = $mode;
                                }
                            }
                            ?>
                            <?php if (!empty($attendance_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($attendance_ok); ?></div><?php endif; ?>
                            <?php if (!empty($attendance_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($attendance_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="save_attendance_setting" value="1" />
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Attendance Mode</label>
                                    <select class="form-select" name="attendance_mode">
                                        <option value="simple" <?php echo $attendance_mode==='simple'?'selected':''; ?>>Simple (Any time, Full Day)</option>
                                        <option value="twotime" <?php echo $attendance_mode==='twotime'?'selected':''; ?>>Two-time (Entry/Exit, Half/Full Day)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <b>Simple:</b> Attendance can be marked any time, always counted as Full Day.<br>
                                        <b>Two-time:</b> <ul class="mb-0"><li>Entry allowed 5:00 AM to 11:00 AM (India time)</li><li>If exit between 11:50 AM and 2:00 PM, Half Day is marked</li><li>If exit between 4:00 PM and 7:00 PM or after, Full Day is marked</li></ul>
                                    </div>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Attendance Setting</button>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="performance" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="fas fa-chart-line me-2"></i>System Performance</h5>
                            <?php
                            // Panel health status
                            $panel_health = [
                                'Admin' => get_setting('panel_down_admin','0')==='1' ? 'DOWN' : 'UP',
                                'Worker' => get_setting('panel_down_worker','0')==='1' ? 'DOWN' : 'UP',
                                'Contact' => get_setting('panel_down_contact','0')==='1' ? 'DOWN' : 'UP',
                                'Attendence' => get_setting('panel_down_attendence','0')==='1' ? 'DOWN' : 'UP'
                            ];
                            // Top active users (last 10 min)
                            $top_admins = [];
                            $top_workers = [];
                            $top_contacts = [];
                            $top_attendence = [];
                            try {
                                $res = $conn->query("SELECT name,email,last_active FROM admin WHERE last_active >= NOW() - INTERVAL 10 MINUTE ORDER BY last_active DESC LIMIT 5");
                                while($row = $res->fetch_assoc()) $top_admins[] = $row;
                            } catch (Throwable $e) {}
                            try {
                                $res = $conn->query("SELECT name,email,last_active FROM workers WHERE last_active >= NOW() - INTERVAL 10 MINUTE ORDER BY last_active DESC LIMIT 5");
                                while($row = $res->fetch_assoc()) $top_workers[] = $row;
                            } catch (Throwable $e) {}
                            try {
                                $res = $conn->query("SELECT name,email,last_active FROM contact WHERE last_active >= NOW() - INTERVAL 10 MINUTE ORDER BY last_active DESC LIMIT 5");
                                while($row = $res->fetch_assoc()) $top_contacts[] = $row;
                            } catch (Throwable $e) {}
                            try {
                                $res = $conn->query("SELECT name,email,last_active FROM attendence WHERE last_active >= NOW() - INTERVAL 10 MINUTE ORDER BY last_active DESC LIMIT 5");
                                while($row = $res->fetch_assoc()) $top_attendence[] = $row;
                            } catch (Throwable $e) {}
                            // Recent logins
                            $recent_logins = [];
                            try {
                                $res = $conn->query("SELECT 'Admin' as panel, name, email, last_active FROM admin WHERE last_active >= NOW() - INTERVAL 1 DAY UNION ALL SELECT 'Worker', name, email, last_active FROM workers WHERE last_active >= NOW() - INTERVAL 1 DAY UNION ALL SELECT 'Contact', name, email, last_active FROM contact WHERE last_active >= NOW() - INTERVAL 1 DAY UNION ALL SELECT 'Attendence', name, email, last_active FROM attendence WHERE last_active >= NOW() - INTERVAL 1 DAY ORDER BY last_active DESC LIMIT 10");
                                while($row = $res->fetch_assoc()) $recent_logins[] = $row;
                            } catch (Throwable $e) {}
                            // Usage arrays initialization (fix for undefined variable warning)
                            if (!isset($usage_labels)) $usage_labels = array();
                            if (!isset($usage_admin)) $usage_admin = array_fill(0,13,0);
                            if (!isset($usage_worker)) $usage_worker = array_fill(0,13,0);
                            if (!isset($usage_contact)) $usage_contact = array_fill(0,13,0);
                            if (!isset($usage_attendence)) $usage_attendence = array_fill(0,13,0);
                            $trend_now = array_sum([$usage_admin[12],$usage_worker[12],$usage_contact[12],$usage_attendence[12]]);
                            $trend_prev = array_sum([$usage_admin[0],$usage_worker[0],$usage_contact[0],$usage_attendence[0]]);
                            $trend_percent = $trend_prev ? round(($trend_now-$trend_prev)/$trend_prev*100,1) : 0;
                            ?>
                            <div class="row mb-3 g-3">
                                <div class="col-md-3">
                                    <div class="card shadow-sm p-3 mb-2 text-center">
                                        <div class="fw-bold">Total Users</div>
                                        <div class="display-6 text-primary"><?php echo $total_users; ?></div>
                                        <div class="small text-muted">All Panels</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card shadow-sm p-3 mb-2 text-center">
                                        <div class="fw-bold">Active Users (10 min)</div>
                                        <div class="display-6 text-success"><?php echo $active_total; ?></div>
                                        <div class="small text-muted">Live Now</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card shadow-sm p-3 mb-2 text-center">
                                        <div class="fw-bold">Usage Trend</div>
                                        <div class="display-6 <?php echo $trend_percent>=0?'text-info':'text-danger'; ?>">
                                            <?php echo $trend_percent>=0?'<i class="bi bi-arrow-up"></i>':'<i class="bi bi-arrow-down"></i>'; ?> <?php echo abs($trend_percent); ?>%
                                        </div>
                                        <div class="small text-muted">Last 24h</div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card shadow-sm p-3 mb-2 text-center">
                                        <div class="fw-bold">PHP Version</div>
                                        <div class="display-6 text-warning"><?php echo phpversion(); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 g-3">
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Panel Health Status</div>
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead><tr><th>Panel</th><th>Status</th></tr></thead>
                                            <tbody>
                                                <?php foreach($panel_health as $panel=>$status): ?>
                                                <tr>
                                                    <td><?php echo $panel; ?></td>
                                                    <td><span class="badge bg-<?php echo $status==='UP'?'success':'danger'; ?>"><?php echo $status; ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Top Active Users (10 min)</div>
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead><tr><th>Panel</th><th>User</th><th>Email</th><th>Last Active</th></tr></thead>
                                            <tbody>
                                                <?php foreach([['Admin',$top_admins],['Worker',$top_workers],['Contact',$top_contacts],['Attendence',$top_attendence]] as [$panel,$list]):
                                                    foreach($list as $u): ?>
                                                    <tr>
                                                        <td><?php echo $panel; ?></td>
                                                        <td><?php echo htmlspecialchars($u['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                                        <td><?php echo htmlspecialchars($u['last_active']); ?></td>
                                                    </tr>
                                                <?php endforeach; endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 g-3">
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Recent Logins (24h)</div>
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead><tr><th>Panel</th><th>User</th><th>Email</th><th>Last Active</th></tr></thead>
                                            <tbody>
                                                <?php foreach($recent_logins as $r): ?>
                                                <tr>
                                                    <td><?php echo $r['panel']; ?></td>
                                                    <td><?php echo htmlspecialchars($r['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($r['last_active']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Export Usage Data</div>
                                        <form method="POST">
                                            <button class="btn btn-outline-primary btn-sm" name="export_usage_csv" value="1"><i class="bi bi-download me-1"></i> Export CSV</button>
                                        </form>
                                        <?php
                                        if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['export_usage_csv'])) {
                                            header('Content-Type: text/csv');
                                            header('Content-Disposition: attachment; filename="usage_data.csv"');
                                            $out = fopen('php://output', 'w');
                                            fputcsv($out,['Hour','Admin','Worker','Contact','Attendence']);
                                            for($i=0;$i<count($usage_labels);$i++) {
                                                fputcsv($out,[$usage_labels[$i],$usage_admin[$i],$usage_worker[$i],$usage_contact[$i],$usage_attendence[$i]]);
                                            }
                                            fclose($out); exit;
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 g-3">
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Active Users Per Page</div>
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead><tr><th>Page</th><th>Active Users</th></tr></thead>
                                            <tbody>
                                                <tr><td>Admin Panel</td><td><?php echo $active_admins; ?></td></tr>
                                                <tr><td>Worker Panel</td><td><?php echo $active_workers; ?></td></tr>
                                                <tr><td>Contact Panel</td><td><?php echo $active_contacts; ?></td></tr>
                                                <tr><td>Attendence Panel</td><td><?php echo $active_attendence; ?></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="fw-bold mb-2">Total Users Per Page</div>
                                        <table class="table table-bordered table-sm mb-0">
                                            <thead><tr><th>Page</th><th>Total Users</th></tr></thead>
                                            <tbody>
                                                <tr><td>Admin Panel</td><td><?php echo $admin_count; ?></td></tr>
                                                <tr><td>Worker Panel</td><td><?php echo $user_count; ?></td></tr>
                                                <tr><td>Contact Panel</td><td><?php echo $contact_count; ?></td></tr>
                                                <tr><td>Attendence Panel</td><td><?php echo $attendence_count; ?></td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2 g-3">
                                <div class="col-md-4"><span class="text-muted">Server Load:</span> <?php echo function_exists('sys_getloadavg') ? implode(', ', sys_getloadavg()) : 'N/A'; ?></div>
                                <div class="col-md-4"><span class="text-muted">DB Size:</span> <?php
                                    $dbsize = 'N/A';
                                    try {
                                        $res = $conn->query("SHOW TABLE STATUS");
                                        $size = 0;
                                        while ($row = $res->fetch_assoc()) {
                                            $size += $row['Data_length'] + $row['Index_length'];
                                        }
                                        $dbsize = round($size/1024/1024,2) . ' MB';
                                    } catch (Throwable $e) {}
                                    echo $dbsize;
                                ?></div>
                                <div class="col-md-4"><span class="text-muted">Server Time:</span> <?php echo date('Y-m-d H:i:s'); ?></div>
                            </div>
                            <div class="row mb-4 g-3">
                                <div class="col-12">
                                    <div class="card shadow-sm p-3 mb-2">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <div class="fw-bold">Website Usage Graph</div>
                                            <div>
                                                <select id="graphType" class="form-select form-select-sm d-inline-block w-auto">
                                                    <option value="bar">Bar Chart</option>
                                                    <option value="line">Line Chart</option>
                                                </select>
                                                <select id="panelFilter" class="form-select form-select-sm d-inline-block w-auto ms-2">
                                                    <option value="all">All Panels</option>
                                                    <option value="admin">Admin</option>
                                                    <option value="worker">Worker</option>
                                                    <option value="contact">Contact</option>
                                                    <option value="attendence">Attendence</option>
                                                </select>
                                                <button class="btn btn-outline-secondary btn-sm ms-2" id="refreshUsage"><i class="bi bi-arrow-repeat"></i> Refresh</button>
                                            </div>
                                        </div>
                                        <canvas id="usageChart" height="120"></canvas>
                                    </div>
                                </div>
                            </div>
                            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var ctx = document.getElementById('usageChart').getContext('2d');
                                var chartType = 'bar';
                                var panelFilter = 'all';
                                var usageLabels = <?php echo json_encode($usage_labels); ?>;
                                var usageAdmin = <?php echo json_encode($usage_admin); ?>;
                                var usageWorker = <?php echo json_encode($usage_worker); ?>;
                                var usageContact = <?php echo json_encode($usage_contact); ?>;
                                var usageAttendence = <?php echo json_encode($usage_attendence); ?>;
                                var datasets = [
                                    {
                                        label: 'Admin',
                                        data: usageAdmin,
                                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                                        borderColor: 'rgba(54, 162, 235, 1)',
                                        fill: true
                                    },
                                    {
                                        label: 'Worker',
                                        data: usageWorker,
                                        backgroundColor: 'rgba(75, 192, 192, 0.7)',
                                        borderColor: 'rgba(75, 192, 192, 1)',
                                        fill: true
                                    },
                                    {
                                        label: 'Contact',
                                        data: usageContact,
                                        backgroundColor: 'rgba(255, 206, 86, 0.7)',
                                        borderColor: 'rgba(255, 206, 86, 1)',
                                        fill: true
                                    },
                                    {
                                        label: 'Attendence',
                                        data: usageAttendence,
                                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                                        borderColor: 'rgba(255, 99, 132, 1)',
                                        fill: true
                                    }
                                ];
                                function getFilteredDatasets() {
                                    if (panelFilter==='all') return datasets;
                                    return datasets.filter(d=>d.label.toLowerCase()===panelFilter);
                                }
                                var usageChart = new Chart(ctx, {
                                    type: chartType,
                                    data: {
                                        labels: usageLabels,
                                        datasets: getFilteredDatasets()
                                    },
                                    options: {
                                        responsive: true,
                                        animation: { duration: 1200 },
                                        plugins: {
                                            legend: { position: 'top' },
                                            title: { display: true, text: 'Active Users Per Page (Last 12 Hours)' }
                                        },
                                        scales: {
                                            x: { stacked: true },
                                            y: { stacked: true, beginAtZero: true }
                                        }
                                    }
                                });
                                document.getElementById('graphType').addEventListener('change', function() {
                                    chartType = this.value;
                                    usageChart.config.type = chartType;
                                    usageChart.update();
                                });
                                document.getElementById('panelFilter').addEventListener('change', function() {
                                    panelFilter = this.value;
                                    usageChart.data.datasets = getFilteredDatasets();
                                    usageChart.update();
                                });
                                document.getElementById('refreshUsage').addEventListener('click', function() {
                                    location.reload();
                                });
                            });
                            </script>
                        </div>
                        <div class="tab-pane fade" id="upi" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-qr-code me-2"></i>UPI Receiver Settings</h5>
                            <?php if (!empty($upi_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($upi_ok); ?></div><?php endif; ?>
                            <?php if (!empty($upi_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($upi_err); ?></div><?php endif; ?>
                            <form method="POST" enctype="multipart/form-data" class="row g-3">
                                <input type="hidden" name="save_upi" value="1" />
                                <div class="col-md-4">
                                    <label class="form-label">UPI VPA (ID)</label>
                                    <input type="text" class="form-control form-control-sm" name="vpa" value="<?php echo htmlspecialchars(get_setting('upi_vpa','')); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payee Name</label>
                                    <input type="text" class="form-control form-control-sm" name="payee" value="<?php echo htmlspecialchars(get_setting('upi_payee','')); ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Mobile Number (optional)</label>
                                    <input type="text" class="form-control form-control-sm" name="mobile" value="<?php echo htmlspecialchars(get_setting('upi_mobile','')); ?>" placeholder="e.g. 98xxxxxx01">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Upload/Replace QR Image (JPG/PNG/WEBP)</label>
                                    <input type="file" class="form-control form-control-sm" name="qr_image" accept="image/jpeg,image/png,image/webp">
                                    <small class="text-muted">This QR will show on the UPI payment page.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label d-block">Current QR</label>
                                    <?php $UPI_QR_PATH = get_setting('upi_qr_path',''); ?>
                                    <?php if (!empty($UPI_QR_PATH)): ?>
                                      <img src="<?php echo htmlspecialchars($UPI_QR_PATH); ?>" alt="Current QR" style="max-height:160px;border:1px solid #eee;border-radius:8px;" />
                                    <?php else: ?>
                                      <span class="text-muted">No QR uploaded.</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save UPI Settings</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="deleteaccount" role="tabpanel">
                            <h5 class="mb-3 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Delete Account</h5>
                            <?php if (!empty($del_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($del_ok); ?></div><?php endif; ?>
                            <?php if (!empty($del_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($del_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="delete_account" value="1">
                                <div class="col-md-6">
                                    <label class="form-label">Enter Password</label>
                                    <input type="password" class="form-control form-control-sm" name="del_password" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Type: <span class="fw-bold text-danger">DELETE MY ACCOUNT</span></label>
                                    <input type="text" class="form-control form-control-sm" name="del_confirm_text" placeholder="DELETE MY ACCOUNT" required>
                                </div>
                                <div class="col-12 mt-2">
                                    <button class="btn btn-danger btn-sm" type="submit" onclick="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone!')"><i class="fas fa-exclamation-triangle me-1"></i> Delete My Account</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="controlpanels" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="fas fa-tools me-2"></i>Control Pnals</h5>
                            <?php if (!empty($ctrl_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($ctrl_ok); ?></div><?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_control_panels" value="1">
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Worker Panel</label>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="panel_down_worker" name="panel_down_worker" value="1" <?php echo get_setting('panel_down_worker','0')==='1'?'checked':''; ?>>
                                                <label class="form-check-label" for="panel_down_worker">Set Worker panel DOWN</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="panel_down_msg_worker" value="<?php echo htmlspecialchars(get_setting('panel_down_msg_worker','')); ?>" placeholder="Message shown to visitors">
                                        </div>
                                        <div class="col-md-3">
                                            <?php $wu = intval(get_setting('panel_down_until_worker','0')); $wrem = $wu > time() ? ceil(($wu - time())/60) : 0; $wdur = intval(get_setting('panel_down_duration_worker','0')); ?>
                                            <input type="number" class="form-control" name="panel_down_minutes_worker" min="0" value="<?php echo $wdur ?: $wrem; ?>" placeholder="Minutes">
                                            <div class="mt-1">
                                                <?php if ($wdur>0): ?><small class="text-muted">Scheduled: <?php echo $wdur; ?> min</small><?php endif; ?>
                                                <div id="ctrl_worker_remaining" class="small text-muted"><?php if ($wu>time()): $remS = max(0,$wu-time()); $rM = floor($remS/60); $rS = $remS%60; echo 'Remaining: '.$rM.'m '.($rS<10?'0'.$rS:$rS).'s'; else: echo ''; endif; ?></div>
                                                <div id="ctrl_worker_current_time" class="small text-muted mt-1">Server time: <?php echo date('Y-m-d H:i:s'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Attendence Panel</label>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="panel_down_attendence" name="panel_down_attendence" value="1" <?php echo get_setting('panel_down_attendence','0')==='1'?'checked':''; ?>>
                                                <label class="form-check-label" for="panel_down_attendence">Set Attendence panel DOWN</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="panel_down_msg_attendence" value="<?php echo htmlspecialchars(get_setting('panel_down_msg_attendence','')); ?>" placeholder="Message shown to visitors">
                                        </div>
                                        <div class="col-md-3">
                                            <?php $au = intval(get_setting('panel_down_until_attendence','0')); $arem = $au > time() ? ceil(($au - time())/60) : 0; $adur = intval(get_setting('panel_down_duration_attendence','0')); ?>
                                            <input type="number" class="form-control" name="panel_down_minutes_attendence" min="0" value="<?php echo $adur ?: $arem; ?>" placeholder="Minutes">
                                            <div class="mt-1">
                                                <?php if ($adur>0): ?><small class="text-muted">Scheduled: <?php echo $adur; ?> min</small><?php endif; ?>
                                                <div id="ctrl_attendence_remaining" class="small text-muted"><?php if ($au>time()): $remS = max(0,$au-time()); $rM = floor($remS/60); $rS = $remS%60; echo 'Remaining: '.$rM.'m '.($rS<10?'0'.$rS:$rS).'s'; else: echo ''; endif; ?></div>
                                                <div id="ctrl_attendence_current_time" class="small text-muted mt-1">Server time: <?php echo date('Y-m-d H:i:s'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3 p-3 border rounded">
                                    <label class="form-label fw-bold">Contact Panel</label>
                                    <div class="row g-2 align-items-center">
                                        <div class="col-auto">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" id="panel_down_contact" name="panel_down_contact" value="1" <?php echo get_setting('panel_down_contact','0')==='1'?'checked':''; ?>>
                                                <label class="form-check-label" for="panel_down_contact">Set Contact panel DOWN</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="text" class="form-control" name="panel_down_msg_contact" value="<?php echo htmlspecialchars(get_setting('panel_down_msg_contact','')); ?>" placeholder="Message shown to visitors">
                                        </div>
                                        <div class="col-md-3">
                                            <?php $cu = intval(get_setting('panel_down_until_contact','0')); $crem = $cu > time() ? ceil(($cu - time())/60) : 0; $cdur = intval(get_setting('panel_down_duration_contact','0')); ?>
                                            <input type="number" class="form-control" name="panel_down_minutes_contact" min="0" value="<?php echo $cdur ?: $crem; ?>" placeholder="Minutes">
                                            <div class="mt-1">
                                                <?php if ($cdur>0): ?><small class="text-muted">Scheduled: <?php echo $cdur; ?> min</small><?php endif; ?>
                                                <div id="ctrl_contact_remaining" class="small text-muted"><?php if ($cu>time()): $remS = max(0,$cu-time()); $rM = floor($remS/60); $rS = $remS%60; echo 'Remaining: '.$rM.'m '.($rS<10?'0'.$rS:$rS).'s'; else: echo ''; endif; ?></div>
                                                <div id="ctrl_contact_current_time" class="small text-muted mt-1">Server time: <?php echo date('Y-m-d H:i:s'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button class="btn btn-primary btn-sm mt-2" type="submit">Save Control Pnals</button>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="sessionmanagement" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-clock-history me-2"></i>Session Management</h5>
                            <?php
                            $session_ok = null; $session_err = null;
                            $admin_timeout = intval(get_setting('admin_session_timeout', get_setting('global_session_timeout','1440')));
                            $panels_timeout = intval(get_setting('panels_session_timeout', get_setting('global_session_timeout','1440')));
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_session_timeout'])) {
                                $new_admin = intval($_POST['admin_session_timeout'] ?? $admin_timeout);
                                $new_panels = intval($_POST['panels_session_timeout'] ?? $panels_timeout);
                                if ($new_admin < 5 || $new_admin > 525600 || $new_panels < 5 || $new_panels > 525600) {
                                    $session_err = 'Timeouts must be between 5 and 525600 minutes.';
                                } else {
                                    set_settings(['admin_session_timeout' => $new_admin, 'panels_session_timeout' => $new_panels, 'global_session_timeout' => $new_admin]);
                                    $admin_timeout = $new_admin; $panels_timeout = $new_panels;
                                    $session_ok = 'Session timeouts updated.';
                                }
                            }
                            ?>
                            <?php if ($session_ok): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($session_ok); ?></div><?php endif; ?>
                            <?php if ($session_err): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($session_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3 mb-3" id="sessionTimeoutForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_session_timeout" value="1" />
                                <div class="col-md-6">
                                    <label class="form-label">Admin Session Timeout</label>
                                    <div class="input-group mb-2">
                                        <select class="form-select" id="sessionTimeoutSelect">
                                            <option value="1440">24 hours</option>
                                            <option value="10080">7 days</option>
                                            <option value="43200">30 days</option>
                                            <option value="129600">3 months</option>
                                            <option value="525600">1 year</option>
                                            <option value="custom">Custom</option>
                                        </select>
                                        <input type="number" class="form-control" name="admin_session_timeout" id="sessionTimeoutInput" min="5" max="525600" value="<?php echo htmlspecialchars($admin_timeout); ?>" required>
                                        <span class="input-group-text">minutes</span>
                                    </div>
                                    <small class="text-muted">Set admin session timeout.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Panels Timeout</label>
                                    <input type="number" class="form-control" name="panels_session_timeout" id="panelsTimeoutInput" min="5" max="525600" value="<?php echo htmlspecialchars($panels_timeout); ?>" required>
                                    <small class="text-muted">Shared timeout for Worker / Contact / Attendence.</small>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Timeouts</button>
                                </div>
                            </form>
                            <div class="row g-3 mt-3">
                                <div class="col-md-6">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="close_admin_sessions" value="1" />
                                        <button class="btn btn-danger w-100" type="submit"><i class="bi bi-x-circle me-1"></i> Close All Admin Sessions</button>
                                    </form>
                                </div>
                                <div class="col-md-6">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="close_panel_sessions" value="1" />
                                        <button class="btn btn-warning w-100" type="submit"><i class="bi bi-x-circle me-1"></i> Close All Panel Sessions</button>
                                    </form>
                                </div>
                            </div>
                            <?php
                            // Backend logic for session close buttons
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_admin_sessions'])) {
                                // Destroy all PHP session files for admins
                                $session_dir = ini_get('session.save_path') ?: sys_get_temp_dir();
                                $deleted = 0;
                                if (is_dir($session_dir)) {
                                    foreach (glob($session_dir . '/sess_*') as $sess_file) {
                                        // Optionally, filter by session content for admin sessions only
                                        // For now, delete all session files (forces logout for all users)
                                        if (@unlink($sess_file)) $deleted++;
                                    }
                                }
                                if ($deleted > 0) {
                                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Sessions Closed',text:'All admin sessions closed. (" . $deleted . " sessions destroyed)',showConfirmButton:false,timer:2000});</script>";
                                } else {
                                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Session Error',text:'No admin sessions found or failed to close.',showConfirmButton:false,timer:2000});</script>";
                                }
                            }
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_panel_sessions'])) {
                                // Destroy all PHP session files for panels
                                $session_dir = ini_get('session.save_path') ?: sys_get_temp_dir();
                                $deleted = 0;
                                if (is_dir($session_dir)) {
                                    foreach (glob($session_dir . '/sess_*') as $sess_file) {
                                        // Optionally, filter by session content for panel sessions only
                                        // For now, delete all session files (forces logout for all users)
                                        if (@unlink($sess_file)) $deleted++;
                                    }
                                }
                                if ($deleted > 0) {
                                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'success',title:'Panel Sessions Closed',text:'All panel sessions closed. (" . $deleted . " sessions destroyed)',showConfirmButton:false,timer:2000});</script>";
                                } else {
                                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Panel Session Error',text:'No panel sessions found or failed to close.',showConfirmButton:false,timer:2000});</script>";
                                }
                            }
                            ?>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var select = document.getElementById('sessionTimeoutSelect');
                                var input = document.getElementById('sessionTimeoutInput');
                                // Set select based on input value
                                var preset = [1440, 10080, 43200, 129600, 525600];
                                var val = parseInt(input.value);
                                if (preset.includes(val)) {
                                    select.value = val;
                                    input.readOnly = true;
                                } else {
                                    select.value = 'custom';
                                    input.readOnly = false;
                                }
                                select.addEventListener('change', function() {
                                    if (this.value === 'custom') {
                                        input.readOnly = false;
                                        input.focus();
                                    } else {
                                        input.value = this.value;
                                        input.readOnly = true;
                                    }
                                });
                                input.addEventListener('input', function() {
                                    if (!preset.includes(parseInt(this.value))) {
                                        select.value = 'custom';
                                        input.readOnly = false;
                                    }
                                });
                            });
                            </script>
                        </div>
                        <!-- ...existing code... -->
                        <div class="tab-pane fade show active" id="profile" role="tabpanel">
                            <h5 class="mb-3 text-primary">Personal Details</h5>
                            <?php if (!empty($prof_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($prof_ok); ?></div><?php endif; ?>
                            <?php if (!empty($prof_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($prof_err); ?></div><?php endif; ?>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" id="btnEnterEdit" class="btn btn-outline-primary btn-sm" <?php echo !empty($profile_edit_mode) ? 'style="display:none;"' : ''; ?>>Update Profile</button>
                                <div id="editActions" class="ms-2" <?php echo !empty($profile_edit_mode) ? '' : 'style="display:none;"'; ?>>
                                    <button type="button" id="btnCancelEdit" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    <button form="profileForm" type="submit" class="btn btn-primary btn-sm ms-1">Save Changes</button>
                                </div>
                            </div>
                            <form id="profileForm" class="row g-3" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_profile" value="1" />
                                <div class="col-12 mb-3 d-flex align-items-center">
                                    <div class="profile-photo-area me-4">
                                        <img src="<?php echo isset($admin['photo']) ? htmlspecialchars($admin['photo']) : 'https://randomuser.me/api/portraits/men/1.jpg'; ?>" class="rounded-circle shadow" style="width:100px;height:100px;object-fit:cover;">
                                    </div>
                                    <div>
                                        <label for="profilePhoto" class="form-label">Update Profile Photo</label>
                                        <input type="file" class="form-control form-control-sm" id="profilePhoto" name="profilePhoto" accept="image/jpeg,image/png,image/webp" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Name</label>
                                    <input data-editable type="text" class="form-control form-control-sm" value="<?php echo isset($admin['name']) ? htmlspecialchars($admin['name']) : ''; ?>" name="name" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?> required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email (Login ID)</label>
                                    <input type="email" class="form-control form-control-sm" value="<?php echo isset($admin['email']) ? htmlspecialchars($admin['email']) : ''; ?>" name="email" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" class="form-control form-control-sm" value="<?php echo isset($admin['phone']) ? htmlspecialchars($admin['phone']) : ''; ?>" name="phone" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control form-control-sm" value="<?php echo isset($admin['type']) ? htmlspecialchars($admin['type']) : 'Admin'; ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Aadhaar</label>
                                    <input data-editable type="text" class="form-control form-control-sm" name="aadhaar" value="<?php echo htmlspecialchars($admin['aadhaar'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Date of Birth</label>
                                    <input data-editable type="date" class="form-control form-control-sm" name="dob" value="<?php echo htmlspecialchars($admin['dob'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Gender</label>
                                    <input data-editable type="text" class="form-control form-control-sm" name="gender" value="<?php echo htmlspecialchars($admin['gender'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">City</label>
                                    <input data-editable type="text" class="form-control form-control-sm" name="city" value="<?php echo htmlspecialchars($admin['city'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">State</label>
                                    <input data-editable type="text" class="form-control form-control-sm" name="state" value="<?php echo htmlspecialchars($admin['state'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Address</label>
                                    <input data-editable type="text" class="form-control form-control-sm" name="address" value="<?php echo htmlspecialchars($admin['address'] ?? ''); ?>" <?php echo !empty($profile_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-12 mt-1">
                                    <!-- Save/Cancel shown above in editActions -->
                                </div>
                            </form>
                            <script>
                                (function(){
                                  const btnEnter = document.getElementById('btnEnterEdit');
                                  const btnCancel = document.getElementById('btnCancelEdit');
                                  const editActions = document.getElementById('editActions');
                                  const inputs = document.querySelectorAll('#profileForm [data-editable], #profilePhoto');
                                  function setMode(edit){
                                    inputs.forEach(el=>{ if (el.getAttribute('name') !== 'phone' && el.getAttribute('name') !== 'email') el.disabled = !edit; });
                                    if (btnEnter) btnEnter.style.display = edit ? 'none':'inline-block';
                                    if (editActions) editActions.style.display = edit ? 'inline-block':'none';
                                  }
                                  if (btnEnter) btnEnter.addEventListener('click', ()=> setMode(true));
                                  if (btnCancel) btnCancel.addEventListener('click', ()=> window.location.reload());
                                  // Respect server-decided initial mode
                                  setMode(<?php echo !empty($profile_edit_mode) ? 'true' : 'false'; ?>);
                                })();
                            </script>
                        </div>
                        <div class="tab-pane fade" id="professional" role="tabpanel">
                            <h5 class="mb-3 text-primary">Employment Details</h5>
                            <?php if (!empty($pro_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($pro_ok); ?></div><?php endif; ?>
                            <?php if (!empty($pro_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($pro_err); ?></div><?php endif; ?>
                            <div class="d-flex justify-content-end mb-2">
                                <button type="button" id="btnProEnter" class="btn btn-outline-primary btn-sm" <?php echo !empty($pro_edit_mode) ? 'style="display:none;"' : ''; ?>>Update Professional</button>
                                <div id="proActions" class="ms-2" <?php echo !empty($pro_edit_mode) ? '' : 'style="display:none;"'; ?>>
                                    <button type="button" id="btnProCancel" class="btn btn-outline-secondary btn-sm">Cancel</button>
                                    <button form="professionalForm" type="submit" class="btn btn-primary btn-sm ms-1">Save Changes</button>
                                </div>
                            </div>
                            <form id="professionalForm" class="row g-3" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_professional" value="1" />
                                <div class="col-md-6">
                                    <label class="form-label">Employee ID</label>
                                    <input type="text" class="form-control form-control-sm" value="<?php echo isset($admin['employee_id']) ? htmlspecialchars($admin['employee_id']) : 'N/A'; ?>" disabled>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Designation</label>
                                    <input data-pro-edit type="text" class="form-control form-control-sm" name="designation" value="<?php echo isset($admin['designation']) ? htmlspecialchars($admin['designation']) : ''; ?>" <?php echo !empty($pro_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <input data-pro-edit type="text" class="form-control form-control-sm" name="department" value="<?php echo isset($admin['department']) ? htmlspecialchars($admin['department']) : ''; ?>" <?php echo !empty($pro_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Joining Date</label>
                                    <input data-pro-edit type="date" class="form-control form-control-sm" name="joining_date" value="<?php echo !empty($admin['joining_date']) ? htmlspecialchars(date('Y-m-d', strtotime($admin['joining_date']))) : ''; ?>" <?php echo !empty($pro_edit_mode) ? '' : 'disabled'; ?>>
                                </div>
                            </form>
                            <script>
                                (function(){
                                  const btnEnter = document.getElementById('btnProEnter');
                                  const btnCancel = document.getElementById('btnProCancel');
                                  const actions = document.getElementById('proActions');
                                  const inputs = document.querySelectorAll('#professionalForm [data-pro-edit]');
                                  function setProMode(edit){
                                    inputs.forEach(el=> el.disabled = !edit);
                                    if (btnEnter) btnEnter.style.display = edit ? 'none':'inline-block';
                                    if (actions) actions.style.display = edit ? 'inline-block':'none';
                                  }
                                  if (btnEnter) btnEnter.addEventListener('click', ()=> setProMode(true));
                                  if (btnCancel) btnCancel.addEventListener('click', ()=> window.location.reload());
                                  setProMode(<?php echo !empty($pro_edit_mode) ? 'true' : 'false'; ?>);
                                })();
                            </script>
                        </div>
                        <div class="tab-pane fade" id="account" role="tabpanel">
                            <div class="mb-4 p-3 border rounded bg-light">
                                <h5 class="mb-3 text-primary"><i class="bi bi-shield-lock me-2"></i>Login Security Settings</h5>
                                <?php if (!empty($login_limit_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($login_limit_ok); ?></div><?php endif; ?>
                                <?php if (!empty($login_limit_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($login_limit_err); ?></div><?php endif; ?>
                                <form method="POST" class="row g-3 mb-3">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="save_login_limit" value="1" />
                                    <div class="col-md-6">
                                        <label class="form-label">Max Login Attempts <i class="bi bi-person-lock ms-1"></i></label>
                                        <input type="number" class="form-control" name="login_attempt_limit" min="3" max="50" value="<?php echo htmlspecialchars($LOGIN_ATTEMPT_LIMIT ?? '10'); ?>" required>
                                        <small class="text-muted">Set the maximum allowed login attempts before lockout.</small>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Limit</button>
                                    </div>
                                </form>
                            </div>
                            <div class="mb-4 p-3 border rounded bg-white">
                                <h5 class="mb-3 text-danger"><i class="fas fa-shield-alt me-2"></i>Change Password & Security</h5>
                                <form method="POST" class="mb-4">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="save_2fa" value="1">
                                    <div class="form-check form-switch mb-2">
                                        <?php $admin_2fa = get_setting('admin_2fa','0'); ?>
                                        <input class="form-check-input" type="checkbox" id="admin2faSwitch" name="admin_2fa" value="1" <?php echo $admin_2fa==='1'?'checked':''; ?> />
                                        <label class="form-check-label" for="admin2faSwitch">Enable Two-Factor Authentication (2FA) for Admin Login</label>
                                        <script>
                                        // Prevent browser or JS from auto-changing 2FA switch
                                        document.addEventListener('DOMContentLoaded', function() {
                                            var twofa = document.getElementById('admin2faSwitch');
                                            if (twofa) {
                                                // Remove any event listeners that could auto-toggle
                                                twofa.addEventListener('change', function(e) {
                                                    // Only allow change if user clicks (not programmatic)
                                                    // No auto-toggle allowed
                                                });
                                            }
                                        });
                                        </script>
                                    </div>
                                    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2 me-1"></i> Save Security Settings</button>
                                </form>
                                <?php if (!empty($sec_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($sec_ok); ?></div><?php endif; ?>
                                <?php if (!empty($sec_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($sec_err); ?></div><?php endif; ?>
                                <form class="row g-3 border-bottom pb-3 mb-4" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="col-md-6">
                                        <label class="form-label">Current Password <i class="bi bi-key ms-1"></i></label>
                                        <input type="password" class="form-control form-control-sm" name="current_password" required>
                                    </div>
                                    <div class="col-md-6"></div>
                                    <div class="col-md-6">
                                        <label class="form-label">New Password</label>
                                        <input type="password" class="form-control form-control-sm" name="new_password" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control form-control-sm" name="confirm_password" required>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button class="btn btn-danger btn-sm px-4" type="submit">Change Password</button>
                                    </div>
                                </form>
                            </div>
                            <div class="mb-4 p-3 border rounded bg-light">
                                <h5 class="mb-3 text-primary"><i class="fas fa-sliders-h me-2"></i>User Preferences</h5>
                                <?php if (!empty($pref_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($pref_ok); ?></div><?php endif; ?>
                                <?php if (!empty($pref_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($pref_err); ?></div><?php endif; ?>
                                <form method="POST" class="mt-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="save_prefs" value="1">
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="darkModeSwitch" name="pref_dark" <?php echo ($pref_dark ?? '0')==='1'?'checked':''; ?> >
                                        <label class="form-check-label" for="darkModeSwitch">Enable Dark Mode</label>
                                    </div>
                                    <div class="form-check form-switch mt-2">
                                        <input class="form-check-input" type="checkbox" id="notifSwitch" name="pref_email_notif" <?php echo ($pref_email_notif ?? '1')==='1'?'checked':''; ?> >
                                        <label class="form-check-label" for="notifSwitch">Enable Email Notifications</label>
                                    </div>
                                    <div class="mt-3">
                                        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-check2 me-1"></i> Save Preferences</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="system" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="fas fa-toolbox me-2"></i>Administrator Tools</h5>
                            <div class="mb-4 p-3 border rounded">
                                <label class="form-label fw-bold">Data Backup</label>
                                <p class="text-muted small mb-2">Download a full backup of all system data.</p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-secondary btn-sm" href="backup_database.php">
                                        <i class="fas fa-download me-1"></i> Download Database Backup
                                    </a>
                                    <a class="btn btn-outline-primary btn-sm" href="backups_tool.php">
                                        <i class="bi bi-hdd-network me-1"></i> Open Backups Tool
                                    </a>
                                </div>
                            </div>
                            <div class="mb-4 p-3 border rounded">
                                <label class="form-label fw-bold">Security & Activity Logs</label>
                                <p class="text-muted small mb-2">Review administrator activity and security events.</p>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-outline-secondary btn-sm" href="audit_logs.php"><i class="bi bi-clipboard2-check me-1"></i> View Audit Logs</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="logs_viewer.php"><i class="bi bi-activity me-1"></i> Logs Viewer</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="roles_matrix.php"><i class="bi bi-diagram-3 me-1"></i> Roles Matrix</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="payslips.php"><i class="bi bi-file-earmark-text me-1"></i> Payslips</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="billing.php"><i class="bi bi-receipt me-1"></i> Billing</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="aging_report.php"><i class="bi bi-hourglass-split me-1"></i> Aging Report</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="bulk_import_export.php"><i class="bi bi-cloud-arrow-up-down me-1"></i> Bulk Import/Export</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="reminders.php"><i class="bi bi-bell me-1"></i> Reminders</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="send_reminders.php"><i class="bi bi-send me-1"></i> Send Reminders</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="system_health.php"><i class="bi bi-heart-pulse me-1"></i> System Health</a>
                                    <a class="btn btn-outline-secondary btn-sm" href="help_docs.php"><i class="bi bi-question-circle me-1"></i> Help & Docs</a>
                                </div>
                            </div>
                            <div class="mb-4 p-3 border rounded text-center">
                                <p class="mb-0">Emergency Lock management has moved to its own tab for easier access.</p>
                                <a class="btn btn-danger btn-sm mt-2" href="#emergency" onclick="document.querySelector('button#emergency-tab')?.click(); return false;">Open Emergency Lock</a>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="emergency" role="tabpanel">
                            <h5 class="mb-3 text-danger"><i class="fas fa-lock me-2"></i>Emergency Lock</h5>
                            <form method="POST" class="row g-3 align-items-center">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="toggle_emergency_lock" value="1">
                                <div class="col-12 col-md-8">
                                    <label class="form-label">Message shown to visitors</label>
                                    <textarea class="form-control" name="emergency_lock_msg" rows="2" placeholder="Message shown to visitors when locked"><?php echo htmlspecialchars(get_setting('emergency_lock_msg','')); ?></textarea>
                                </div>
                                <div class="col-12 col-md-2">
                                    <label class="form-label">Active</label>
                                    <div class="form-check form-switch mt-2">
                                        <?php $is_locked = get_setting('emergency_lock','0')==='1'; ?>
                                        <input class="form-check-input" type="checkbox" id="enable_lock_panel" name="enable_lock" value="1" <?php echo $is_locked ? 'checked' : ''; ?> >
                                        <label class="form-check-label" for="enable_lock_panel"><?php echo $is_locked? 'Locked':'Unlocked'; ?></label>
                                    </div>
                                </div>
                                <div class="col-12 col-md-2">
                                    <div class="d-flex flex-column gap-2 align-items-stretch">
                                        <button class="btn btn-danger mb-1" id="applyLockBtn">Apply</button>
                                        <button type="submit" name="release_lock_now" value="1" class="btn btn-outline-secondary" id="releaseLockBtn">Release Now</button>
                                    </div>
                                </div>
                            </form>
                            <div class="mt-3">
                                <label class="form-label fw-bold">Notify admin email on lock/unlock</label>
                                <form method="POST" class="row g-2 align-items-center">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                    <input type="hidden" name="save_notify_email" value="1">
                                    <div class="col-md-8">
                                        <input type="email" class="form-control form-control-sm" name="notify_email" value="<?php echo htmlspecialchars(get_setting('security_notify_email','')); ?>" placeholder="Admin notification email">
                                    </div>
                                    <div class="col-md-4">
                                        <button class="btn btn-primary btn-sm">Save Email</button>
                                    </div>
                                </form>
                                <hr>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Recent Security Alerts</h6>
                                    <form method="POST" style="margin:0;">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <button type="submit" name="export_alerts" value="1" class="btn btn-sm btn-outline-secondary">Export CSV</button>
                                    </form>
                                </div>
                                <div style="max-height:260px;overflow:auto;">
                                    <table class="table table-sm">
                                        <thead><tr><th>When</th><th>IP</th><th>Reason</th><th>URI</th><th></th></tr></thead>
                                        <tbody>
                                        <?php
                                        $alerts = [];
                                        try {
                                            if ($res = $conn->query("SELECT id, ip, uri, user_agent, reason, details, created_at FROM admin_security_alerts ORDER BY created_at DESC LIMIT 50")) {
                                                while ($r = $res->fetch_assoc()) $alerts[] = $r;
                                                $res->close();
                                            }
                                        } catch (Throwable $e) {}
                                        foreach ($alerts as $a): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($a['created_at']); ?></td>
                                                <td><?php echo htmlspecialchars($a['ip']); ?></td>
                                                <td><code><?php echo htmlspecialchars($a['reason']); ?></code></td>
                                                <td style="max-width:300px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;"><?php echo htmlspecialchars($a['uri']); ?></td>
                                                <td class="text-end">
                                                    <form method="POST" style="display:inline" onsubmit="return confirm('Block IP <?php echo htmlspecialchars($a['ip']); ?>?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                                        <input type="hidden" name="block_ip" value="<?php echo htmlspecialchars($a['ip']); ?>">
                                                        <button class="btn btn-sm btn-outline-danger">Block IP</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-2">
                                    <label class="form-label fw-bold">Blocked IPs</label>
                                    <?php $blocked = implode(', ', get_blocked_ips()); ?>
                                    <div class="small text-muted mb-2"><?php echo htmlspecialchars($blocked); ?></div>
                                    <form method="POST" class="row g-2" onsubmit="return confirm('Unblock IP?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="unblock_ip" value="1">
                                        <div class="col-md-6"><input type="text" class="form-control form-control-sm" name="unblock_ip_addr" placeholder="IP to unblock"></div>
                                        <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Unblock</button></div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="reminders" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-megaphone me-2"></i>Reminder Templates & Channels</h5>
                            <?php
                            $rem_brevo_api_file = __DIR__ . '/brevo_api_key.txt';
                            $rem_brevo_sender_file = __DIR__ . '/brevo_sender.txt';
                            $REM_BREVO_API_KEY = file_exists($rem_brevo_api_file) ? trim(@file_get_contents($rem_brevo_api_file)) : get_setting('rem_brevo_api_key', get_setting('rem_brevo_key',''));
                            $REM_BREVO_SENDER = get_setting('rem_brevo_sender', file_exists($rem_brevo_sender_file) ? trim(@file_get_contents($rem_brevo_sender_file)) : get_setting('brevo_sender',''));
                            $REM_WA_TOKEN = get_setting('rem_wa_token','');
                            $REM_WA_PHONE_ID = get_setting('rem_wa_phone_id', get_setting('rem_wa_phone',''));
                            $REM_EMAIL_SUBJECT = get_setting('rem_email_subject', get_setting('tpl_email_sub','Payment Reminder'));
                            $REM_EMAIL_BODY = get_setting('rem_email_body', get_setting('tpl_email_body','Dear {{name}}, your due amount is ₹{{amount}}.'));
                            $REM_SMS_TPL = get_setting('rem_sms_tpl', get_setting('tpl_wa','Hi {{name}}, ₹{{amount}} is due.'));
                            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_rem_brevo'])) {
                                $new_api = trim($_POST['rem_brevo_api_key'] ?? '');
                                $new_sender = trim($_POST['rem_brevo_sender'] ?? '');
                                if ($new_api === '' || $new_sender === '') {
                                    $rem_err = 'API key and sender email are required.';
                                } elseif (!filter_var($new_sender, FILTER_VALIDATE_EMAIL)) {
                                    $rem_err = 'Sender email is not valid.';
                                } else {
                                    if (@file_put_contents($rem_brevo_api_file, $new_api) !== false && @file_put_contents($rem_brevo_sender_file, $new_sender) !== false) {
                                        set_settings(['rem_brevo_api_key' => $new_api, 'rem_brevo_sender' => $new_sender]);
                                        $rem_ok = 'Brevo API key and sender updated.';
                                        $REM_BREVO_API_KEY = $new_api;
                                        $REM_BREVO_SENDER = $new_sender;
                                    } else {
                                        $rem_err = 'Failed to save settings.';
                                    }
                                }
                            }
                            ?>
                            <?php if (!empty($rem_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($rem_ok); ?></div><?php endif; ?>
                            <?php if (!empty($rem_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($rem_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_reminders" value="1" />
                                <div class="col-md-6">
                                    <label class="form-label">Brevo API Key (Email)</label>
                                    <input type="text" class="form-control form-control-sm" name="brevo_key" value="<?php echo htmlspecialchars($REM_BREVO_API_KEY); ?>" placeholder="xkeysib-..." readonly>
                                    <small class="text-muted">Email reminders use Brevo. Leave blank to disable.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Brevo Sender Email</label>
                                    <input type="email" class="form-control form-control-sm" name="brevo_sender" value="<?php echo htmlspecialchars($REM_BREVO_SENDER); ?>" placeholder="sender@email.com" readonly>
                                    <small class="text-muted">This email will be used as the sender for Brevo reminders.</small>
                                </div>
                                <div class="col-12 mt-2" id="remBrevoEditActions" style="display:none;">
                                    <form method="POST" class="row g-2">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                        <input type="hidden" name="save_rem_brevo" value="1" />
                                        <div class="col-md-6">
                                            <input type="text" class="form-control form-control-sm" name="rem_brevo_api_key" value="<?php echo htmlspecialchars($REM_BREVO_API_KEY); ?>" placeholder="xkeysib-..." required>
                                        </div>
                                        <div class="col-md-6">
                                            <input type="email" class="form-control form-control-sm" name="rem_brevo_sender" value="<?php echo htmlspecialchars($REM_BREVO_SENDER); ?>" placeholder="sender@email.com" required>
                                        </div>
                                        <div class="col-12 mt-2">
                                            <button class="btn btn-primary btn-sm me-2"><i class="bi bi-check2 me-1"></i> Save Brevo API Key</button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" id="remBrevoCancelBtn">Cancel</button>
                                        </div>
                                    </form>
                                </div>
                                <div class="mt-2" id="remBrevoUpdateBtnArea">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="remBrevoUpdateBtn"><i class="bi bi-pencil-square me-1"></i> Update Brevo API Key</button>
                                </div>
                                <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    var editMode = false;
                                    var updateBtn = document.getElementById('remBrevoUpdateBtn');
                                    var updateBtnArea = document.getElementById('remBrevoUpdateBtnArea');
                                    var editActions = document.getElementById('remBrevoEditActions');
                                    var cancelBtn = document.getElementById('remBrevoCancelBtn');
                                    function setEditMode(on) {
                                        editMode = on;
                                        editActions.style.display = on ? 'block' : 'none';
                                        updateBtnArea.style.display = on ? 'none' : 'block';
                                    }
                                    if (updateBtn) updateBtn.onclick = function() { setEditMode(true); };
                                    if (cancelBtn) cancelBtn.onclick = function() { setEditMode(false); };
                                    setEditMode(false);
                                });
                                </script>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp Cloud API Token</label>
                                    <input type="text" class="form-control form-control-sm" name="wa_token" value="<?php echo htmlspecialchars($REM_WA_TOKEN); ?>" placeholder="EAAG...">
                                    <small class="text-muted">Optional. Leave blank to skip WhatsApp.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">WhatsApp Business Phone ID</label>
                                    <input type="text" class="form-control form-control-sm" name="wa_phone" value="<?php echo htmlspecialchars($REM_WA_PHONE_ID); ?>" placeholder="1234567890">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Email Subject Template</label>
                                    <input type="text" class="form-control form-control-sm" name="tpl_email_sub" value="<?php echo htmlspecialchars($REM_EMAIL_SUBJECT); ?>" placeholder="Payment Reminder">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Email Body Template</label>
                                    <textarea class="form-control form-control-sm" name="tpl_email_body" rows="4" placeholder="Dear {{name}}, your due amount is ₹{{amount}}."><?php echo htmlspecialchars($REM_EMAIL_BODY); ?></textarea>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">WhatsApp/SMS Template</label>
                                    <textarea class="form-control form-control-sm" name="tpl_wa" rows="3" placeholder="Hi {{name}}, ₹{{amount}} is due."><?php echo htmlspecialchars($REM_SMS_TPL); ?></textarea>
                                    <small class="text-muted">Placeholders: {{name}}, {{amount}}, {{days}}, {{mobile}}, {{email}}</small>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Reminder Settings</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="localization" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-translate me-2"></i>Localization</h5>
                            <?php if (!empty($loc_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($loc_ok); ?></div><?php endif; ?>
                            <?php if (!empty($loc_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($loc_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_localization" value="1">
                                <div class="col-md-4">
                                    <label class="form-label">Currency Symbol</label>
                                    <input class="form-control form-control-sm" name="currency" value="<?php echo htmlspecialchars(get_setting('loc_currency','₹')); ?>" placeholder="₹">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Date Format</label>
                                    <select class="form-select form-select-sm" name="date_format">
                                        <?php $opts=['DD-MM-YYYY','YYYY-MM-DD','MM/DD/YYYY']; $LOC_DATEFMT=get_setting('loc_date_format','DD-MM-YYYY'); foreach($opts as $op): ?>
                                          <option value="<?php echo $op; ?>" <?php echo $LOC_DATEFMT===$op?'selected':''; ?>><?php echo $op; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Number Format</label>
                                    <select class="form-select form-select-sm" name="number_format">
                                      <option value="IN" <?php echo get_setting('loc_number_format','IN')==='IN'?'selected':''; ?>>Indian (1,23,456.78)</option>
                                      <option value="US" <?php echo get_setting('loc_number_format','IN')==='US'?'selected':''; ?>>US (123,456.78)</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary btn-sm"><i class="bi bi-check2 me-1"></i> Save Localization</button>
                                </div>
                            </form>
                        </div>
                        <div class="tab-pane fade" id="brevo" role="tabpanel">
                            <h5 class="mb-3 text-primary"><i class="bi bi-envelope-at me-2"></i>Brevo Email Settings</h5>
                            <?php
                            $brevo_api_file = __DIR__ . '/brevo_api_key.txt';
                            $brevo_sender_file = __DIR__ . '/brevo_sender.txt';
                            $BREVO_API_KEY = file_exists($brevo_api_file) ? trim(@file_get_contents($brevo_api_file)) : get_setting('brevo_api_key','');
                            $BREVO_SENDER = get_setting('brevo_sender', file_exists($brevo_sender_file) ? trim(@file_get_contents($brevo_sender_file)) : '');
                            ?>
                            <?php if (!empty($brevo_ok)): ?><div class="alert alert-success py-2"><?php echo htmlspecialchars($brevo_ok); ?></div><?php endif; ?>
                            <?php if (!empty($brevo_err)): ?><div class="alert alert-danger py-2"><?php echo htmlspecialchars($brevo_err); ?></div><?php endif; ?>
                            <form method="POST" class="row g-3" id="brevoForm">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(csrf_token()); ?>">
                                <input type="hidden" name="save_brevo" value="1" />
                                <div class="col-md-6">
                                    <label class="form-label">Brevo API Key</label>
                                    <input type="text" class="form-control form-control-sm" name="brevo_api_key" id="brevoApiKey" value="<?php echo htmlspecialchars($BREVO_API_KEY); ?>" placeholder="xkeysib-..." required readonly>
                                    <small class="text-muted">Paste your Brevo (Sendinblue) API key here.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Sender Email</label>
                                    <input type="email" class="form-control form-control-sm" name="brevo_sender" id="brevoSender" value="<?php echo htmlspecialchars($BREVO_SENDER); ?>" placeholder="sender@email.com" required readonly>
                                    <small class="text-muted">This email will be used as the sender for Brevo emails.</small>
                                </div>
                                <div class="col-12 mt-2" id="brevoEditActions" style="display:none;">
                                    <button class="btn btn-primary btn-sm me-2"><i class="bi bi-check2 me-1"></i> Save Brevo Settings</button>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="brevoCancelBtn">Cancel</button>
                                </div>
                            </form>
                            <div class="mt-2" id="brevoUpdateBtnArea">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="brevoUpdateBtn"><i class="bi bi-pencil-square me-1"></i> Update</button>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                var editMode = false;
                                var apiInput = document.getElementById('brevoApiKey');
                                var senderInput = document.getElementById('brevoSender');
                                var updateBtn = document.getElementById('brevoUpdateBtn');
                                var updateBtnArea = document.getElementById('brevoUpdateBtnArea');
                                var editActions = document.getElementById('brevoEditActions');
                                var cancelBtn = document.getElementById('brevoCancelBtn');
                                function setEditMode(on) {
                                    editMode = on;
                                    apiInput.readOnly = !on;
                                    senderInput.readOnly = !on;
                                    editActions.style.display = on ? 'block' : 'none';
                                    updateBtnArea.style.display = on ? 'none' : 'block';
                                }
                                if (updateBtn) updateBtn.onclick = function() { setEditMode(true); };
                                if (cancelBtn) cancelBtn.onclick = function() { setEditMode(false); apiInput.value = "<?php echo htmlspecialchars($BREVO_API_KEY); ?>"; senderInput.value = "<?php echo htmlspecialchars($BREVO_SENDER); ?>"; };
                                setEditMode(false);
                            });
                            </script>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'downfooter.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var tabEl = document.querySelectorAll('button[data-bs-toggle="pill"]');
    tabEl.forEach(function(tab) {
        tab.addEventListener('shown.bs.tab', function (event) {
            document.querySelector('.col-md-9').scrollTo(0, 0);
            var target = event.target?.getAttribute('data-bs-target');
            if (target && target.startsWith('#')) { history.replaceState(null, '', target); }
        });
    });
    document.addEventListener('DOMContentLoaded', function(){
        if (location.hash) {
            var btn = document.querySelector('button[data-bs-target="' + location.hash + '"]');
            if (btn) { btn.click(); }
        }
    });
</script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    try {
        var token = <?= json_encode(htmlspecialchars(csrf_token())); ?>;
        document.querySelectorAll('form').forEach(function(f){
            if (!f.querySelector('input[name="csrf_token"]')) {
                var inp = document.createElement('input'); inp.type='hidden'; inp.name='csrf_token'; inp.value = token;
                f.prepend(inp);
            }
        });
    } catch (e) { }
});
</script>
</body>
</html>