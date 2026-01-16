<?php
error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
ini_set('display_errors', 0);

session_start();
require_once '../admin/database.php';
date_default_timezone_set('Asia/Kolkata');

// Ensure attendance_log table exists
$conn->query("CREATE TABLE IF NOT EXISTS attendance_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    staff_id INT NOT NULL,
    attendance_date DATE NOT NULL,
    status ENUM('Present','Absent') DEFAULT 'Present',
    check_in_time TIME,
    method VARCHAR(20) DEFAULT 'Manual'
)");

$today = date('Y-m-d');
$staff_id = $_SESSION['attendance_id'] ?? 1;

// Worker Info
$worker = $conn->query("SELECT * FROM attendence_users WHERE id=$staff_id LIMIT 1")->fetch_assoc();
$worker_name = $worker['name'] ?? 'Worker';
$worker_first = explode(' ', trim($worker_name))[0];
$worker_photo = !empty($worker['image'])
    ? $worker['image']
    : 'https://ui-avatars.com/api/?name='.urlencode($worker_name).'&background=6366f1&color=fff';

// Attendance Check
$att_check = $conn->query("SELECT * FROM attendance_log WHERE staff_id=$staff_id AND attendance_date='$today'")->fetch_assoc();
$is_present = ($att_check && $att_check['status'] === 'Present');
$check_in_time = $is_present ? date('h:i A', strtotime($att_check['check_in_time'])) : '-- : --';

// Team Board
$team_sql = "SELECT w.id, w.name, w.photo AS image, wa.status, wa.check_in AS check_in_time
             FROM workers w
             LEFT JOIN worker_attendance wa ON w.id = wa.worker_id AND wa.date = '$today'
             ORDER BY w.name ASC";
$team_result = $conn->query($team_sql);

$team_members = [];
$total_present = 0;
while($row = $team_result->fetch_assoc()) {
    if(isset($row['status']) && $row['status'] === 'Present') {
        $total_present++;
    }
    $team_members[] = $row;
}
$total_absent = count($team_members) - $total_present;

$present_count = $total_present;
$absent_count = $total_absent;

// QR code generation
$worker_qr = 'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . urlencode('WORKER_ID:' . $staff_id);

$worker_phone = $worker['phone'] ?? 'N/A';
$worker_salary_type = isset($worker['salary_type']) ? ucfirst($worker['salary_type']) : 'Standard';

include 'header.php';
?>

