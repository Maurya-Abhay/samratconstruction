<?php

// --- Use existing header (do not modify) ---
$page_title = "Worker Dashboard";
include 'header.php';


// --- Database includes / functions ---
if (file_exists(__DIR__ . '/../admin/lib_common.php')) {
    require_once __DIR__ . '/../admin/lib_common.php';
}
if (file_exists(__DIR__ . '/../admin/database.php')) {
    require_once __DIR__ . '/../admin/database.php'; // expects $conn
}

// Fallback DB connection
$auto_db = isset($conn) && ($conn instanceof mysqli);
if (!$auto_db) {
    $db_host = 'localhost';
    $db_user = 'root';
    $db_pass = '';
    $db_name = 'your_db';
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        $db_error = $conn->connect_error;
        $auto_db = false;
    } else {
        $auto_db = true;
    }
}

// Safe helper
function s($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Worker id from session
$worker_id = $_SESSION['worker_id'] ?? null;

// Create holidays table if DB available (Logic kept same)
if ($auto_db) {
    $conn->query("CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_name VARCHAR(255) NOT NULL,
        holiday_date DATE NOT NULL,
        description TEXT,
        is_active ENUM('Yes','No') DEFAULT 'Yes',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
}

// Next upcoming holiday
$today = date('Y-m-d');
$next_holiday = null;
if ($auto_db) {
    $stmt = $conn->prepare("SELECT * FROM holidays WHERE holiday_date >= ? AND is_active='Yes' ORDER BY holiday_date ASC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $res = $stmt->get_result();
        $next_holiday = $res->fetch_assoc();
        $stmt->close();
    }
}

// Notices counts
$total_notices = 0;
$urgent_notices = 0;
if ($auto_db) {
    $r = $conn->query("SELECT COUNT(*) as cnt FROM notices WHERE is_active='Yes'");
    if ($r) $total_notices = (int)$r->fetch_assoc()['cnt'];
    $r2 = $conn->query("SELECT COUNT(*) as cnt FROM notices WHERE priority='Urgent' AND is_active='Yes'");
    if ($r2) $urgent_notices = (int)$r2->fetch_assoc()['cnt'];
}

// Attendance mode
$attendance_mode = function_exists('get_setting') ? get_setting('attendance_mode', 'simple') : 'simple';
function attendance_text($m){ return $m === 'twotime' ? 'Check-In / Check-Out' : 'Daily Marking'; }

// Holiday text calculation
$holiday_title = "No Upcoming Holidays";
$holiday_date_text = "Work hard, play hard!";
$holiday_badge_color = "secondary";
if ($next_holiday) {
    $next_date = new DateTime($next_holiday['holiday_date']);
    $today_date = new DateTime($today);
    $diff = $today_date->diff($next_date)->days;
    $holiday_title = $next_holiday['holiday_name'];
    
    if ($next_holiday['holiday_date'] == $today) {
        $holiday_date_text = "Happening Today! 🎉";
        $holiday_badge_color = "success";
    } elseif ($diff == 1) {
        $holiday_date_text = "Tomorrow";
        $holiday_badge_color = "warning";
    } else {
        $holiday_date_text = "In " . $diff . " days (" . date('d M', strtotime($next_holiday['holiday_date'])) . ")";
        $holiday_badge_color = "primary";
    }
}

// Worker details
$worker = null;
$photoPath = 'https://res.cloudinary.com/YOUR_CLOUD_NAME/image/upload/v1/default-avatar.png';
if ($auto_db && $worker_id) {
    $stmt = $conn->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('i', $worker_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $worker = $res->fetch_assoc();
        $stmt->close();
    }
    if ($worker && !empty($worker['photo'])) {
        $photo = $worker['photo'];
        // Always use Cloudinary URL from DB
        if (preg_match('#^https?://#', $photo)) {
            $photoPath = $photo;
        }
    }
}

// Greeting based on time
$hour = date('H');
if ($hour < 12) $greeting = "Good Morning";
elseif ($hour < 17) $greeting = "Good Afternoon";
else $greeting = "Good Evening";

// Traffic analytics
$traffic_file = __DIR__ . '/traffic.json';
$traffic = [];
if (file_exists($traffic_file)) {
    $traffic = json_decode(file_get_contents($traffic_file), true) ?: [];
}
$now = time();
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$traffic[$ip] = $now;
foreach ($traffic as $k => $v) if ($v < $now - 600) unset($traffic[$k]);
file_put_contents($traffic_file, json_encode($traffic));
$active_users = count($traffic);

$workerName = $worker ? s($worker['name']) : 'Worker Name';
$workerId = $worker_id ? s($worker_id) : '--';
$workerPhone = $worker ? s($worker['phone']) : '--';
$workerEmail = $worker ? s($worker['email'] ?? '--') : '--';
$workerAadhaar = $worker ? s($worker['aadhaar'] ?? '') : '';
$workerJoiningDate = $worker ? s($worker['joining_date'] ?? '--') : '--';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

<style>
    :root {
        --primary: #4f46e5; /* Indigo */
        --primary-dark: #2563eb; /* Strong Blue for ID Card */
        --primary-light: #e0e7ff;
        --secondary: #64748b;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    /* --- Hero Section --- */
    .dashboard-hero {
        background: linear-gradient(135deg, var(--primary), #818cf8);
        color: white;
        padding: 10px 10px 60px 10px; /* Extra padding bottom for overlap */
        border-radius: 0 0 25px 25px;
        margin-bottom: -80px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
    }
    .hero-brand { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    .hero-brand .logo-box { background: rgba(255,255,255,0.2); padding: 5px 12px; border-radius: 8px; backdrop-filter: blur(5px); }
    .hero-welcome h2 { margin: 0; font-weight: 700; font-size: 1.8rem; }
    .hero-welcome p { opacity: 0.9; margin: 0; font-size: 0.95rem; }

    /* --- Layout Container --- */
    .main-container {
        max-width: 1140px;
        margin: 0 auto;
        padding: 0 15px;
    }

    /* --- Cards General --- */
    .modern-card {
        background: var(--card-bg);
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s, box-shadow 0.2s;
        overflow: hidden;
    }

    /* --- Action Menu Cards --- */
    .action-card {
        display: block;
        text-decoration: none;
        color: var(--text-dark);
        padding: 24px;
        height: 100%;
        position: relative;
    }
    .action-card:hover { transform: translateY(-5px); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); color: var(--primary); }
    .icon-bubble {
        width: 50px; height: 50px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem; margin-bottom: 15px;
        transition: all 0.3s;
    }
    /* Icon Colors */
    .icon-attend { background: #dbeafe; color: #2563eb; }
    .icon-notice { background: #ffedd5; color: #ea580c; }
    .icon-leave  { background: #dcfce7; color: #16a34a; }
    .icon-pay    { background: #f3e8ff; color: #9333ea; }
    .icon-prof   { background: #f1f5f9; color: #475569; }
    .icon-msg    { background: #fee2e2; color: #dc2626; }

    .action-card h5 { font-weight: 700; margin-bottom: 5px; font-size: 1.1rem; }
    .action-card span { font-size: 0.85rem; color: var(--secondary); display: block; line-height: 1.4; }

    /* --- Sidebar: ID Card --- */
    .id-widget {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #e2e8f0;
        text-align: center;
        padding: 25px 20px;
    }
    .id-photo-wrapper {
        position: relative;
        width: 90px; height: 90px;
        margin: 0 auto 15px auto;
    }
    .id-photo-wrapper img {
        width: 100%; height: 100%; object-fit: cover;
        border-radius: 50%;
        border: 3px solid white;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .id-status {
        position: absolute; bottom: 2px; right: 2px;
        width: 18px; height: 18px; background: #22c55e;
        border: 2px solid white; border-radius: 50%;
    }
    .id-name { font-weight: 700; font-size: 1.2rem; margin-bottom: 2px; color: #0f172a; }
    .id-role { color: var(--secondary); font-size: 0.9rem; margin-bottom: 15px; font-weight: 500; }
    .id-meta {
        background: #f1f5f9; border-radius: 8px; padding: 10px;
        font-size: 0.85rem; color: #334155; margin-bottom: 15px;
        text-align: left;
    }
    .id-meta div { display: flex; justify-content: space-between; margin-bottom: 4px; }
    .id-meta div:last-child { margin-bottom: 0; }

    /* --- Holiday Banner --- */
    .holiday-banner {
        background: #fff;
        border-left: 5px solid;
        border-radius: 12px;
        padding: 20px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
    }
    .holiday-banner.border-primary { border-color: var(--primary); }
    .holiday-banner.border-warning { border-color: #f59e0b; }
    .holiday-banner.border-success { border-color: #10b981; }

    /* --- Modals --- */
    .qr-box { background: #fff; padding: 20px; display: inline-block; border-radius: 12px; border: 1px solid #eee; }
    
    /* ID Card Modern Styling (for #viewCardModal) */
    .id-card-modern {
        background: var(--primary-dark);
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 8px 32px rgba(37, 99, 235, 0.12);
        max-width: 340px;
        margin: auto;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .dashboard-hero { border-radius: 0 0 10px 10px; margin-bottom: -90px; }
        .hero-brand { font-size: 1.2rem; }
        .hero-welcome h2 { font-size: 1.4rem; }
        .holiday-banner { flex-direction: column; align-items: flex-start; gap: 10px; }
        .holiday-banner .badge { margin-top: 5px; }
    }
</style>

<div class="dashboard-hero">
    <div class="main-container">
        <div class="d-flex justify-content-between align-items-start mb-4">
        
        </div>
        
        <div class="hero-welcome">
            <div class="row align-items-end">
                <div class="col-md-8">
                    <p class="mb-1"><?= $greeting ?>,</p>
                    <h2><?= $worker ? s($worker['name']) : 'Worker' ?></h2>
                </div>
                <div class="col-md-4 text-md-end mt-3 mt-md-0">
                    <button class="btn btn-light btn-sm text-primary shadow-sm fw-bold" id="openQRBtn">
                        <i class="bi bi-qr-code-scan me-1"></i> Show QR Code
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="main-container" style="margin-top: 20px;">
    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="modern-card holiday-banner border-<?= $holiday_badge_color ?>">
                <div>
                    <h6 class="text-uppercase text-muted small fw-bold mb-1">Upcoming Holiday</h6>
                    <h4 class="mb-0 fw-bold text-dark"><?= s($holiday_title) ?></h4>
                    <small class="text-<?= $holiday_badge_color ?> fw-bold mt-1 d-block">
                        <i class="bi bi-calendar-event me-1"></i> <?= s($holiday_date_text) ?>
                    </small>
                </div>
                <a href="holidays.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">View All</a>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-md-4">
                    <div class="modern-card p-3 d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2">
                            <i class="bi bi-people fs-4"></i>
                        </div>
                        <div style="line-height: 1.2;">
                            <h5 class="mb-0 fw-bold"><?= $active_users ?></h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Online Now</small>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="modern-card p-3 d-flex align-items-center gap-3">
                        <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2">
                            <i class="bi bi-bell fs-4"></i>
                        </div>
                        <div style="line-height: 1.2;">
                            <h5 class="mb-0 fw-bold"><?= $urgent_notices > 0 ? $urgent_notices : $total_notices ?></h5>
                            <small class="text-muted" style="font-size: 0.75rem;">Notices</small>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="text-muted small fw-bold text-uppercase mb-3 ps-1">Quick Actions</h6>
            <div class="row g-3">
                <div class="col-6 col-md-4">
                    <a href="attendance.php" class="modern-card action-card">
                        <div class="icon-bubble icon-attend"><i class="bi bi-fingerprint"></i></div>
                        <h5>Attendance</h5>
                        <span><?= s(attendance_text($attendance_mode)) ?></span>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="notices.php" class="modern-card action-card">
                        <div class="icon-bubble icon-notice"><i class="bi bi-megaphone"></i></div>
                        <h5>Notices</h5>
                        <span><?= $urgent_notices > 0 ? $urgent_notices.' Urgent!' : 'Latest Updates' ?></span>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="leave.php" class="modern-card action-card">
                        <div class="icon-bubble icon-leave"><i class="bi bi-airplane"></i></div>
                        <h5>Apply Leave</h5>
                        <span>Request off & History</span>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="payment.php" class="modern-card action-card">
                        <div class="icon-bubble icon-pay"><i class="bi bi-wallet2"></i></div>
                        <h5>My Pay</h5>
                        <span>Slips & History</span>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="messages.php" class="modern-card action-card">
                        <div class="icon-bubble icon-msg"><i class="bi bi-chat-dots"></i></div>
                        <h5>Messages</h5>
                        <span>Chat with Admin</span>
                    </a>
                </div>
                <div class="col-6 col-md-4">
                    <a href="profile.php" class="modern-card action-card">
                        <div class="icon-bubble icon-prof"><i class="bi bi-person-gear"></i></div>
                        <h5>Profile</h5>
                        <span>Settings & Docs</span>
                    </a>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="modern-card id-widget mb-4">
                <div class="id-photo-wrapper">
                    <img src="<?= s($photoPath) ?>" alt="Profile">
                    <div class="id-status" title="Active"></div>
                </div>
                <div class="id-name"><?= $worker ? s($worker['name']) : 'Worker Name' ?></div>
                <div class="id-role">General Worker</div>
                
                <div class="id-meta">
                    <div><span>ID No:</span> <strong>#<?= s($worker_id) ?></strong></div>
                    <div><span>Phone:</span> <strong><?= $worker ? s($worker['phone']) : '--' ?></strong></div>
                    <div><span>Joined:</span> <strong><?= $worker ? s($worker['joining_date'] ?? 'N/A') : '--' ?></strong></div>
                </div>

                <div class="d-grid gap-2">
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#viewCardModal">
                        <i class="bi bi-eye me-2"></i> View Full ID
                    </button>
                    <a href="worker_digital_attendance.php" class="btn btn-outline-success btn-sm">
                        <i class="bi bi-fingerprint me-2"></i> Mark Digital Attendance
                    </a>
                </div>
            </div>

            <div class="modern-card p-4">
                <div class="d-flex align-items-center gap-2 mb-3 text-secondary">
                    <i class="bi bi-shield-check fs-5"></i>
                    <h6 class="mb-0 fw-bold">Rules & Guidelines</h6>
                </div>
                <ul class="list-unstyled small text-muted mb-3" style="line-height: 1.6;">
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Mark attendance daily on time.</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Wear safety gear on site.</li>
                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Report issues immediately.</li>
                </ul>
                <a href="rules.php" class="btn btn-link btn-sm p-0 text-decoration-none">Read Full Policy &rarr;</a>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="workerQRModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center border-0 shadow-lg rounded-4">
            <div class="modal-body p-4">
                <h5 class="fw-bold mb-3">Attendance QR Code</h5>
                <div style="display:flex;justify-content:center;align-items:center;">
                    <div id="workerQR" style="background:#fff; padding:18px; border-radius:16px; border:2px solid #222; display:inline-block;"></div>
                </div>
                <div class="mt-3 fw-bold text-dark" style="font-size:1.1rem;">Scan to mark attendance</div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold ms-2">Digital Worker ID</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body pt-0 pb-2">
                <div class="id-card-modern text-center">
                    <div style="background:var(--primary-dark);padding:18px 0 10px 0;display:flex;align-items:center;justify-content:center;gap:10px;">
                        <div style="height:32px;width:32px;border-radius:8px;background:#fff;display:flex;align-items:center;justify-content:center;">
                            <img src="<?= htmlspecialchars($footer_settings['logo_url'] ?? '../admin/assets/111.png') ?>" alt="Logo" style="height:28px;width:28px;object-fit:cover;border-radius:6px;">
                        </div>
                        <span style="color:#fff;font-size:1.2rem;font-weight:700;letter-spacing:1px;">SAMRAT CONSTRUCTION</span>
                        <?php if (!empty($footer_settings['app_download_url'])): ?>
                        <a href="<?= htmlspecialchars($footer_settings['app_download_url']) ?>" class="btn btn-outline-info ms-3 rounded-pill px-3 py-1" target="_blank" style="font-size:0.95rem;">
                            <i class="bi bi-download"></i> App
                        </a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="background:#fff;padding:24px 18px 10px 18px;">
                        <div style="display:flex;align-items:center;gap:16px;justify-content:center;">
                            <img src="<?= s($photoPath) ?>" alt="Worker Photo" style="height:80px;width:80px;border-radius:12px;object-fit:cover;border:3px solid var(--primary-dark);">
                            <div style="text-align:left;">
                                <div style="font-size:1.3rem;font-weight:700;color:var(--primary-dark);line-height:1.1;"><?= $workerName ?></div>
                                <div style="font-size:1rem;color:#555;font-weight:500;">General Worker</div>
                                <div style="font-size:0.9rem;color:#888;">ID: <b style="color:var(--text-dark);"><?= $workerId ?></b></div>
                            </div>
                        </div>
                        
                        <div style="margin-top:20px;text-align:left;font-size:0.9rem;padding:0 10px;">
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:4px 0;">
                                <b>Phone:</b> <span><?= $workerPhone ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:4px 0;">
                                <b>Email:</b> <span><?= $workerEmail ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;border-bottom:1px solid #eee;padding:4px 0;">
                                <b>Aadhaar:</b> <span>**** <?= substr($workerAadhaar, -4) ?></span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding:4px 0;">
                                <b>Joining:</b> <span><?= $workerJoiningDate ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div style="background:#f1f5f9;padding:20px 0;">
                        <div id="workerCardQRDisplay" style="width:120px;height:120px;margin:auto;"></div>
                        <div style="font-size:0.85rem;color:var(--primary-dark);margin-top:10px;font-weight:600;">SCAN FOR IDENTIFICATION</div>
                    </div>
                </div>
                </div>
            
            <div class="modal-footer border-0 justify-content-center pt-0 pb-4">
                <button class="btn btn-primary rounded-pill px-5 fw-bold" onclick="printID()">
                    <i class="bi bi-printer me-2"></i> Print Card
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var workerId = <?= json_encode($worker_id ?: 'N/A') ?>;
    var workerQrData = 'WORKER_ID:' + workerId;

    // --- QR Code Modal Logic (#workerQRModal) ---
    document.getElementById('openQRBtn').addEventListener('click', function(){
        var workerQR = document.getElementById('workerQR');
        // Clear previous QR
        workerQR.innerHTML = '';
        if (workerId && workerId !== 'N/A') {
            new QRCode(workerQR, {
                text: workerQrData, // Use the proper QR data
                width: 240,
                height: 240,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            var qrModal = new bootstrap.Modal(document.getElementById('workerQRModal'));
            qrModal.show();
        } else {
            alert('Worker ID not found.');
        }
    });

    // --- Modern ID Card Modal Logic (#viewCardModal) ---
    var viewCardModal = document.getElementById('viewCardModal');
    viewCardModal.addEventListener('shown.bs.modal', function () {
        var qrElement = document.getElementById('workerCardQRDisplay');
        // Clear previous QR
        qrElement.innerHTML = ''; 
        
        if (workerId && workerId !== 'N/A') {
            new QRCode(qrElement, {
                text: workerQrData, // Use the proper QR data
                width: 120,
                height: 120,
                colorDark : "#000000",
                colorLight : "#f1f5f9", // Match QR container background
                correctLevel : QRCode.CorrectLevel.H
            });
        }
    });

    // Download Logic (Opens Print Window)
    document.getElementById('downloadIdBtn').addEventListener('click', function () {
        printID();
    });
});

function printID() {
    // --- Print Function Logic (YATHAVAT RAKHA GAYA HAI) ---
    var name = <?= json_encode($worker['name'] ?? 'Worker') ?>;
    var photo = <?= json_encode($photoPath) ?>;
    var phone = <?= json_encode($worker['phone'] ?? '-') ?>;
    var wid = <?= json_encode($worker_id) ?>;
    
    var html = `
        <html><head><title>ID Card - ${name}</title>
        <style>
            body { font-family: sans-serif; -webkit-print-color-adjust: exact; display: flex; justify-content: center; align-items: center; height: 100vh; background: #f0f0f0; }
            .id-card { width: 320px; height: 480px; background: #fff; border-radius: 15px; overflow: hidden; box-shadow: 0 0 0 1px #ddd; position: relative; }
            .header { background: #4f46e5; height: 100px; }
            .photo-box { width: 100px; height: 100px; border-radius: 50%; border: 5px solid #fff; margin: -50px auto 10px; overflow: hidden; background: #eee; }
            .photo-box img { width: 100%; height: 100%; object-fit: cover; }
            .content { text-align: center; padding: 0 20px; }
            .name { font-size: 22px; font-weight: bold; margin: 0; color: #333; }
            .role { color: #4f46e5; font-size: 14px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 20px; display: block; }
            .details { text-align: left; font-size: 14px; line-height: 2.2; border-top: 1px solid #eee; padding-top: 10px; }
            .row { display: flex; justify-content: space-between; }
            .label { color: #777; }
            .val { font-weight: 600; color: #333; }
        </style>
        </head><body>
        <div class="id-card">
            <div class="header"></div>
            <div class="photo-box"><img src="${photo}"></div>
            <div class="content">
                <h2 class="name">${name}</h2>
                <span class="role">Worker</span>
                <div class="details">
                    <div class="row"><span class="label">ID Number</span><span class="val">#${wid}</span></div>
                    <div class="row"><span class="label">Phone</span><span class="val">${phone}</span></div>
                    <div class="row"><span class="label">Company</span><span class="val">Samrat Const.</span></div>
                    <div class="row"><span class="label">Issued</span><span class="val"><?= date('M Y') ?></span></div>
                </div>
            </div>
        </div>
        </body></html>`;
        
    var w = window.open('', '_blank');
    w.document.write(html);
    w.document.close();
    setTimeout(function(){ w.print(); }, 500);
}
</script>

<?php include 'footer.php'; ?>