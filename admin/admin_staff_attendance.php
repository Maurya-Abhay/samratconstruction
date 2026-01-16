<?php
// admin_staff_attendance.php
// COMPLETE NEXT-GEN STAFF MANAGEMENT SYSTEM
require_once 'lib_common.php'; 
date_default_timezone_set('Asia/Kolkata'); 

// --- INCLUDE SYSTEM FILES ---
include 'sidenavbar.php';
include 'topheader.php';

// --- PHP BACKEND LOGIC (NO CHANGE IN LOGIC, JUST OPTIMIZED) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE SALARY SETUP
    if (isset($_POST['update_salary_setup'])) {
        $staff_id = (int)$_POST['staff_id'];
        $salary_type = $_POST['salary_type'];
        $salary = (float)$_POST['salary'];
        $stmt = $conn->prepare("UPDATE attendence_users SET salary_type = ?, salary = ? WHERE id = ?");
        $stmt->bind_param("sdi", $salary_type, $salary, $staff_id);
        if ($stmt->execute()) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => { Swal.fire('Success', 'Salary setup updated!', 'success').then(() => { window.location.href='admin_staff_attendance.php'; }); });</script>";
        } else {
            echo "<script>document.addEventListener('DOMContentLoaded', () => { Swal.fire('Error', '".$stmt->error."', 'error'); });</script>";
        }
        $stmt->close();
    }

    // 2. MARK DAILY ATTENDANCE
    if (isset($_POST['mark_attendance'])) {
        $staff_id = (int)$_POST['staff_id'];
        $date = date('Y-m-d');
        $time = date('H:i:s');
        
        $check_stmt = $conn->prepare("SELECT id FROM attendance_log WHERE staff_id = ? AND attendance_date = ?");
        $check_stmt->bind_param("is", $staff_id, $date);
        $check_stmt->execute();
        if ($check_stmt->get_result()->num_rows > 0) {
            echo "<script>document.addEventListener('DOMContentLoaded', () => { Swal.fire('Info', 'Attendance already marked for today!', 'info'); });</script>";
        } else {
            $log_stmt = $conn->prepare("INSERT INTO attendance_log (staff_id, attendance_date, attendance_time) VALUES (?, ?, ?)");
            $log_stmt->bind_param("iss", $staff_id, $date, $time);
            $log_stmt->execute();
            $conn->query("UPDATE attendence_users SET attendance = attendance + 1 WHERE id = $staff_id");
            echo "<script>document.addEventListener('DOMContentLoaded', () => { Swal.fire('Present', 'Attendance marked successfully!', 'success'); });</script>";
        }
        $check_stmt->close();
    }
    
    // 3. MAKE PAYMENT
    if (isset($_POST['make_payment'])) {
        $staff_id = (int)$_POST['staff_id'];
        $payment_amount = (float)$_POST['payment_amount'];
        $remarks = $_POST['remarks'];
        $date = date('Y-m-d H:i:s');
        $receipt_no = 'PAY' . time() . $staff_id;

        $log_stmt = $conn->prepare("INSERT INTO attendence_payments_log (staff_id, date, amount, receipt_no, remarks) VALUES (?, ?, ?, ?, ?)");
        $log_stmt->bind_param("isdss", $staff_id, $date, $payment_amount, $receipt_no, $remarks);
        
        if ($log_stmt->execute()) {
            $conn->query("UPDATE attendence_users SET payment_given = payment_given + $payment_amount WHERE id = $staff_id");
            echo "<script>document.addEventListener('DOMContentLoaded', () => { Swal.fire('Success', 'Payment of ₹$payment_amount recorded!', 'success').then(() => { window.location.href='admin_staff_attendance.php'; }); });</script>";
        }
        $log_stmt->close();
    }
}

// --- FETCH DATA ---
$staff_result = $conn->query("SELECT * FROM attendence_users");
$payments_result = $conn->query("SELECT * FROM attendence_payments_log ORDER BY date DESC");
$total_staff = $conn->query("SELECT COUNT(*) as cnt FROM attendence_users")->fetch_assoc()['cnt'];

