<?php
// attendance.php (Modern Light Premium redesign)
session_start();

$page_title = "Attendance";
$show_back_btn = true;
include 'header.php';

require_once __DIR__ . '/../admin/lib_common.php';

// Ensure DB connection ($conn) is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (file_exists(__DIR__ . '/../admin/database.php')) {
        require_once __DIR__ . '/../admin/database.php';
    }
}

$worker_id = $_SESSION['worker_id'] ?? null;
if (!$worker_id) {
    header('Location: login.php');
    exit();
}

// Fetch worker info
$worker = null;
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT * FROM workers WHERE id = ? LIMIT 1");
    $stmt->bind_param('i', $worker_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $worker = $res->fetch_assoc();
    $stmt->close();
}
if (!$worker) {
    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'error',title:'Worker Not Found',text:'Worker not found!',showConfirmButton:false,timer:2000});</script>";
    include 'footer.php';
    exit();
}

// Fetch attendance history
$attendance = [];
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT * FROM worker_attendance WHERE worker_id = ? ORDER BY date DESC");
    $stmt->bind_param('i', $worker_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $attendance[] = $r;
    $stmt->close();
}

// Summary counts
$summary = ['Present'=>0, 'Absent'=>0, 'Leave'=>0, 'Half Day'=>0];
foreach ($attendance as $row) {
    if (isset($summary[$row['status']])) $summary[$row['status']]++;
}

// Today's attendance
$today = date('Y-m-d');
$todayStatus = null;
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT status FROM worker_attendance WHERE worker_id = ? AND date = ? LIMIT 1");
    $stmt->bind_param('is', $worker_id, $today);
    $stmt->execute();
    $res = $stmt->get_result();
    $todayStatus = $res->fetch_assoc();
    $stmt->close();
}
$marked = $todayStatus && in_array($todayStatus['status'], ['Present','Leave','Half Day']);

// Payments / financial snapshot
$total_present_payment = ($summary['Present']) * floatval($worker['salary'] ?? 0.0);
$total_paid = 0.0;
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare("SELECT SUM(amount) AS total_paid FROM worker_payments WHERE worker_id = ?");
    $stmt->bind_param('i', $worker_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $rowPay = $res->fetch_assoc();
    $total_paid = floatval($rowPay['total_paid'] ?? 0);
    $stmt->close();
}
$remaining_due = $total_present_payment - $total_paid;