<!-- HTML BELOW (unchanged) -->


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    
    <!-- Modern Assets -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4338ca;
            --secondary: #8b5cf6;
            --accent: #f59e0b;
            --bg-body: #f8fafc;
            --text-main: #1e293b;
        }

        body { 
            background: var(--bg-body); 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* --- HERO SECTION WITH CURVE --- */
        .hero-banner {
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
            padding: 40px 0 80px; /* Extra padding bottom for overlap */
            border-radius: 0 0 30px 30px;
            margin-bottom: -60px; /* Negative margin for overlap */
            position: relative;
            z-index: 1;
            box-shadow: 0 20px 40px -10px rgba(99, 102, 241, 0.3);
            color: white;
        }

        /* --- GLASS CARDS --- */
        .glass-card {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 24px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.06);
            padding: 24px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .glass-card:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 15px 35px rgba(0,0,0,0.1); 
        }

        /* --- FINGERPRINT SCANNER ANIMATION --- */
        .scan-ring {
            width: 100px; height: 100px;
            background: rgba(16, 185, 129, 0.05);
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto;
            position: relative;
        }
        .scan-ring::after {
            content: ''; position: absolute;
            width: 100%; height: 100%; border-radius: 50%;
            border: 3px solid #10b981;
            border-top-color: transparent;
            border-left-color: transparent;
            animation: spin 1.5s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        /* --- FLOATING PROFILE --- */
        .profile-float-card {
            background: #fff;
            margin-top: 0;
        }
        .profile-hero-img {
            width: 90px; height: 90px; border-radius: 50%;
            border: 4px solid #fff;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            margin-top: -60px;
            background: #eef2ff;
            object-fit: cover;
            position: relative;
            z-index: 2;
        }

        /* --- TEAM BOARD --- */
        .team-row {
            padding: 12px 0;
            border-bottom: 1px dashed #e2e8f0;
            display: flex; align-items: center; justify-content: space-between;
            transition: background 0.2s;
        }
        .team-row:hover { background: #f8fafc; padding-left: 5px; padding-right: 5px; border-radius: 8px; }
        .team-row:last-child { border: none; }
        
        .status-badge {
            font-size: 0.65rem; font-weight: 800; text-transform: uppercase;
            padding: 5px 12px; border-radius: 20px; letter-spacing: 0.5px;
        }
        .badge-present { background: #dcfce7; color: #15803d; }
        .badge-absent { background: #fee2e2; color: #b91c1c; }

        /* --- ACTION SQUARES --- */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
        }
        .action-sq {
            aspect-ratio: 1;
            border-radius: 20px;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            font-size: 1.5rem; color: white; transition: 0.3s;
            border: none; outline: none; text-decoration: none;
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .action-sq span { font-size: 0.75rem; margin-top: 5px; font-weight: 600; opacity: 0.9; }
        .btn-blue { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .btn-purple { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .btn-orange { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .action-sq:hover { transform: translateY(-5px) scale(1.02); filter: brightness(1.1); }

        /* --- ID CARD MODAL --- */
        .id-card-body {
            background-image: radial-gradient(#e0e7ff 1px, transparent 1px);
            background-size: 20px 20px;
        }
        .receipt-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.9rem; }
    </style>
</head>
<body>

<!-- HERO HEADER -->
<div class="hero-banner">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="fw-bold mb-0 text-white">Hello, <?= $worker_first ?>! 👋</h1>
                <p class="mb-0 opacity-75 fw-light">Let's make today productive.</p>
            </div>
            <button class="btn btn-light rounded-pill px-4 fw-bold shadow-sm text-primary" data-bs-toggle="modal" data-bs-target="#idCardModal">
                <i class="bi bi-person-badge-fill me-2"></i>My ID
            </button>
        </div>
    </div>
</div>

<!-- MAIN CONTENT (Overlapping) -->
<div class="container" style="position: relative; z-index: 10;">
    <div class="row g-4">
        
        <!-- LEFT COLUMN: Main Stats & Actions -->
        <div class="col-lg-8">
            <div class="row g-4">
                
                <!-- 1. ATTENDANCE CARD -->
                <div class="col-md-6">
                    <div class="glass-card text-center h-100 d-flex flex-column justify-content-center">
                        <h5 class="fw-bold text-dark mb-1">Today's Status</h5>
                        <div class="text-muted small mb-4"><?= date('l, d M Y') ?></div>

                        <div class="py-2">
                            <div class="scan-ring mb-3">
                                <i class="bi bi-fingerprint fs-1 text-success"></i>
                            </div>
                            <?php if($is_present): ?>
                                <div class="badge bg-success bg-opacity-10 text-success px-4 py-2 rounded-pill border border-success border-opacity-25 shadow-sm">
                                    <i class="bi bi-check-circle-fill me-1"></i> Checked In: <?= $check_in_time ?>
                                </div>
                            <?php else: ?>
                                <div class="mb-2 text-danger fw-bold small">Not Checked In Yet</div>
                                <a href="manual_attendance.php" class="btn btn-primary rounded-pill px-4 fw-bold shadow-lg w-100">
                                    Check In Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- 2. QUICK STATS -->
                <div class="col-md-6">
                    <div class="glass-card h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold text-secondary mb-0">COMPANY STATS</h6>
                            <span class="badge bg-primary rounded-pill">Today</span>
                        </div>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="p-3 rounded-4 bg-success bg-opacity-10 border border-success border-opacity-25 text-center">
                                    <div class="small text-success fw-bold">PRESENT</div>
                                    <h2 class="mb-0 fw-bold text-success mt-1"><?= $present_count ?></h2>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-3 rounded-4 bg-danger bg-opacity-10 border border-danger border-opacity-25 text-center">
                                    <div class="small text-danger fw-bold">ABSENT</div>
                                    <h2 class="mb-0 fw-bold text-danger mt-1"><?= $absent_count ?></h2>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex align-items-center bg-warning bg-opacity-10 p-2 rounded-3 border border-warning border-opacity-25">
                                <div class="bg-warning text-white rounded p-2 text-center me-3 shadow-sm" style="min-width: 50px;">
                                    <small class="d-block fw-bold lh-1" style="font-size: 0.65rem;">DEC</small>
                                    <span class="d-block fs-5 fw-bold lh-1">25</span>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">Christmas</h6>
                                    <small class="text-muted">Upcoming Holiday</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. TEAM BOARD -->
                <div class="col-12">
                    <div class="glass-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="fw-bold mb-0 text-dark">Team Board</h5>
                            <button class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold">View All</button>
                        </div>

                        <div class="px-1">
                            <?php foreach($team_members as $tm): 
                                $status_class = ($tm['status'] == 'Present') ? 'badge-present' : 'badge-absent';
                                $img = !empty($tm['image']) ? $tm['image'] : 'https://ui-avatars.com/api/?name='.urlencode($tm['name']).'&background=random&color=fff';
                                $check_in = ($tm['status'] == 'Present' && $tm['attendance_time']) ? date('h:i A', strtotime($tm['attendance_time'])) : '';
                                $status_text = ($tm['status'] == 'Present') ? 'PRESENT' : 'ABSENT';
                            ?>
                            <div class="team-row">
                                <div class="d-flex align-items-center">
                                    <img src="<?= $img ?>" class="rounded-circle me-3 shadow-sm" style="width:42px; height:42px; object-fit:cover;">
                                    <div>
                                        <h6 class="mb-0 fw-bold text-dark small"><?= htmlspecialchars($tm['name']) ?></h6>
                                        <small class="text-muted" style="font-size:0.75rem;">Worker</small>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                                    <?php if($check_in): ?>
                                        <div class="text-muted small mt-1 fw-bold" style="font-size:0.65rem">In: <?= $check_in ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Profile & Menu -->
        <div class="col-lg-4">
            <div class="glass-card profile-float-card text-center p-4">
                
                <!-- Online Indicator -->
                <div class="position-absolute top-0 end-0 p-4">
                    <div class="bg-success rounded-circle border border-white border-2 shadow-sm" style="width:12px; height:12px;"></div>
                </div>
                
                <img src="<?= $worker_photo ?>" class="profile-hero-img">
                
                <h4 class="fw-bold mt-3 mb-0 text-dark"><?= $worker_name ?></h4>
                <div class="text-muted small mb-4">ID: #<?= str_pad($staff_id, 4, '0', STR_PAD_LEFT) ?></div>

                <div class="p-3 rounded-4 bg-dark bg-opacity-75 text-white mb-4 shadow-sm backdrop-blur-md">
                    <div class="d-flex justify-content-between small opacity-75 mb-1">
                        <span>Shift Progress</span>
                        <span>09:00 - 18:00</span>
                    </div>
                    <div class="progress bg-white bg-opacity-25" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 45%"></div>
                    </div>
                </div>

                <h6 class="text-start fw-bold text-secondary mb-3 small">QUICK ACTIONS</h6>
                <div class="action-grid">
                    <a href="profile.php" class="action-sq btn-blue" title="Profile">
                        <i class="bi bi-person-circle"></i>
                        <span>Profile</span>
                    </a>
                    <a href="messages.php" class="action-sq btn-purple" title="Message">
                        <i class="bi bi-chat-dots-fill"></i>
                        <span>Chat</span>
                    </a>
                    <a href="leave.php" class="action-sq btn-orange" title="Leave">
                        <i class="bi bi-calendar2-minus-fill"></i>
                        <span>Leave</span>
                    </a>
                    <a href="manual_attendance.php" class="action-sq btn-blue" title="Mark Attendance">
                        <i class="bi bi-clipboard-check-fill"></i>
                        <span>Mark In</span>
                    </a>
                    <a href="qr_attendance.php" class="action-sq btn-purple" title="QR Scan">
                        <i class="bi bi-qr-code-scan"></i>
                        <span>Scan</span>
                    </a>
                    <a href="user_attendance.php" class="action-sq btn-orange" title="Payslips">
                        <i class="bi bi-cash-stack"></i>
                        <span>Pay</span>
                    </a>
                </div>

            </div>
        </div>

    </div>
</div>

<!-- ID CARD MODAL -->
<div class="modal fade" id="idCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:16px; max-width:350px; margin:auto; border:2px dashed #ccc; padding:0;">
            <div class="text-center pt-4 pb-2 position-relative">
                <span class="fw-bold" style="font-size:1.3rem;">EMPLOYEE ID CARD</span>
                <span class="position-absolute end-0 top-0 p-2" style="cursor:pointer; font-size:1.5rem;" data-bs-dismiss="modal" aria-label="Close">&times;</span>
                <div class="small text-muted mb-2">Official Identity</div>
            </div>
            <div class="px-4 pb-4 pt-2">
                <div class="text-center mb-3">
                    <img src="<?= $worker_photo ?>" alt="User Image" style="width:70px; height:70px; border-radius:50%; border:2px solid #6366f1; object-fit:cover; background:#f8fafc;">
                </div>
                <div class="receipt-row" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span class="text-muted">ID:</span>
                    <span class="fw-bold">#<?= str_pad($staff_id, 4, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="receipt-row" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span class="text-muted">Name:</span>
                    <span class="fw-bold"><?= $worker_name ?></span>
                </div>
                <div class="receipt-row" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span class="text-muted">Phone:</span>
                    <span class="fw-bold"><?= $worker_phone ?></span>
                </div>
                <div class="receipt-row" style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span class="text-muted">Salary Type:</span>
                    <span class="fw-bold"><?= $worker_salary_type ?: 'Standard' ?></span>
                </div>
                <hr class="my-2">
                <div class="text-center mt-3">
                    <img src="<?= $worker_qr ?>" alt="QR Code" style="width:120px; height:120px; border:1px solid #eee;">
                    <div class="small text-muted mt-1">Scan to verify ID</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php include 'footer.php'; ?>
</body>
</html>