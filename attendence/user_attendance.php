<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// attendence/user_attendance.php
session_start();
require_once '../admin/database.php';
include 'header.php';


// --- SECURITY & DATA FETCHING ---
$user_id = isset($_SESSION['attendance_id']) ? $_SESSION['attendance_id'] : 0;
if(!$user_id) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

// Fetch user info
$user_query = "SELECT * FROM attendence_users WHERE id='$user_id'";
$user_result = mysqli_query($conn, $user_query);
$user = mysqli_fetch_assoc($user_result);

// Fetch payment history
$payments_query = "SELECT * FROM attendence_payments_log WHERE staff_id='$user_id' ORDER BY date DESC";
$payments_result = mysqli_query($conn, $payments_query);

// Fetch attendance log
$att_log_query = "SELECT attendance_date, attendance_time FROM attendance_log WHERE staff_id='$user_id' ORDER BY attendance_date DESC";
$att_log_result = mysqli_query($conn, $att_log_query);

// --- CALCULATIONS ---
$salary_type = isset($user['salary_type']) ? $user['salary_type'] : 'payday';
$base_salary = isset($user['salary']) ? $user['salary'] : 0;
$attendance_count = isset($user['attendance']) ? $user['attendance'] : 0;

if($salary_type == 'payday') {
    $total_salary = $attendance_count * $base_salary;
} else {
    $total_salary = $base_salary;
}