// Staff Arrays for JS
$staffListPay = [];
$staffListMain = [];
if ($staff_result->num_rows > 0) {
    while($row = $staff_result->fetch_assoc()) {
        $staffListMain[] = $row;
        $staffListPay[$row['id']] = $row;
    }
}
$staff_result->data_seek(0); // Reset pointer
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

    <style>
        :root {
            --primary-grad: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --card-bg: rgba(255, 255, 255, 0.9);
            --glass-border: 1px solid rgba(255, 255, 255, 0.5);
            --shadow: 0 10px 30px rgba(0,0,0,0.08);
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f4f6;
            background-image: radial-gradient(at 40% 20%, hsla(28,100%,74%,1) 0px, transparent 50%),
                              radial-gradient(at 80% 0%, hsla(189,100%,56%,1) 0px, transparent 50%),
                              radial-gradient(at 0% 50%, hsla(355,100%,93%,1) 0px, transparent 50%);
            min-height: 100vh;
        }
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(10px);
            border: var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--shadow);
            overflow: hidden;
            transition: transform 0.3s;
        }
        .action-card {
            height: 100%;
            border: none;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
        }
        .action-card:hover { transform: translateY(-5px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .bg-gradient-primary { background: var(--primary-grad) !important; color: white; }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            color: #555;
            font-weight: 600;
            padding: 10px 20px;
        }
        .nav-pills .nav-link.active {
            background: var(--primary-grad);
            color: white;
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.3);
        }
        
        /* Receipt Style */
        .receipt-container {
            border: 2px dashed #cbd5e1;
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            position: relative;
        }
        .receipt-hole {
            width: 20px; height: 20px; background: #333; border-radius: 50%;
            position: absolute; top: 50%; transform: translateY(-50%);
        }
        .receipt-hole.left { left: -10px; }
        .receipt-hole.right { right: -10px; }
                    /* Responsive receipt modal */
                    .receipt-container {
                        max-width: 600px;
                        width: 100%;
                        margin: auto;
                    }
            /* Prevent modal backdrop from dimming screen brightness */
            .modal-backdrop.show {
                opacity: 0 !important;
            }
    </style>
</head>
<body>

