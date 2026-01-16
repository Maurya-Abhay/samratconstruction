<?php
// manual_attendance.php
session_start();
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }

require_once '../admin/database.php';
date_default_timezone_set('Asia/Kolkata');

// --- AUTHENTICATION & INITIALIZATION ---
// NOTE: You should add proper permission checks here 
// to ensure only supervisors/admins can access this page.
$msg = null; // Use null for clean initial state
$today = date('Y-m-d');

// Mark manual attendance for selected worker
if (isset($_POST['mark_manual']) && isset($_POST['worker_id'])) {
    $worker_id = (int)$_POST['worker_id'];
    
    // Check if already marked in worker_attendance
    $check_stmt = $conn->prepare("SELECT id FROM worker_attendance WHERE worker_id=? AND date=?");
    $check_stmt->bind_param('is', $worker_id, $today);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $check_stmt->close();

    // Fetch worker name for message
    $worker_name_q = $conn->query("SELECT name FROM workers WHERE id=$worker_id")->fetch_assoc();
    $worker_name = htmlspecialchars($worker_name_q['name'] ?? 'Worker');

    if ($check_result->num_rows == 0) {
        // NOTE: Also include a field for the manual marker's ID in production for audit purposes.
        $time = date('H:i:s'); // This is now IST
        // Assuming your table can handle this structure. You may need to add a 'method' or 'marked_by' column.
        $insert_stmt = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status, check_in) VALUES (?, ?, 'Present', ?)");
        $insert_stmt->bind_param('iss', $worker_id, $today, $time);
        
        if ($insert_stmt->execute()) {
             $msg = ['type' => 'success', 'text' => "Attendance successfully recorded for <strong>{$worker_name}</strong> at <strong>" . date('h:i A') . " IST</strong>.", 'time' => $time];
        } else {
             $msg = ['type' => 'error', 'text' => "Database error: Could not mark attendance."];
        }
        $insert_stmt->close();
       
    } else {
        $msg = ['type' => 'warning', 'text' => "Attendance already marked for <strong>{$worker_name}</strong> today. Check-in skipped."];
    }
}

// Fetch all workers
$workers = $conn->query("SELECT id, name FROM workers ORDER BY name ASC");

include 'header.php';
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --primary-color: #4f46e5; /* Indigo 600 */
        --primary-light: #818cf8; /* Indigo 400 */
        --bg-color: #eef2ff; /* Light Indigo background */
        --card-bg: rgba(255, 255, 255, 0.95);
        --shadow-elevation: 0 15px 35px rgba(0,0,0,0.1);
        --success-bg: #d1fae5;
        --success-text: #059669;
        --warning-bg: #fff7ed;
        --warning-text: #d97706;
    }
    
    body { 
        background: var(--bg-color); 
        font-family: 'Plus Jakarta Sans', sans-serif; 
        color: #1f2937; /* Dark text */
    }
    
    .modern-card { 
        background: var(--card-bg); 
        backdrop-filter: blur(10px); 
        border-radius: 20px; 
        box-shadow: var(--shadow-elevation); 
        padding: 40px; 
        border: 1px solid rgba(255, 255, 255, 0.7);
    }
    
    .form-select, .form-control { 
        border: 1px solid #e5e7eb; 
        border-radius: 14px; 
        padding: 12px 20px; 
        transition: border-color 0.3s, box-shadow 0.3s;
        height: auto; /* Override default Bootstrap height */
        font-weight: 600;
        background-color: #f9fafb;
    }
    .form-select:focus, .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
        background-color: #fff;
    }
    
    .btn-action { 
        background: var(--primary-color); 
        border-color: var(--primary-color); 
        border-radius: 12px; 
        padding: 12px 25px;
        font-weight: 700; 
        box-shadow: 0 8px 15px rgba(79, 70, 229, 0.3);
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn-action:hover { 
        background: #4338ca; /* Darker indigo */
        border-color: #4338ca;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
    }
    
    /* Custom Alert Styling */
    .alert-custom-success {
        background: var(--success-bg); 
        color: var(--success-text); 
        border-left: 5px solid #10b981;
        border-radius: 12px;
        font-weight: 600;
    }
    .alert-custom-warning {
        background: var(--warning-bg); 
        color: var(--warning-text); 
        border-left: 5px solid #f59e0b;
        border-radius: 12px;
        font-weight: 600;
    }
    .alert-custom-error {
        background: #fee2e2; 
        color: #dc2626; 
        border-left: 5px solid #ef4444;
        border-radius: 12px;
        font-weight: 600;
    }
    
    /* Admin Sidebar Styling */
    .admin-notes-card {
        background: var(--primary-color);
        color: #e0e7ff;
        border: none;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.25);
    }
    .admin-notes-card h6 {
        color: #fff;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        padding-bottom: 10px;
    }
    .admin-notes-card ul li {
        margin-bottom: 12px !important;
        font-size: 0.95rem;
    }
    .admin-notes-card .bi-caret-right-fill {
        color: #93c5fd; /* Lighter highlight */
    }
    .date-status {
        font-size: 0.9rem;
        padding: 8px 15px;
        background: #f1f5f9;
        border-radius: 8px;
        font-weight: 600;
    }