$paid_amount = isset($user['payment_given']) ? $user['payment_given'] : 0;
$pending_amount = $total_salary - $paid_amount;
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --glass-bg: rgba(255, 255, 255, 0.95);
        --glass-border: 1px solid rgba(255, 255, 255, 0.18);
        --shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f0f2f5;
        background-image: radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
        background-attachment: fixed;
        min-height: 100vh;
    }

    .glass-card {
        background: var(--glass-bg);
        /* Removed backdrop-filter for modal brightness fix */
        border-radius: 20px;
        border: var(--glass-border);
        box-shadow: var(--shadow);
        transition: transform 0.3s ease;
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        border-left: 5px solid #667eea;
        transition: transform 0.2s;
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card.pending { border-left-color: #ff6b6b; }
    .stat-card.paid { border-left-color: #20bf6b; }
    .stat-card.att { border-left-color: #f7b731; }

    .nav-pills .nav-link {
        color: #555;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
    }
    .nav-pills .nav-link.active {
        background: var(--primary-gradient);
        color: white;
        box-shadow: 0 4px 15px rgba(118, 75, 162, 0.4);
    }

    .table thead th {
        background-color: #f8f9fa;
        color: #444;
        font-weight: 600;
        border-bottom: 2px solid #eee;
    }

    /* Receipt Styling */
    .receipt-box {
        border: 2px dashed #ccc;
        padding: 20px;
        background: #fff;
        position: relative;
    }
    .receipt-header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 10px; margin-bottom: 15px; }
    .receipt-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
    
    .avatar-circle {
        width: 60px; height: 60px;
        background: var(--primary-gradient);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: bold;
    }
</style>

<div class="container py-5">
    
    <div class="glass-card p-4 mb-4">
        <div class="d-flex align-items-center flex-wrap gap-3">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
            </div>
            <div class="flex-grow-1">
                <h2 class="mb-0 fw-bold text-dark">Welcome, <?php echo htmlspecialchars($user['name']); ?></h2>
                <div class="text-muted">
                    <span class="badge bg-secondary me-2">ID: <?php echo $user['id']; ?></span>
                    <span class="badge bg-info text-dark">Type: <?php echo ucfirst($user['salary_type']); ?></span>
                </div>
            </div>
            <div class="text-end">
                <div class="small text-muted">Current Date</div>
                <div class="fw-bold"><?php echo date('d M, Y'); ?></div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="stat-card">
                <div class="text-muted small text-uppercase fw-bold">Total Earnings</div>
                <h3 class="mb-0 fw-bold">₹<?php echo number_format($total_salary, 2); ?></h3>
                <small class="text-muted">Base: ₹<?php echo $base_salary; ?></small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card att">
                <div class="text-muted small text-uppercase fw-bold">Total Attendance</div>
                <h3 class="mb-0 fw-bold"><?php echo $attendance_count; ?> <span class="fs-6 text-muted">Days</span></h3>
                <small class="text-success"><i class="fas fa-check-circle"></i> Active</small>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card paid">
                <div class="text-muted small text-uppercase fw-bold">Amount Received</div>
                <h3 class="mb-0 fw-bold text-success">₹<?php echo number_format($paid_amount, 2); ?></h3>
                <div class="progress mt-2" style="height: 5px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($total_salary > 0) ? ($paid_amount/$total_salary)*100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="stat-card pending">
                <div class="text-muted small text-uppercase fw-bold">Pending Dues</div>
                <h3 class="mb-0 fw-bold <?php echo $pending_amount > 0 ? 'text-danger' : 'text-success'; ?>">
                    ₹<?php echo number_format($pending_amount, 2); ?>
                </h3>
                <small class="text-muted"><?php echo $pending_amount > 0 ? 'Payment Required' : 'All Clear'; ?></small>
            </div>
        </div>
    </div>

    <div class="glass-card p-4">
        <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="pills-attendance-tab" data-bs-toggle="pill" data-bs-target="#pills-attendance" type="button" role="tab"><i class="fas fa-calendar-check me-2"></i>Attendance Log</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="pills-payment-tab" data-bs-toggle="pill" data-bs-target="#pills-payment" type="button" role="tab"><i class="fas fa-file-invoice-dollar me-2"></i>Payment History</button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            <div class="tab-pane fade show active" id="pills-attendance" role="tabpanel">
                <div class="table-responsive">
                    <table id="attendanceTable" class="table table-hover align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time In</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            if($att_log_result && mysqli_num_rows($att_log_result)>0) {
                                while($row = mysqli_fetch_assoc($att_log_result)) { 
                                    $timeObj = new DateTime($row['attendance_time']);
                                    $formattedTime = $timeObj->format('g:i A');
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['attendance_date'])); ?></td>
                                <td class="fw-bold text-primary"><?php echo $formattedTime; ?></td>
                                <td><span class="badge bg-success bg-opacity-10 text-success px-3">Present</span></td>
                            </tr>
                            <?php } 
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="pills-payment" role="tabpanel">
                <div class="table-responsive">
                    <table id="paymentTable" class="table table-hover align-middle" style="width:100%">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Amount</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $modalIndex = 1; 
                            if($payments_result && mysqli_num_rows($payments_result)>0) {
                                while($row = mysqli_fetch_assoc($payments_result)) { 
                            ?>
                            <tr>
                                <td><?php echo date('d M Y', strtotime($row['date'])); ?></td>
                                <td><span class="font-monospace text-muted">#<?php echo $row['receipt_no']; ?></span></td>
                                <td class="fw-bold text-success">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo $row['remarks']; ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#receiptModal<?php echo $modalIndex; ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                </td>
                            </tr>

                            <div class="modal fade" id="receiptModal<?php echo $modalIndex; ?>" tabindex="-1" aria-labelledby="receiptModalLabel<?php echo $modalIndex; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content border-0 shadow-lg">
                                        <div class="modal-header bg-light">
                                            <h5 class="modal-title fw-bold" id="receiptModalLabel<?php echo $modalIndex; ?>"><i class="fas fa-receipt me-2"></i>Payment Receipt</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body p-4" id="printableArea<?php echo $modalIndex; ?>">
                                            <div class="receipt-box">
                                                <div class="receipt-header">
                                                    <h4 class="fw-bold mb-1">PAYMENT SLIP</h4>
                                                    <small class="text-muted">Official Receipt</small>
                                                </div>
                                                <div class="receipt-row">
                                                    <span class="text-muted">Receipt No:</span>
                                                    <span class="fw-bold font-monospace"><?php echo $row['receipt_no']; ?></span>
                                                </div>
                                                <div class="receipt-row">
                                                    <span class="text-muted">Date:</span>
                                                    <span class="fw-bold"><?php echo date('d M Y', strtotime($row['date'])); ?></span>
                                                </div>
                                                <hr class="my-2 text-muted">
                                                <div class="receipt-row">
                                                    <span class="text-muted">Employee:</span>
                                                    <span class="fw-bold"><?php echo $user['name']; ?></span>
                                                </div>
                                                <div class="receipt-row">
                                                    <span class="text-muted">Payment For:</span>
                                                    <span>Salary / Advance</span>
                                                </div>
                                                <div class="receipt-row mt-3">
                                                    <span class="fs-5 fw-bold">Total Paid:</span>
                                                    <span class="fs-5 fw-bold text-success">₹<?php echo number_format($row['amount'], 2); ?></span>
                                                </div>
                                                <div class="mt-3 small text-muted fst-italic border-top pt-2">
                                                    Remarks: <?php echo $row['remarks']; ?>
                                                </div>
                                                <div class="text-center mt-3">
                                                    <?php
                                                    $qrData = urlencode(
                                                        'Receipt No: '.$row['receipt_no'].'\n'.
                                                        'Date: '.date('d M Y', strtotime($row['date'])).'\n'.
                                                        'Employee: '.$user['name'].'\n'.
                                                        'Amount: ₹'.number_format($row['amount'],2).'\n'.
                                                        'Remarks: '.$row['remarks'].'\n'.
                                                        'Verified Payment'
                                                    );
                                                    ?>
                                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=<?php echo $qrData; ?>" alt="QR Code" style="border:1px solid #eee;" />
                                                    <div class="small text-muted mt-1">Scan to verify receipt</div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php $modalIndex++; } 
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    // Initialize DataTables with Modern Options
    $(document).ready(function() {
        $('#attendanceTable, #paymentTable').DataTable({
            "pageLength": 10,
            "language": {
                "search": "_INPUT_",
                "searchPlaceholder": "Search records..."
            },
            "dom": '<"d-flex justify-content-between align-items-center mb-3"f>t<"d-flex justify-content-between align-items-center mt-3"ip>'
        });
    });

    // Print Receipt Function
    function printDiv(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        var originalContents = document.body.innerHTML;
        document.body.innerHTML = printContents;
        window.print();
        document.body.innerHTML = originalContents;
        window.location.reload(); // Reload to restore events
    }
</script>

<?php include 'footer.php'; ?>