<div class="container py-5">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold mb-0 text-dark"><i class="bi bi-people-fill me-2 text-primary"></i>Staff Management</h2>
            <p class="text-muted mb-0">Attendance, Payments & Salary Control</p>
        </div>
        <div class="d-flex gap-2">
            <div class="bg-white px-4 py-2 rounded-pill shadow-sm border">
                <span class="fw-bold text-secondary">Total Staff:</span> 
                <span class="badge bg-primary rounded-pill ms-2"><?= $total_staff ?></span>
            </div>
            <button class="btn btn-dark rounded-pill shadow-sm" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                <i class="bi bi-qr-code-scan me-2"></i>Verify Receipt
            </button>
        </div>
    </div>

    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <div class="action-card bg-white p-4 border-start border-5 border-primary">
                <h5 class="fw-bold text-primary mb-3"><i class="bi bi-gear-fill me-2"></i>Salary Setup</h5>
                <form method="post" id="salarySetupForm">
                    <div class="mb-2">
                        <select name="staff_id" class="form-select" id="salaryStaffSelect" onchange="fillSalaryForm(this.value)">
                            <option value="">Select Staff...</option>
                            <?php foreach($staffListMain as $s) { echo "<option value='{$s['id']}'>{$s['name']}</option>"; } ?>
                        </select>
                    </div>
                    
                    <div id="salaryFormContent" style="display:none; opacity:0; transition: opacity 0.5s;">
                        <div class="input-group mb-2">
                            <span class="input-group-text"><i class="bi bi-briefcase"></i></span>
                            <select name="salary_type" class="form-select" id="salTypeInput">
                                <option value="payday">Pay Per Day</option>
                                <option value="monthly">Monthly Fixed</option>
                            </select>
                        </div>
                        <div class="input-group mb-3">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="salary" id="salAmtInput" step="0.01" class="form-control" placeholder="Amount">
                        </div>
                        <button type="submit" name="update_salary_setup" class="btn btn-outline-primary w-100 fw-bold">Update Salary</button>
                    </div>
                    <div id="salaryStaffInfo"></div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="action-card bg-white p-4 border-start border-5 border-success">
                <h5 class="fw-bold text-success mb-3"><i class="bi bi-calendar-check-fill me-2"></i>Daily Attendance</h5>
                <form method="post">
                    <div class="mb-3">
                        <select name="staff_id" class="form-select" required onchange="showStaffInfoForAttendance(this.value)">
                            <option value="">Select Staff to Mark Present...</option>
                            <?php foreach($staffListMain as $s) { echo "<option value='{$s['id']}'>{$s['name']}</option>"; } ?>
                        </select>
                    </div>
                    <div id="attendanceStaffInfo" class="mb-2"></div>
                    <div class="d-grid">
                        <button type="submit" name="mark_attendance" class="btn btn-success fw-bold">
                            <i class="bi bi-check-circle me-2"></i>Mark Present Today
                        </button>
                    </div>
                    <div class="text-center mt-2">
                        <small class="text-muted">Date: <?= date('d M Y') ?></small>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-4">
            <div class="action-card bg-white p-4 border-start border-5 border-warning">
                <h5 class="fw-bold text-warning mb-3"><i class="bi bi-cash-stack me-2"></i>Record Payment</h5>
                <form method="post" id="paymentActionForm">
                    <div class="mb-2">
                        <select name="staff_id" class="form-select" id="paymentStaffSelect" onchange="showStaffInfoForPayment(this.value)" required>
                            <option value="">Select Staff...</option>
                            <?php foreach($staffListMain as $s) { echo "<option value='{$s['id']}'>{$s['name']}</option>"; } ?>
                        </select>
                    </div>
                    <div id="selectedStaffInfoPay" class="mb-2 small"></div>
                    <div class="input-group mb-2">
                        <span class="input-group-text">₹</span>
                        <input type="number" name="payment_amount" class="form-control" placeholder="Amount" required>
                    </div>
                    <input type="text" name="remarks" class="form-control mb-3" placeholder="Remarks (Optional)">
                    <button type="submit" name="make_payment" class="btn btn-warning w-100 text-white fw-bold">Pay Now</button>
                </form>
            </div>
        </div>
    </div>

    <div class="glass-card p-4">
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-summary"><i class="bi bi-pie-chart-fill me-2"></i>Financial Summary</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-history"><i class="bi bi-receipt me-2"></i>Payment History</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-today"><i class="bi bi-clock-history me-2"></i>Today's Attendance</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-individual"><i class="bi bi-person-vcard me-2"></i>Individual View</button>
            </li>
        </ul>

        <div class="tab-content">
            
            <div class="tab-pane fade show active" id="tab-summary">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="summaryTable">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Att. Days</th>
                                <th>Salary Type</th>
                                <th>Base</th>
                                <th>Total Due</th>
                                <th>Paid</th>
                                <th>Pending</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($staffListMain as $row): 
                                $salary_type = $row['salary_type'] ?? 'payday';
                                $base = (float)($row['salary'] ?? 0);
                                $att = (int)($row['attendance'] ?? 0);
                                $due = ($salary_type == 'payday') ? ($att * $base) : $base;
                                $given = (float)($row['payment_given'] ?? 0);
                                $pending = $due - $given;
                            ?>
                            <tr>
                                <td><span class="badge bg-light text-dark border"><?= $row['id'] ?></span></td>
                                <td class="fw-bold"><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= $att ?></td>
                                <td><span class="badge bg-secondary"><?= ucfirst($salary_type) ?></span></td>
                                <td>₹<?= number_format($base, 2) ?></td>
                                <td class="fw-bold text-primary">₹<?= number_format($due, 2) ?></td>
                                <td class="text-success">₹<?= number_format($given, 2) ?></td>
                                <td>
                                    <?php if($pending > 0): ?>
                                        <span class="badge bg-danger">Pending: ₹<?= number_format($pending, 2) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Cleared</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-history">
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="historyTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Staff Name</th>
                                <th>Amount</th>
                                <th>Receipt</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