function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #64748b;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
        --success-soft: #dcfce7;
        --success-text: #166534;
        --danger-soft: #fee2e2;
        --danger-text: #991b1b;
        --warning-soft: #fef3c7;
        --warning-text: #92400e;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    /* Container */
    .att-container {
        max-width: 1000px;
        margin: 30px auto;
        padding: 0 15px;
    }

    /* Cards */
    .modern-card {
        background: var(--card-bg);
        border: none;
        border-radius: 16px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        margin-bottom: 20px;
        padding: 20px;
    }

    /* Header Section */
    .header-card {
        background: linear-gradient(135deg, var(--primary), #818cf8);
        color: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 25px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }
    .header-info h2 { font-weight: 700; margin: 0; font-size: 1.8rem; }
    .header-info p { margin: 5px 0 0; opacity: 0.9; font-size: 0.95rem; }
    
    .status-badge-lg {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(5px);
        padding: 10px 20px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
        border: 1px solid rgba(255,255,255,0.3);
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 25px;
    }
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        display: flex;
        align-items: center;
        gap: 15px;
        border: 1px solid rgba(0,0,0,0.04);
    }
    .stat-icon {
        width: 50px; height: 50px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
        flex-shrink: 0;
    }
    .stat-text h3 { margin: 0; font-weight: 700; font-size: 1.5rem; color: var(--text-dark); }
    .stat-text span { color: var(--secondary); font-size: 0.85rem; font-weight: 500; }

    /* Colors for stats */
    .bg-soft-success { background: var(--success-soft); color: var(--success-text); }
    .bg-soft-danger { background: var(--danger-soft); color: var(--danger-text); }
    .bg-soft-warning { background: var(--warning-soft); color: var(--warning-text); }
    .bg-soft-primary { background: #e0e7ff; color: var(--primary); }

    /* Financial Card */
    .finance-card {
        border-left: 5px solid var(--primary);
    }
    .finance-item {
        margin-bottom: 10px;
    }
    .finance-label { font-size: 0.85rem; color: var(--secondary); margin-bottom: 2px; }
    .finance-val { font-size: 1.1rem; font-weight: 700; }

    /* Report Section */
    .report-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        padding: 20px;
    }

    /* Table */
    .table-responsive {
        border-radius: 12px;
        overflow: hidden;
    }
    .custom-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }
    .custom-table thead th {
        background: #f1f5f9;
        color: var(--secondary);
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        padding: 15px;
        border-bottom: 1px solid #e2e8f0;
    }
    .custom-table tbody td {
        background: white;
        padding: 15px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 0.95rem;
        vertical-align: middle;
    }
    .custom-table tbody tr:last-child td { border-bottom: none; }
    .custom-table tbody tr:hover td { background: #f8fafc; }

    /* Badges */
    .badge-pill {
        padding: 6px 12px;
        border-radius: 30px;
        font-size: 0.8rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-present { background: var(--success-soft); color: var(--success-text); }
    .badge-absent { background: var(--danger-soft); color: var(--danger-text); }
    .badge-leave { background: var(--warning-soft); color: var(--warning-text); }
    .badge-half { background: #e0f2fe; color: #0369a1; }
    
    @media (max-width: 768px) {
        .header-card { flex-direction: column; align-items: flex-start; }
        .header-card .btn { width: 100%; }
        .stats-grid { grid-template-columns: 1fr 1fr; }
    }
</style>

<div class="att-container">

    <div class="header-card">
        <div class="header-info">
            <h2>Attendance</h2>
            <p>
                <i class="bi bi-person-circle me-1"></i> <?= s($worker['name']) ?> 
                <span class="opacity-50 mx-2">|</span> 
                ID: #<?= s($worker_id) ?>
            </p>
        </div>

        <div class="d-flex flex-column align-items-end gap-2 w-100-mobile">
            <?php if ($marked): ?>
                <div class="status-badge-lg">
                    <i class="bi bi-check-circle-fill text-success"></i> 
                    <span>Marked: <?= s($todayStatus['status']) ?></span>
                </div>
            <?php else: ?>
                <div class="status-badge-lg" style="background: rgba(255,200,200,0.2); border-color: rgba(255,200,200,0.4);">
                    <i class="bi bi-exclamation-triangle-fill text-warning"></i> 
                    <span>Pending for Today</span>
                </div>
                <a href="#" class="btn btn-light text-primary fw-bold shadow-sm w-100">
                    <i class="bi bi-fingerprint me-2"></i> Mark Now
                </a>
            <?php endif; ?>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold small mb-3 ps-1">Overview</h6>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-soft-success">
                <i class="bi bi-check-lg"></i>
            </div>
            <div class="stat-text">
                <h3><?= s($summary['Present']) ?></h3>
                <span>Present Days</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-danger">
                <i class="bi bi-x-lg"></i>
            </div>
            <div class="stat-text">
                <h3><?= s($summary['Absent']) ?></h3>
                <span>Absent Days</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-warning">
                <i class="bi bi-pause-fill"></i>
            </div>
            <div class="stat-text">
                <h3><?= s($summary['Leave']) ?></h3>
                <span>Leaves Taken</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-soft-primary">
                <i class="bi bi-calendar4-week"></i>
            </div>
            <div class="stat-text">
                <h3><?= s($summary['Present'] + $summary['Leave'] + $summary['Absent'] + $summary['Half Day']) ?></h3>
                <span>Total Days</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="modern-card finance-card h-100">
                <div class="d-flex align-items-center mb-4">
                    <div class="p-2 bg-light rounded-circle me-3 text-primary"><i class="bi bi-wallet2 fs-4"></i></div>
                    <h5 class="mb-0 fw-bold">Financial Snapshot</h5>
                </div>
                
                <div class="row">
                    <div class="col-md-4 finance-item border-end">
                        <div class="finance-label">Total Earned</div>
                        <div class="finance-val text-success">₹<?= number_format($total_present_payment, 2) ?></div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Based on present days</small>
                    </div>
                    <div class="col-md-4 finance-item border-end">
                        <div class="finance-label">Amount Paid</div>
                        <div class="finance-val text-dark">₹<?= number_format($total_paid, 2) ?></div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Received to date</small>
                    </div>
                    <div class="col-md-4 finance-item">
                        <div class="finance-label">Remaining Due</div>
                        <div class="finance-val <?= $remaining_due > 0 ? 'text-danger' : 'text-success' ?>">
                            ₹<?= number_format($remaining_due, 2) ?>
                        </div>
                        <small class="text-muted d-block" style="font-size: 0.75rem;">Pending payout</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="modern-card h-100 d-flex flex-column justify-content-center">
                <h6 class="fw-bold mb-3"><i class="bi bi-file-earmark-pdf me-2"></i>Download Report</h6>
                <div class="report-box">
                    <form action="worker_pdf_report.php" method="get" target="_blank">
                        <input type="hidden" name="id" value="<?= s($worker_id) ?>">
                        <div class="mb-2">
                            <label class="small text-muted mb-1">From Date</label>
                            <input type="date" id="fromDate" name="from" class="form-control form-control-sm border-0 shadow-sm" required>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted mb-1">To Date</label>
                            <input type="date" id="toDate" name="to" class="form-control form-control-sm border-0 shadow-sm" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold">
                            Generate PDF
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <h6 class="text-uppercase text-muted fw-bold small mb-3 ps-1">Attendance History</h6>
    <div class="modern-card p-0">
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Date</th>
                        <th>Day</th>
                        <th>Status</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attendance) > 0): 
                        $i=1;
                        foreach ($attendance as $row):
                            $dayName = date('l', strtotime($row['date']));
                            $status = $row['status'];
                            
                            // Determine Badge Style
                            $badgeClass = 'badge-pill bg-light text-dark';
                            $icon = 'bi-circle';
                            
                            if($status == 'Present') { $badgeClass = 'badge-pill badge-present'; $icon = 'bi-check-circle-fill'; }
                            elseif($status == 'Absent') { $badgeClass = 'badge-pill badge-absent'; $icon = 'bi-x-circle-fill'; }
                            elseif($status == 'Leave') { $badgeClass = 'badge-pill badge-leave'; $icon = 'bi-dash-circle-fill'; }
                            elseif($status == 'Half Day') { $badgeClass = 'badge-pill badge-half'; $icon = 'bi-hourglass-split'; }
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= $i++ ?></td>
                            <td class="fw-bold text-dark"><?= date('d M, Y', strtotime($row['date'])) ?></td>
                            <td class="text-muted"><?= s($dayName) ?></td>
                            <td>
                                <span class="<?= $badgeClass ?>">
                                    <i class="bi <?= $icon ?>"></i> <?= s($status) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= s($row['notes'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x display-4 d-block mb-3 opacity-25"></i>
                                No attendance records found.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Simple validation for dates
    document.querySelector('form[action="worker_pdf_report.php"]')?.addEventListener('submit', function(e){
        const from = document.getElementById('fromDate').value;
        const to = document.getElementById('toDate').value;
        if (from && to && from > to) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Date Error',
                text: 'From date cannot be after To date.',
                showConfirmButton: false,
                timer: 2000
            });
        }
    });
</script>

<?php include 'footer.php'; ?>