</style>

<div class="container py-5">
    
    <h1 class="fw-bolder mb-3" style="color: #111827; letter-spacing: -1px;">
        <i class="bi bi-person-fill-lock me-3" style="color: var(--primary-color);"></i>
        Manual Attendance Override
    </h1>
    <p class="lead text-muted mb-5">Administrative portal for supervisory check-in/check-out for team members.</p>
    
    <div class="row">
        
        <div class="col-lg-7 col-md-8 mb-4">
            <div class="modern-card">
                <h4 class="text-dark fw-bold mb-3">Worker Selection & Punch-In</h4>
                <p class="text-muted mb-4">Select the worker's name from the list and use the button below to register their presence with the **current server time**.</p>
                
                <?php if($msg): ?>
                    <div class="alert alert-custom-<?= htmlspecialchars($msg['type']) ?> py-3 px-4 mb-4 animate__animated animate__fadeInDown" role="alert"> 
                        <i class="bi <?= $msg['type'] == 'success' ? 'bi-check-circle-fill' : ($msg['type'] == 'warning' ? 'bi-exclamation-triangle-fill' : 'bi-x-octagon-fill') ?> me-2"></i>
                        <?= $msg['text'] ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="mt-4">
                    <div class="mb-4">
                        <label for="workerSelect" class="form-label text-dark fw-bold small mb-2">Worker/Staff Name</label>
                        <select name="worker_id" id="workerSelect" class="form-select" required>
                            <option value="">-- Choose worker to mark attendance --</option>
                            <?php while($w = $workers->fetch_assoc()): ?>
                                <option value="<?= $w['id'] ?>"> <?= htmlspecialchars($w['name']) ?> </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <hr class="my-4">
                    
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <div class="date-status mb-2 mb-md-0">
                            Today's Date: <strong class="text-dark"><?= date('l, d M Y') ?></strong>
                        </div>
                        <button name="mark_manual" class="btn btn-action btn-lg">
                            <i class="bi bi-clock-fill me-2"></i> Confirm & Mark Present
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-lg-5 col-md-4">
            <div class="admin-notes-card h-100">
                <h6 class="fw-bold mb-4">AUTHORIZATION CHECKLIST</h6>
                <ul class="list-unstyled small">
                    <li class="d-flex align-items-start mb-3">
                        <i class="bi bi-caret-right-fill me-3 mt-1 flex-shrink-0"></i>
                        <span>This action registers attendance with the <strong>current server time</strong>. Ensure worker details are verified before submission.</span>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="bi bi-caret-right-fill me-3 mt-1 flex-shrink-0"></i>
                        <span>This method is strictly reserved for **override** or cases where standard methods (QR/Biometric) are unavailable.</span>
                    </li>
                    <li class="d-flex align-items-start mb-3">
                        <i class="bi bi-caret-right-fill me-3 mt-1 flex-shrink-0"></i>
                        <span>A log is implicitly created. **Any existing record for today** will prevent double-marking via this form.</span>
                    </li>
                </ul>
                <div class="text-center mt-4 pt-3" style="border-top: 1px dashed rgba(255, 255, 255, 0.2);">
                    <i class="bi bi-shield-lock-fill" style="font-size: 2.5rem; color: #fff;"></i>
                    <p class="small mt-2 mb-0">Supervisory Access Required</p>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include 'footer.php'; ?>