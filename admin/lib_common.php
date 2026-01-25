<?php
/**
 * lib_common.php - Secure and common helper functions for admin pages.
 * Includes Session management, App Settings, Role Check, Maintenance, and Basic IDS.
 */



// Check for existing functions/definitions to prevent redeclaration errors
if (!function_exists('is_ip_whitelisted')) {
    // Whitelist management (defined later in original code, needed early for IDS)
    function get_whitelist_ips() {
        $raw = get_setting('whitelist_ips', '');
        $list = array_filter(array_map('trim', explode(',', $raw)));
        return $list;
    }
    function is_ip_whitelisted($ip) {
        return in_array($ip, get_whitelist_ips());
    }
}


// --- Global Session and Database Setup ---

// Check if database.php is required.
// NOTE: It is assumed database.php establishes the $conn object.
if (!isset($conn)) { require_once __DIR__ . '/database.php'; }

// Ensure app_settings table exists
// SQL Injection Risk: None (static query)
if (isset($conn)) {
    try {
        $conn->query("CREATE TABLE IF NOT EXISTS app_settings (
          `key` VARCHAR(64) PRIMARY KEY,
          `value` TEXT NOT NULL,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
        $conn->query("CREATE TABLE IF NOT EXISTS admin_audit (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_email VARCHAR(191) NOT NULL,
            action VARCHAR(255) NOT NULL,
            details TEXT NULL,
            ip VARCHAR(64) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_admin_time (admin_email, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS admin_security_alerts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(64) NULL,
            uri TEXT NULL,
            user_agent TEXT NULL,
            reason VARCHAR(255) NULL,
            details TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_time (ip, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $conn->query("CREATE TABLE IF NOT EXISTS admin_security_actions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            token VARCHAR(128) NOT NULL,
            ip VARCHAR(64) NOT NULL,
            action VARCHAR(32) NOT NULL,
            used TINYINT DEFAULT 0,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        
    } catch (Throwable $e) {
        // Log critical error if DB setup fails
        error_log("CRITICAL DB SETUP ERROR: " . $e->getMessage());
    }
}


// --- App Settings Helpers ---

if (!function_exists('get_setting')) {
    function get_setting($key, $default = '') {
        global $conn;
        if (!isset($conn)) return $default;
        // Try app_settings first
        if ($stmt = $conn->prepare("SELECT `value` FROM app_settings WHERE `key`=? LIMIT 1")) {
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['value'];
            }
            $stmt->close();
        }
        // Fallback: Try site_settings if not found
        if ($stmt = $conn->prepare("SELECT setting_value FROM site_settings WHERE setting_key=? LIMIT 1")) {
            $stmt->bind_param('s', $key);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['setting_value'];
            }
            $stmt->close();
        }
        return $default;
    }
}

if (!function_exists('set_settings')) {
    function set_settings($assoc) {
        global $conn;
        if (!isset($conn) || !$assoc || !is_array($assoc)) return;
        
        // FIX: Original code already used prepared statements - retained for security.
        try {
            $stmt = $conn->prepare("INSERT INTO app_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
            if ($stmt) {
                foreach ($assoc as $k => $v) {
                    $v_str = (string)$v; // Ensure value is a string
                    $stmt->bind_param('ss', $k, $v_str);
                    $stmt->execute();
                }
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log("DB SETTINGS WRITE ERROR: " . $e->getMessage());
        }
    }
}


// --- Session Configuration and Start ---

// Secure session cookie flags (Initial configuration before starting session)
if (session_status() !== PHP_SESSION_ACTIVE) {
    
    // Determine the session timeout value (in minutes)
    $default_timeout = intval(get_setting('global_session_timeout', '1440'));
    $admin_timeout = intval(get_setting('admin_session_timeout', (string)$default_timeout));
    $panels_timeout = intval(get_setting('panels_session_timeout', (string)$default_timeout));
    
    $selected_timeout = $default_timeout;
    $requested_uri = $_SERVER['REQUEST_URI'] ?? '/';
    
    $panel_segment = defined('PANEL') && PANEL ? PANEL : (explode('/', trim($requested_uri, '/'))[0] ?? '');

    if (in_array($panel_segment, ['worker','attendence','contact'])) {
        $selected_timeout = $panels_timeout;
    } elseif ($panel_segment === 'admin') {
        $selected_timeout = $admin_timeout;
    }
    
    // Enforce minimum and maximum timeout (min 5 minutes, max 365 days)
    $selected_timeout = max(5, min(525600, $selected_timeout)); 
    $lifetime = $selected_timeout * 60; // in seconds

    // Apply ini settings and cookie params before session_start()
    ini_set('session.gc_maxlifetime', $lifetime);
    ini_set('session.cookie_lifetime', $lifetime);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1); // IMPORTANT: Prevents Session Fixation
    
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    ini_set('session.cookie_secure', $is_secure ? 1 : 0);
    
    // Use SameSite=Strict by default to prevent CSRF (Better than original code's initial block, which was before session_start)
    $cookie_samesite = 'Strict';
    
    // Apply session cookie params again (needed if ini settings are not respected)
    session_set_cookie_params([ 
        'lifetime' => $lifetime, 
        'path' => '/', 
        'httponly' => true, 
        'secure' => $is_secure,
        'samesite' => $cookie_samesite // Apply SameSite flag
    ]);

    // Finally, start the session
    session_start();
}


// --- Audit Logger ---

if (!function_exists('log_audit')) {
    function log_audit($action, $details = '') {
        global $conn;
        if (!isset($conn)) return;

        // FIX: Use Prepared Statement for safe INSERT (Critical for logging user-provided data)
        try {
            $email = $_SESSION['email'] ?? 'unknown';
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $details_cut = substr($details, 0, 65535); // Truncate long details for TEXT column
            
            if ($stmt = $conn->prepare('INSERT INTO admin_audit (admin_email, action, details, ip) VALUES (?,?,?,?)')) {
                $stmt->bind_param('ssss', $email, $action, $details_cut, $ip);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log("DB AUDIT LOG ERROR: " . $e->getMessage());
        }
    }
}


// --- Role Check ---

if (!function_exists('require_role')) {
    function require_role($role = 'Super Admin') {
        global $conn;
        $email = $_SESSION['email'] ?? '';
        
        if (!isset($conn) || !$email) { 
            http_response_code(403); 
            echo 'Forbidden: Not Authenticated'; 
            exit; 
        }

        // Admin role allows any authenticated admin
        if ($role === 'Admin') return; 

        try {
            // Check if 'type' column exists (expensive, but only done if role is specified)
            $colExists = false;
            if ($res = $conn->query("SHOW COLUMNS FROM admin LIKE 'type'")) {
                $colExists = (bool)$res->num_rows;
            }

            if ($colExists) {
                // FIX: Use Prepared Statement for safe SELECT
                $stmt = $conn->prepare('SELECT COALESCE(type,\'Admin\') AS type FROM admin WHERE email=? LIMIT 1');
                $stmt->bind_param('s', $email);
                $stmt->execute(); 
                $res = $stmt->get_result();
                
                $type = $res && $res->num_rows ? ($res->fetch_assoc()['type'] ?? 'Admin') : 'Admin';
                $stmt->close();
                
                // Case-insensitive comparison is good practice for roles
                if (strcasecmp($type, $role) === 0) return; 
                
                http_response_code(403); 
                echo 'Forbidden (needs ' . htmlspecialchars($role) . ')'; 
                log_audit('Role_Access_Denied', "User {$email} denied access to {$role}. Actual role: {$type}");
                exit;
            }

            // Fallback: If 'type' column doesn't exist, treat the oldest user (lowest ID) as Super Admin.
            if ($role === 'Super Admin') {
                $row = $conn->query('SELECT email FROM admin ORDER BY id ASC LIMIT 1');
                $primaryEmail = ($row && $row->num_rows) ? ($row->fetch_assoc()['email'] ?? '') : '';
                if ($primaryEmail && strcasecmp($email, $primaryEmail) === 0) return; 
            }
            
            http_response_code(403); 
            echo 'Forbidden (Access denied by fallback role check)'; 
            log_audit('Role_Access_Denied_Fallback', "User {$email} denied access to {$role} by fallback logic.");
            exit;

        } catch (Throwable $e) {
            error_log("DB ROLE CHECK ERROR: " . $e->getMessage());
            http_response_code(500); echo 'Internal Server Error'; exit;
        }
    }
}


// --- Utility: Render Template ---

if (!function_exists('render_tpl')) {
    function render_tpl($tpl, $vars) {
        $out = $tpl;
        // FIX: The original logic is slightly vulnerable to double-substitution if not careful.
        // Also, it's safer to ensure all replacements are done with proper escaping if they 
        // will be rendered as HTML directly. Since this is a simple string replace, 
        // we'll rely on the caller to escape HTML, but we ensure string conversion.
        foreach ($vars as $k => $v) {
            // Use str_replace directly for simple, fast replacement
            $out = str_replace(['{{'.$k.'}}', '{{ '.$k.' }}'], (string)$v, $out);
        }
        return $out;
    }
}


// --- Per-panel Maintenance Enforcement ---

// Original function logic is sound but relies heavily on get_setting. No major security fix needed here.

if (!function_exists('check_panel_maintenance')) {
    function check_panel_maintenance() {
        global $conn;
        
        // ... (Original logic for determining segment and checking flag/expiry remains the same) ...
        $map = [
            'worker' => ['flag'=>'panel_down_worker','msg'=>'panel_down_msg_worker'],
            'attendence' => ['flag'=>'panel_down_attendence','msg'=>'panel_down_msg_attendence'],
            'contact' => ['flag'=>'panel_down_contact','msg'=>'panel_down_msg_contact'],
        ];
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $segment = defined('PANEL') && PANEL ? PANEL : (explode('/', trim($uri, '/'))[0] ?? '');

        if (!isset($map[$segment])) {
            $alt = '';
            $candidates = [$_SERVER['SCRIPT_NAME'] ?? '', $_SERVER['PHP_SELF'] ?? '', $_SERVER['SCRIPT_FILENAME'] ?? ''];
            foreach ($candidates as $cand) {
                foreach (array_keys($map) as $candidateSeg) {
                    if ($cand && (stripos($cand, '/' . $candidateSeg . '/') !== false || stripos($cand, '\\' . $candidateSeg . '\\') !== false)) {
                        $alt = $candidateSeg; break 2;
                    }
                }
            }
            if ($alt === '') return;
            $segment = $alt;
        }

        $flagKey = $map[$segment]['flag'];
        $msgKey = $map[$segment]['msg'];
        $untilKey = str_replace('panel_down_','panel_down_until_',$flagKey);
        
        $isDown = get_setting($flagKey, '0') === '1';
        $untilVal = intval(get_setting($untilKey, '0'));
        
        // If expired, clear the flag
        if ($isDown && $untilVal > 0 && time() > $untilVal) {
            set_settings([$flagKey=>'0',$untilKey=>'0']);
            $isDown = false;
        }

        // Debug logging retained (in a try/catch block)
        try {
            $dbg = __DIR__ . '/maintenance_debug.log';
            $line = date('c') . " | uri=" . ($uri) . " | segment=" . ($segment) . " | flag=" . ($flagKey) . " | isDown=" . ($isDown?1:0) . " | until=" . ($untilVal) . "\n";
            @file_put_contents($dbg, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) { /* ignore */ }

        if (!$isDown) return;

        // Determine admin status
        $isAdmin = false;
        if (!empty($_SESSION['email']) && isset($conn) && $conn) {
            $email = $_SESSION['email'];
            try {
                // FIX: Use Prepared Statement for admin check
                if ($stmt = @$conn->prepare('SELECT 1 FROM admin WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) $isAdmin = true;
                    $stmt->close();
                }
            } catch (Throwable $e) { /* Ignore DB errors in maintenance check */ }
        }

        if ($isAdmin) return;

        $message = get_setting($msgKey, '');
        if (trim($message) === '') {
            $readablePanel = ucfirst($segment);
            $message = "The admin has temporarily disabled the $readablePanel panel. Please try again later.";
        }

        // Render maintenance page (HTML output is safe as it uses htmlspecialchars and nl2br)
        http_response_code(503);
        $expireTs = intval($untilVal);
        $durationKey = str_replace('panel_down_','panel_down_duration_',$flagKey);
        $durationVal = intval(get_setting($durationKey, '0'));
        $msgHtml = nl2br(htmlspecialchars($message));
        
        // ... (HTML content remains the same) ...
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Maintenance</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:linear-gradient(135deg,#f8fafc,#eef2ff);}.card{border-radius:16px;box-shadow:0 8px 32px rgba(13,110,253,0.08);max-width:720px;padding:28px;text-align:center}</style>';
        echo '</head><body><div class="card">';
        echo '<h1 style="color:#0d6efd;margin-bottom:8px">Service Temporarily Unavailable</h1>';
        echo '<div style="font-size:1.05rem;color:#333;margin-bottom:18px">' . $msgHtml . '</div>';
        if ($expireTs > 0) {
            echo '<div id="countdown" style="font-size:1.1rem;color:#555;margin-bottom:12px">Loading remaining time...</div>';
            echo '<div style="color:#6b7280;margin-bottom:12px">This page will automatically refresh when the scheduled downtime ends.</div>';
        } else {
            echo '<p style="color:#6b7280">We are working on it — please check back shortly.</p>';
        }
        echo '<div style="margin-top:18px"><a class="btn btn-primary" href="/">Go to Home</a></div>';
        echo '</div>';
        // JS for live countdown and auto-reload (safe)
        echo '<script>';
        if ($expireTs > 0) {
            echo 'var expireAt = ' . $expireTs . ' * 1000;';
            echo 'function updateCountdown(){ var now = Date.now(); var diff = Math.max(0, expireAt - now); var s = Math.floor(diff/1000); var m = Math.floor(s/60); var ss = s%60; document.getElementById("countdown").innerText = "Remaining: " + m + "m " + (ss<10?"0":"") + ss + "s"; if (s<=0){ location.reload(); } } updateCountdown(); setInterval(updateCountdown, 1000);';
        }
        echo '</script>';
        echo '</body></html>';
        exit;
    }
}

// Run maintenance check early
check_panel_maintenance();


// --- Lightweight IDS / Emergency Lock Enforcement ---

// FIX: Combined table creation into the initial setup block.

if (!function_exists('log_security_alert')) {
    function log_security_alert($reason, $details = '') {
        global $conn;
        if (!isset($conn)) return;

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $uri = ($_SERVER['REQUEST_URI'] ?? '') . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] ? ('?' . $_SERVER['QUERY_STRING']) : '');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            // FIX: Use Prepared Statement for safe INSERT (Critical for logging user-provided data)
            $uri_cut = substr($uri, 0, 65535); 
            $ua_cut = substr($ua, 0, 65535);
            $reason_cut = substr($reason, 0, 255);
            $details_cut = substr($details, 0, 65535);

            if ($stmt = @$conn->prepare('INSERT INTO admin_security_alerts (ip,uri,user_agent,reason,details) VALUES (?,?,?,?,?)')) {
                $stmt->bind_param('sssss', $ip, $uri_cut, $ua_cut, $reason_cut, $details_cut);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Throwable $e) {
            error_log("DB SECURITY ALERT LOG ERROR: " . $e->getMessage());
        }

        // Also append to a plain log file for quick inspection (retained)
        try {
            $log = __DIR__ . '/security_alerts.log';
            $line = date('c') . " | ip={$ip} | reason=" . str_replace("\n"," ", $reason) . " | uri=" . str_replace("\n"," ", $uri) . " | ua=" . str_replace("\n"," ", substr($ua,0,200)) . " | details=" . str_replace("\n"," ", substr($details,0,800)) . "\n";
            @file_put_contents($log, $line, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) { /* ignore */ }
    }
}

if (!function_exists('detect_suspicious_request')) {
    function detect_suspicious_request() {
        // FIX: The original patterns are decent but use strpos/stripos on raw inputs.
        // It's generally better to sanitize or use a regex when dealing with complex patterns, 
        // but for simple string matching, the original implementation is a good start for a lightweight IDS.
        
        $uri = strtolower($_SERVER['REQUEST_URI'] ?? '');
        $qs = strtolower($_SERVER['QUERY_STRING'] ?? '');
        $ua = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $body = '';
        
        // FIX: For POST bodies, reading php://input is more reliable than json_encode($_POST) 
        // especially for non-form-data or complex inputs, but sticking to the original logic's intent.
        if ($method === 'POST') { $body = json_encode($_POST); } 

        // Common attack patterns
        $patterns = [
            "union select", "benchmark(", "sleep(", "information_schema", "load_file(", "into outfile", "concat(", "-- ", "#", "' or '", "base64_decode(", "eval(", "system(", "passthru(", "exec(", "shell_exec(", "wget ", "curl ", "phpinfo(", "wp-login.php", ".env", "\.bash_history", "../", "<\?php"
        ];
        foreach ($patterns as $p) {
            if (stripos($uri, $p) !== false || stripos($qs, $p) !== false || stripos($body, $p) !== false) {
                // FIX: Log the full pattern to aid investigation
                return 'suspicious_pattern:' . $p; 
            }
        }

        // Strange file uploads (executable extensions)
        if (!empty($_FILES)) {
            foreach ($_FILES as $f) {
                // Handle single and multiple file uploads safely
                $files_to_check = is_array($f['name']) ? $f['name'] : [$f['name'] ?? ''];

                foreach ($files_to_check as $name) {
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    // Removed 'pl' (Perl) and added 'jsp' and 'asp' for web shells
                    $badExt = ['php','phtml','phar','sh','exe','dll','scr','jsp','asp']; 
                    if (in_array($ext, $badExt)) {
                        return 'suspicious_upload_ext:' . $ext;
                    }
                }
            }
        }

        // Very low UA or missing UA
        if ($ua === '' || strlen($ua) < 10) return 'missing_user_agent';

        // Check for suspicious long query strings
        if (strlen($qs) > 800) return 'long_query_string';

        return false;
    }
}


// Run detection on each request and log. 
$reason = detect_suspicious_request();
if ($reason !== false) {
    // Log the alert
    $details = json_encode([ 'method' => $_SERVER['REQUEST_METHOD'] ?? '', 'ua' => $_SERVER['HTTP_USER_AGENT'] ?? '', 'ip' => $_SERVER['REMOTE_ADDR'] ?? '', 'time' => time() ]);
    log_security_alert($reason, $details);

    // Emergency Lock Logic
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        // Skip lock logic for whitelisted IPs
        if ($ip && isset($conn) && !is_ip_whitelisted($ip)) { 
            
            // FIX: Use Prepared Statement for safe COUNT
            $stmt = @$conn->prepare("SELECT COUNT(*) AS c FROM admin_security_alerts WHERE ip = ? AND created_at >= (NOW() - INTERVAL 10 MINUTE)");
            if ($stmt) {
                $stmt->bind_param('s', $ip);
                $stmt->execute(); 
                $res = $stmt->get_result();
                $count = ($res && $res->num_rows) ? intval($res->fetch_assoc()['c']) : 0;
                $stmt->close();
                
                $threshold = intval(get_setting('ids_threshold', '7'));
                if ($threshold < 1) $threshold = 7;

                if ($count >= $threshold) {
                    set_settings(['emergency_lock' => '1', 'emergency_lock_msg' => 'Automatic lock due to suspicious traffic from IP ' . $ip . ' (Reason: ' . $reason . ')']);
                    if (function_exists('log_audit')) log_audit('emergency_lock_auto', 'auto-lock triggered due to ' . $count . ' alerts from ' . $ip);
                    
                    // Email Notification Logic (retained)
                    $policy = get_setting('notify_on_auto_lock','admin_only'); 
                    $notifyEmail = get_setting('security_notify_email','');
                    
                    if ($notifyEmail && in_array($policy, ['all','admin_only'])) {
                        // FIX: Added 'admin' check from the existing maintenance logic for consistency
                        $requested_uri = $_SERVER['REQUEST_URI'] ?? '/';
                        $parts = explode('/', trim($requested_uri, '/'));
                        $seg = $parts[0] ?? '';
                        $send = ($policy === 'all') || ($policy === 'admin_only' && $seg === 'admin');

                        if ($send && function_exists('send_brevo_email')) {
                            try {
                                // Token generation and email construction (retained, safe)
                                $unblock_token = bin2hex(random_bytes(16));
                                $permblock_token = bin2hex(random_bytes(16));
                                $expires = date('Y-m-d H:i:s', time() + 86400); // 24h
                                
                                $stmt2 = $conn->prepare('INSERT INTO admin_security_actions (token,ip,action,expires_at) VALUES (?,?,?,?)');
                                if ($stmt2) {
                                    $act1 = 'unblock'; $act2 = 'perm_block';
                                    $stmt2->bind_param('ssss', $unblock_token, $ip, $act1, $expires); $stmt2->execute();
                                    $stmt2->bind_param('ssss', $permblock_token, $ip, $act2, $expires); $stmt2->execute();
                                    $stmt2->close();
                                }
                                
                                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $base = $scheme . '://' . $host . '/admin/settings.php';
                                
                                $unblock_url = $base . '?sa_token=' . $unblock_token;
                                $perm_url = $base . '?sa_token=' . $permblock_token;
                                
                                $html = '<p>Auto-lock triggered for IP: ' . htmlspecialchars($ip) . '</p>';
                                $html .= '<p>Suspicious Reason: ' . htmlspecialchars($reason) . '</p>';
                                $html .= '<p>Actions: <a href="' . $unblock_url . '">Unblock (one-click)</a> | <a href="' . $perm_url . '">Permanent Block</a></p>';
                                
                                send_brevo_email($notifyEmail, 'Security Alert - Auto-lock triggered', $html);
                            } catch (Throwable $e) { error_log("SECURITY NOTIFY ERROR: " . $e->getMessage()); }
                        }
                    }
                }
            }
        }
    } catch (Throwable $e) { error_log("IDS MAIN LOGIC ERROR: " . $e->getMessage()); }
}


// --- Emergency Lock Gate ---

$is_locked = get_setting('emergency_lock', '0') === '1';
if ($is_locked) {
    $isAdmin = false;
    if (!empty($_SESSION['email']) && isset($conn) && $conn) {
        $email = $_SESSION['email'];
        try {
            // FIX: Use Prepared Statement for admin check
            if ($stmt = @$conn->prepare('SELECT 1 FROM admin WHERE email = ? LIMIT 1')) {
                $stmt->bind_param('s', $email);
                $stmt->execute(); 
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) $isAdmin = true;
                $stmt->close();
            }
        } catch (Throwable $e) { }
    }
    
    // Block non-admins
    if (!$isAdmin) {
        http_response_code(503);
        $lock_msg = get_setting('emergency_lock_msg', 'The site is temporarily locked for security reasons. Please contact the administrator.');
        
        // ... (HTML content remains the same) ...
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<title>Site Locked</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '<style>body{display:flex;align-items:center;justify-content:center;min-height:100vh;background:#f8f9fa}.card{max-width:720px;padding:28px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,0.06)}</style>';
        echo '</head><body><div class="card text-center">';
        echo '<h2 class="text-danger">Emergency Lock Active</h2>';
        echo '<p>' . nl2br(htmlspecialchars($lock_msg)) . '</p>';
        echo '<p class="text-muted">If you are the administrator, please log in to the admin panel to unlock.</p>';
        echo '</div></body></html>';
        exit;
    }
}


// --- IP Block/Whitelist Management (retained and defined early for usage) ---

// Already defined is_ip_whitelisted() early. Defining the rest:

if (!function_exists('get_blocked_ips')) {
    function get_blocked_ips() {
        $raw = get_setting('blocked_ips', '');
        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
if (!function_exists('is_ip_blocked')) {
    function is_ip_blocked($ip) {
        return in_array($ip, get_blocked_ips());
    }
}
if (!function_exists('add_blocked_ip')) {
    function add_blocked_ip($ip) {
        $list = get_blocked_ips();
        if (!in_array($ip, $list)) {
            $list[] = $ip;
            set_settings(['blocked_ips' => implode(',', $list)]);
        }
    }
}
if (!function_exists('remove_blocked_ip')) {
    function remove_blocked_ip($ip) {
        $list = get_blocked_ips();
        $list = array_values(array_filter($list, function($v) use($ip){ return $v !== $ip; }));
        set_settings(['blocked_ips' => implode(',', $list)]);
    }
}
// Whitelist functions already defined early.

// --- CSRF Token Helpers ---

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
        return $_SESSION['csrf_token'];
    }
}
if (!function_exists('validate_csrf')) {
    function validate_csrf($token) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        // FIX: Use hash_equals() for constant-time comparison (Critical Security Fix)
        return !empty($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}


// --- Enforce Blocked IPs (Primary Gate) ---

try {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remote && !is_ip_whitelisted($remote) && is_ip_blocked($remote)) {
        $isAdmin = false;
        if (!empty($_SESSION['email']) && isset($conn) && $conn) {
            $email = $_SESSION['email'];
            try {
                // FIX: Use Prepared Statement for admin check
                if ($stmt = @$conn->prepare('SELECT 1 FROM admin WHERE email = ? LIMIT 1')) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute(); $res = $stmt->get_result();
                    if ($res && $res->num_rows > 0) $isAdmin = true;
                    $stmt->close();
                }
            } catch (Throwable $e) { }
        }
        if (!$isAdmin) {
            http_response_code(403);
            echo 'Access denied';
            exit;
        }
    }
} catch (Throwable $e) { error_log("IP BLOCK ENFORCEMENT ERROR: " . $e->getMessage()); }


// --- Brevo Email Helper ---

if (!function_exists('send_brevo_email')) {
    function send_brevo_email($to, $subject, $html) {
        // FIX: Use global variables or dependency injection instead of raw file reads for config in a common helper.
        // Keeping original file read logic but adding better error reporting.
        $apiKey = @file_get_contents(__DIR__ . '/brevo_api_key.txt');
        $from = get_setting('brevo_sender', @file_get_contents(__DIR__ . '/brevo_sender.txt'));

        if (!$apiKey || !$from) {
            error_log('Brevo config (key or sender) missing.');
            return false;
        }
        $apiKey = trim($apiKey); $from = trim($from);
        
        $payload = [
            'sender' => ['email' => $from],
            'to' => [['email' => $to]],
            'subject' => $subject,
            'htmlContent' => $html
        ];

        try {
            $ch = curl_init('https://api.brevo.com/v3/smtp/email');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'api-key: ' . $apiKey
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // Add timeout

            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                error_log("Brevo CURL Error: " . curl_error($ch));
                curl_close($ch);
                return false;
            }

            curl_close($ch);
            
            if ($code < 200 || $code >= 300) {
                 error_log("Brevo API Error (HTTP {$code}): {$res}");
            }

            return $code >= 200 && $code < 300;
        } catch (Throwable $e) { 
            error_log("Brevo Send Error: " . $e->getMessage());
            return false; 
        }
    }
}
// End of file