<?php 
$receiptModals = [];
$modalIndex = 1;
if($payments_result && $payments_result->num_rows > 0) {
    while($row = $payments_result->fetch_assoc()) {
        $s = isset($staffListPay[$row['staff_id']]) ? $staffListPay[$row['staff_id']] : ['name'=>'Unknown', 'salary'=>0, 'salary_type'=>'N/A'];
?>
<tr>
    <td><?= date('d M Y', strtotime($row['date'])) ?></td>
    <td><?= htmlspecialchars($s['name']) ?></td>
    <td class="fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></td>
    <td class="font-monospace small"><?= $row['receipt_no'] ?></td>
    <td>
        <button class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#receiptModal<?= $modalIndex ?>">
            <i class="bi bi-eye"></i> Receipt
        </button>
    </td>
</tr>
<?php 
$receiptModals[] = [
    'modalIndex' => $modalIndex,
    'row' => $row,
    's' => $s
];
$modalIndex++;
    }
}
?>
</tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-today">
                <table class="table table-bordered align-middle">
                    <thead class="table-light"><tr><th>Name</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php 
                        $today = date('Y-m-d');
                        foreach($staffListMain as $row): 
                            $staff_id = $row['id'];
                            $isPresent = $conn->query("SELECT id FROM attendance_log WHERE staff_id='$staff_id' AND attendance_date='$today'")->num_rows > 0;
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <?php if($isPresent): ?>
                                    <span class="badge bg-success w-100 py-2">PRESENT</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-danger border border-danger w-100 py-2">ABSENT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="tab-pane fade" id="tab-individual">
                <form method="post" id="viewStaffForm" class="mb-4" onsubmit="return false;">
                     <label class="form-label fw-bold">Select Staff to View Full Profile:</label>
                     <select name="view_staff_id" class="form-select w-50" onchange="this.form.submit()">
                        <option value="">Choose...</option>
                        <?php foreach($staffListMain as $s) { 
                            $sel = (isset($_POST['view_staff_id']) && $_POST['view_staff_id'] == $s['id']) ? 'selected' : '';
                            echo "<option value='{$s['id']}' $sel>{$s['name']}</option>"; 
                        } ?>
                     </select>
                </form>

                <div id="individualViewProfile">
                <?php if(isset($_POST['view_staff_id']) && $_POST['view_staff_id']): 
                    $sid = (int)$_POST['view_staff_id'];
                    $staff = $staffListPay[$sid];
                    $logs = $conn->query("SELECT * FROM attendance_log WHERE staff_id='$sid' ORDER BY attendance_date DESC");
                    $payments = $conn->query("SELECT * FROM attendence_payments_log WHERE staff_id='$sid' ORDER BY date DESC");
                ?>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body text-center">
                                <div class="display-1 text-primary mb-2"><i class="bi bi-person-circle"></i></div>
                                <h4><?= $staff['name'] ?></h4>
                                <p class="text-muted">ID: <?= $staff['id'] ?></p>
                                <hr>
                                <div class="d-flex justify-content-between px-3">
                                    <span>Phone:</span> <strong><?= $staff['phone'] ?? $staff['mobile'] ?? $staff['contact'] ?? 'N/A' ?></strong>
                                </div>
                                <div class="d-flex justify-content-between px-3 mt-2">
                                    <span>Salary:</span> <strong>₹<?= $staff['salary'] ?> (<?= ucfirst($staff['salary_type']) ?>)</strong>
                                </div>
                                <div class="d-flex justify-content-between px-3 mt-2">
                                    <span>Attendance:</span> <strong><?= $staff['attendance'] ?> Days</strong>
                                </div>
                                <div class="d-flex justify-content-between px-3 mt-2">
                                    <span>Total Paid:</span> <strong>₹<?= $staff['payment_given'] ?></strong>
                                </div>
                                <div class="d-flex justify-content-between px-3 mt-2">
                                    <span>Pending:</span> <strong class="<?= (($staff['salary_type']=='payday'?($staff['attendance']*$staff['salary']):$staff['salary'])-$staff['payment_given'])>0?'text-danger':'text-success' ?>">₹<?= number_format((($staff['salary_type']=='payday'?($staff['attendance']*$staff['salary']):$staff['salary'])-$staff['payment_given']),2) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <h5 class="fw-bold">Attendance Log</h5>
                        <div style="max-height: 300px; overflow-y:auto;" class="border rounded p-2 bg-white mb-4">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Date</th><th>Time</th></tr></thead>
                                <tbody>
                                    <?php while($l = $logs->fetch_assoc()): ?>
                                    <tr><td><?= $l['attendance_date'] ?></td><td><?= $l['attendance_time'] ?></td></tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <h5 class="fw-bold mt-4">Payment History</h5>
                        <div style="max-height: 300px; overflow-y:auto;" class="border rounded p-2 bg-white">
                            <table class="table table-sm table-striped">
                                <thead><tr><th>Date</th><th>Amount</th><th>Receipt</th></tr></thead>
                                <tbody>
                                    <?php $modalIndex = 1000; while($p = $payments->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('d M Y, h:i A', strtotime($p['date'])) ?></td>
                                        <td class="fw-bold text-success">₹<?= number_format($p['amount'],2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info rounded-pill" data-bs-toggle="modal" data-bs-target="#receiptModal<?= $modalIndex ?>">
                                                <i class="bi bi-eye"></i> Receipt
                                            </button>
                                        </td>
                                    </tr>
                                    <!-- Modal for this payment -->
                                    <div class="modal fade" id="receiptModal<?= $modalIndex ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
                                        <div class="modal-dialog modal-dialog-centered modal-xl">
                                            <div class="modal-content border-0 bg-transparent">
                                                <div class="receipt-container shadow-lg" id="receiptContent<?= $modalIndex ?>">
                                                    <div class="text-center border-bottom pb-3 mb-3">
                                                        <h4 class="fw-bold ls-1">PAYMENT RECEIPT</h4>
                                                        <p class="text-muted small mb-0">Official Acknowledgement</p>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Receipt No:</div>
                                                        <div class="col-6 text-end fw-bold font-monospace"><?= $p['receipt_no'] ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Date:</div>
                                                        <div class="col-6 text-end fw-bold"><?= date('d M Y, h:i A', strtotime($p['date'])) ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Staff Member:</div>
                                                        <div class="col-6 text-end fw-bold text-primary"><?= $staff['name'] ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Phone:</div>
                                                        <div class="col-6 text-end fw-bold"><?= $staff['phone'] ?? $staff['mobile'] ?? $staff['contact'] ?? 'N/A' ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Salary:</div>
                                                        <div class="col-6 text-end fw-bold">₹<?= number_format($staff['salary'],2) ?> (<?= ucfirst($staff['salary_type']) ?>)</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Attendance:</div>
                                                        <div class="col-6 text-end fw-bold"><?= $staff['attendance'] ?> Days</div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Total Paid:</div>
                                                        <div class="col-6 text-end fw-bold text-success">₹<?= number_format($staff['payment_given'],2) ?></div>
                                                    </div>
                                                    <div class="row mb-2">
                                                        <div class="col-6 text-muted">Pending:</div>
                                                        <div class="col-6 text-end fw-bold <?= ((($staff['salary_type']=='payday'?($staff['attendance']*$staff['salary']):$staff['salary'])-$staff['payment_given'])>0?'text-danger':'text-success') ?>">₹<?= number_format((($staff['salary_type']=='payday'?($staff['attendance']*$staff['salary']):$staff['salary'])-$staff['payment_given']),2) ?></div>
                                                    </div>
                                                    <div class="p-3 bg-light rounded mt-3 mb-3 border">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <span class="fs-5">Amount Paid</span>
                                                            <span class="fs-4 fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></span>
                                                        </div>
                                                    </div>
                                                    <p class="small text-muted mb-3">Remarks: <?= $p['remarks'] ? htmlspecialchars($p['remarks']) : 'N/A' ?></p>
                                                    <div class="text-center">
                                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($qrData) ?>" class="img-thumbnail" alt="QR">
                                                        <div class="text-success small fw-bold mt-1"><i class="bi bi-patch-check-fill"></i> Verified Payment</div>
                                                    </div>
                                                    <div class="mt-4 d-grid gap-2">
                                                        <button type="button" class="btn btn-success rounded-pill" onclick="downloadReceiptImage('receiptContent<?= $modalIndex ?>')"><i class="bi bi-image"></i> Download Image</button>
                                                        <button type="button" class="btn btn-primary rounded-pill" onclick="downloadReceiptPDF('receiptContent<?= $modalIndex ?>')"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
                                                        <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php $modalIndex++; endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <div class="modal fade" id="qrScannerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content text-white bg-dark">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title"><i class="bi bi-qr-code-scan me-2"></i>Scan Receipt QR</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qr-reader" style="width:100%"></div>
                    <div id="qr-result" class="mt-3 p-3 bg-light text-dark rounded d-none"></div>
                    <button id="resetScannerBtn" class="btn btn-primary mt-3 d-none">Scan Another</button>
                </div>
            </div>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script>
    // 1. DataTables Init
    $(document).ready(function() {
        $('#summaryTable, #historyTable').DataTable({
            "language": { "search": "", "searchPlaceholder": "Search..." },
            "dom": '<"d-flex justify-content-between mb-3"f>t<"d-flex justify-content-between mt-3"p>'
        });
    });

    // 2. Staff Data for JS
    var staffData = <?php echo json_encode($staffListPay); ?>;

    // 3. Fill Salary Form on Select
    function fillSalaryForm(id) {
        var div = document.getElementById('salaryFormContent');
        var infoDiv = document.getElementById('salaryStaffInfo');
        if(id && staffData[id]) {
            div.style.display = 'block';
            setTimeout(() => div.style.opacity = '1', 10);
            document.getElementById('salTypeInput').value = staffData[id].salary_type;
            document.getElementById('salAmtInput').value = staffData[id].salary;
            // Show extra info
            var s = staffData[id];
            var type = s.salary_type || 'payday';
            var base = parseFloat(s.salary) || 0;
            var att = parseInt(s.attendance) || 0;
            var paid = parseFloat(s.payment_given) || 0;
            var totalDue = (type === 'payday') ? (att * base) : base;
            var pending = totalDue - paid;
            var color = pending > 0 ? 'text-danger' : 'text-success';
            var phone = s.phone || s.mobile || s.contact || 'N/A';
            infoDiv.innerHTML = `
                <div class=\"bg-light p-2 rounded border mb-2\">
                    <div class=\"d-flex justify-content-between\"><span>Name:</span> <strong>${s.name}</strong></div>
                    <div class=\"d-flex justify-content-between\"><span>Phone:</span> <strong>${phone}</strong></div>
                    <div class=\"d-flex justify-content-between\"><span>Pending:</span> <strong class=\"${color}\">₹${pending.toFixed(2)}</strong></div>
                    <div class=\"d-flex justify-content-between text-muted\"><span>Salary Type:</span> <span>${type}</span></div>
                    <div class=\"d-flex justify-content-between text-muted\"><span>Base Salary:</span> <span>₹${base.toFixed(2)}</span></div>
                    <div class=\"d-flex justify-content-between text-muted\"><span>Total Attendance:</span> <span>${att} days</span></div>
                    <div class=\"d-flex justify-content-between text-muted\"><span>Total Paid:</span> <span>₹${paid.toFixed(2)}</span></div>
                </div>
            `;
        } else {
            div.style.opacity = '0';
            setTimeout(() => div.style.display = 'none', 500);
            infoDiv.innerHTML = '';
        }
    }

    // 4. Show Payment Info on Select
    function showStaffInfoForPayment(id) {
        var infoDiv = document.getElementById('selectedStaffInfoPay');
        if(id && staffData[id]) {
            var s = staffData[id];
            var type = s.salary_type || 'payday';
            var base = parseFloat(s.salary) || 0;
            var att = parseInt(s.attendance) || 0;
            var paid = parseFloat(s.payment_given) || 0;
            var totalDue = (type === 'payday') ? (att * base) : base;
            var pending = totalDue - paid;
            var color = pending > 0 ? 'text-danger' : 'text-success';
            infoDiv.innerHTML = `
                <div class="bg-light p-2 rounded border mt-2">
                    <div class="d-flex justify-content-between"><span>Pending:</span> <strong class="${color}">₹${pending.toFixed(2)}</strong></div>
                    <div class="d-flex justify-content-between text-muted"><span>Salary Type:</span> <span>${type}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Base Salary:</span> <span>₹${base.toFixed(2)}</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Total Attendance:</span> <span>${att} days</span></div>
                    <div class="d-flex justify-content-between text-muted"><span>Total Paid:</span> <span>₹${paid.toFixed(2)}</span></div>
                </div>
            `;
        } else {
            infoDiv.innerHTML = '';
        }
    }

    // 5. Payment Confirmation & AJAX
    document.getElementById('paymentActionForm').addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('paymentStaffSelect').value;
        var amt = this.payment_amount.value;
        var remarks = this.remarks.value;
        var staff = staffData[id];
        if (!id || !amt) return;
        Swal.fire({
            title: 'Confirm Payment',
            html: `Pay <b>₹${amt}</b> to <b>${staff.name}</b>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Pay Now',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (result.isConfirmed) {
                // AJAX submit
                $.ajax({
                    url: '',
                    type: 'POST',
                    data: {
                        staff_id: id,
                        payment_amount: amt,
                        remarks: remarks,
                        make_payment: 1
                    },
                    success: function(res) {
                        Swal.fire('Success', 'Payment recorded!', 'success');
                        setTimeout(function(){ location.reload(); }, 1500);
                    },
                    error: function() {
                        Swal.fire('Error', 'Payment failed!', 'error');
                    }
                });
            }
        });
    });

    // 6. QR Scanner Logic
    let html5QrcodeScanner = null;
    const qrModal = document.getElementById('qrScannerModal');
    const qrReaderDiv = document.getElementById('qr-reader');

    qrModal.addEventListener('shown.bs.modal', function () {
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                qrReaderDiv.innerHTML = "";
                startScanner();
            });
        } else {
            qrReaderDiv.innerHTML = "";
            startScanner();
        }
        function startScanner() {
            html5QrcodeScanner = new Html5Qrcode("qr-reader");
            html5QrcodeScanner.start({ facingMode: "environment" }, { fps: 10, qrbox: 250 },
                (decodedText) => {
                    html5QrcodeScanner.pause();
                    let resultDiv = document.getElementById('qr-result');
                    resultDiv.classList.remove('d-none');
                    // Modern success card
                    let details = decodedText.replace(/\\n|\n/g, '<br>');
                    resultDiv.innerHTML = `
                        <div class='card border-success mb-3 shadow-sm'>
                            <div class='card-body text-center'>
                                <div class='mb-2'><span class='fs-2 text-success'><i class='bi bi-patch-check-fill'></i></span></div>
                                <h5 class='card-title text-success fw-bold'>Receipt Verified!</h5>
                                <p class='card-text text-dark mb-2'>${details}</p>
                            </div>
                        </div>
                        <button id='scanAnotherBtn' class='btn btn-primary mt-2'>Scan Another</button>
                    `;
                    document.getElementById('resetScannerBtn').classList.add('d-none');
                    // Scan Another button logic
                    setTimeout(() => {
                        const btn = document.getElementById('scanAnotherBtn');
                        if(btn) btn.onclick = function() {
                            resultDiv.classList.add('d-none');
                            if(html5QrcodeScanner) html5QrcodeScanner.resume();
                        };
                    }, 100);
                },
                (errorMessage) => { /* Ignore errors */ }
            ).catch(err => console.log(err));
        }
    });

    qrModal.addEventListener('hidden.bs.modal', function () {
        if(html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                qrReaderDiv.innerHTML = "";
            });
        } else {
            qrReaderDiv.innerHTML = "";
        }
        document.getElementById('qr-result').classList.add('d-none');
        document.getElementById('resetScannerBtn').classList.add('d-none');
    });

    document.getElementById('resetScannerBtn').addEventListener('click', () => {
        document.getElementById('qr-result').classList.add('d-none');
        document.getElementById('resetScannerBtn').classList.add('d-none');
        if(html5QrcodeScanner) html5QrcodeScanner.resume();
    });

    // 7. Change Individual View form to prevent page reload
    // <form method="post" id="viewStaffForm" class="mb-4" onsubmit="return false;">
    // ...existing code...
    // 2. Add JS to handle staff selection and update profile/log via AJAX
    $(document).ready(function() {
        $('#viewStaffForm select[name="view_staff_id"]').on('change', function() {
            var staffId = $(this).val();
            if(staffId) {
                $.ajax({
                    url: 'admin_staff_attendance.php',
                    type: 'POST',
                    data: { view_staff_id: staffId, ajax: 1 },
                    success: function(res) {
                        // Extract only the profile/log HTML from response
                        var html = $(res).find('.row').first();
                        $('#individualViewProfile').html(html.length ? html : '<div class="alert alert-warning">No data found.</div>');
                    }
                });
            } else {
                $('#individualViewProfile').html('');
            }
        });
    });

    // Fix for modal backdrop and scroll lock for dynamically generated modals
    $(document).on('hidden.bs.modal', '.modal', function () {
        if ($('.modal.show').length === 0) {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }
    });
    // Ensure modals are dismissible by click outside and Escape
    $(document).on('show.bs.modal', '.modal', function () {
        $(this).attr('data-bs-backdrop', 'true');
        $(this).attr('data-bs-keyboard', 'true');
    });

    // Fix for dynamically generated modals in Individual View
    $(document).on('click', '.modal', function(e) {
        if ($(e.target).hasClass('modal')) {
            $(this).modal('hide');
        }
    });
    // Remove scroll lock and backdrop after modal close
    $(document).on('hidden.bs.modal', '.modal', function () {
        setTimeout(function() {
            $('body').removeClass('modal-open');
            $('.modal-backdrop').remove();
        }, 100);
    });

    function downloadReceiptImage(receiptId) {
        var node = document.getElementById(receiptId);
        var btns = node.querySelectorAll('.btn');
        btns.forEach(btn => btn.style.display = 'none');
        var qrImg = node.querySelector('img[alt="QR"]');
        if (qrImg) qrImg.crossOrigin = 'anonymous';
        setTimeout(function() {
            html2canvas(node, {useCORS:true}).then(function(canvas) {
                var link = document.createElement('a');
                link.download = 'payment_receipt.png';
                link.href = canvas.toDataURL();
                link.click();
                btns.forEach(btn => btn.style.display = '');
            });
        }, 300);
    }
    function downloadReceiptPDF(receiptId) {
        var node = document.getElementById(receiptId);
        var btns = node.querySelectorAll('.btn');
        btns.forEach(btn => btn.style.display = 'none');
        var qrImg = node.querySelector('img[alt="QR"]');
        if (qrImg) qrImg.crossOrigin = 'anonymous';
        setTimeout(function() {
            html2canvas(node, {useCORS:true}).then(function(canvas) {
                // Use JPEG format and lower quality for smaller size
                var imgData = canvas.toDataURL('image/jpeg', 0.7); // 0.7 = 70% quality
                var pdf = new window.jspdf.jsPDF({orientation: 'portrait', unit: 'px', format: [canvas.width, canvas.height]});
                pdf.addImage(imgData, 'JPEG', 0, 0, canvas.width, canvas.height);
                pdf.save('payment_receipt.pdf');
                btns.forEach(btn => btn.style.display = '');
            });
        }, 300);
    }
</script>
</body>
</html>
<?php $conn->close(); ?>

<?php if (!empty($receiptModals)):
    foreach ($receiptModals as $modal):
        $row = $modal['row'];
        $s = $modal['s'];
        $modalIndex = $modal['modalIndex'];
        $qrData = "Receipt:{$row['receipt_no']}|Amt:{$row['amount']}|Staff:{$s['name']}|Status:Verified";
?>
<div class="modal fade" id="receiptModal<?= $modalIndex ?>" tabindex="-1" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 bg-transparent">
            <div class="receipt-container shadow-lg" id="receiptContent<?= $modalIndex ?>">
                <div class="text-center border-bottom pb-3 mb-3">
                    <h4 class="fw-bold ls-1">PAYMENT RECEIPT</h4>
                    <p class="text-muted small mb-0">Official Acknowledgement</p>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Receipt No:</div>
                    <div class="col-6 text-end fw-bold font-monospace"><?= $row['receipt_no'] ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Date:</div>
                    <div class="col-6 text-end fw-bold"><?= date('d M Y, h:i A', strtotime($row['date'])) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Staff Member:</div>
                    <div class="col-6 text-end fw-bold text-primary"><?= $s['name'] ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Phone:</div>
                    <div class="col-6 text-end fw-bold"><?= $s['phone'] ?? $s['mobile'] ?? $s['contact'] ?? 'N/A' ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Salary:</div>
                    <div class="col-6 text-end fw-bold">₹<?= number_format($s['salary'],2) ?> (<?= ucfirst($s['salary_type']) ?>)</div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Attendance:</div>
                    <div class="col-6 text-end fw-bold"><?= $s['attendance'] ?> Days</div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Total Paid:</div>
                    <div class="col-6 text-end fw-bold text-success">₹<?= number_format($s['payment_given'],2) ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-6 text-muted">Pending:</div>
                    <div class="col-6 text-end fw-bold <?= ((($s['salary_type']=='payday'?($s['attendance']*$s['salary']):$s['salary'])-$s['payment_given'])>0?'text-danger':'text-success') ?>">₹<?= number_format((($s['salary_type']=='payday'?($s['attendance']*$s['salary']):$s['salary'])-$s['payment_given']),2) ?></div>
                </div>
                <div class="p-3 bg-light rounded mt-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fs-5">Amount Paid</span>
                        <span class="fs-4 fw-bold text-success">₹<?= number_format($row['amount'], 2) ?></span>
                    </div>
                </div>
                <p class="small text-muted mb-3">Remarks: <?= $row['remarks'] ? htmlspecialchars($row['remarks']) : 'N/A' ?></p>
                <div class="text-center">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?= urlencode($qrData) ?>" class="img-thumbnail" alt="QR">
                    <div class="text-success small fw-bold mt-1"><i class="bi bi-patch-check-fill"></i> Verified Payment</div>
                </div>
                <div class="mt-4 d-grid gap-2">
                    <button type="button" class="btn btn-success rounded-pill" onclick="downloadReceiptImage('receiptContent<?= $modalIndex ?>')"><i class="bi bi-image"></i> Download Image</button>
                    <button type="button" class="btn btn-primary rounded-pill" onclick="downloadReceiptPDF('receiptContent<?= $modalIndex ?>')"><i class="bi bi-file-earmark-pdf"></i> Download PDF</button>
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; endif; ?>