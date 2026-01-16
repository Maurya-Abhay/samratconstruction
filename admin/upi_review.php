<?php
// upi_payments_review.php
ob_start(); // Output buffering start (prevents header errors)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

include 'database.php'; // Use correct DB connection file

// --- 1. HANDLE LOGIC FIRST (Before any HTML output) ---

// Ensure tables exist
$conn->query("
    CREATE TABLE IF NOT EXISTS upi_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contact_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        note VARCHAR(140) NULL,
        upi_vpa VARCHAR(100) NOT NULL,
        upi_payee VARCHAR(120) NOT NULL,
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        proof_path VARCHAR(255) NULL,
        receipt_payment_id INT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        reviewed_by VARCHAR(120) NULL,
        INDEX idx_contact_status (contact_id, status),
        INDEX idx_created (created_at),
        CONSTRAINT fk_up_contact FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Check and add receipt_payment_id column if missing
try {
    $conn->query("SELECT receipt_payment_id FROM upi_payments LIMIT 1");
} catch (Exception $e) {
    $conn->query("ALTER TABLE upi_payments ADD COLUMN receipt_payment_id INT NULL");
}

$feedback = null;

// Handle Approval / Rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = (int)($_POST['pid'] ?? 0);
    $action = $_POST['action'] ?? '';
    $user_email = $_SESSION['email'] ?? 'admin';

    if ($pid > 0 && in_array($action, ['approve', 'reject'], true)) {
        $row = $conn->query("SELECT * FROM upi_payments WHERE id=$pid")->fetch_assoc();
        
        if ($row) {
            if ($row['status'] !== 'Pending') {
                header("Location: upi_review.php?status=Pending&msg_type=warning&msg=" . urlencode('Record already reviewed.'));
                exit;
            } else if ($action === 'approve') {
                // --- APPROVE LOGIC ---
                $conn->begin_transaction();
                try {
                    // 1. Mark Approved
                    $stmt = $conn->prepare("UPDATE upi_payments SET status='Approved', reviewed_at=NOW(), reviewed_by=? WHERE id=?");
                    $stmt->bind_param('si', $user_email, $pid);
                    $stmt->execute();
                    $stmt->close();

                    // 2. Add to contact_payments
                    $stmt = $conn->prepare("INSERT INTO contact_payments (contact_id, amount, payment_date, method, notes) VALUES (?,?,CURDATE(),'UPI',?)");
                    $notes = 'UPI proof approved (ID #'.$pid.') - VPA: ' . $row['upi_vpa'];
                    $stmt->bind_param('ids', $row['contact_id'], $row['amount'], $notes);
                    $stmt->execute();
                    $cp_id = $stmt->insert_id;
                    $stmt->close();

                    // 3. Update Contact Balance
                    $conn->query("UPDATE contacts SET amount_paid = amount_paid + ".$row['amount']." WHERE id=".(int)$row['contact_id']);

                    // 4. Link Receipt
                    $st2 = $conn->prepare("UPDATE upi_payments SET receipt_payment_id=? WHERE id=?");
                    $st2->bind_param('ii', $cp_id, $pid);
                    $st2->execute();
                    $st2->close();

                    $conn->commit();
                    header("Location: upi_review.php?status=Pending&msg_type=success&msg=" . urlencode('Approved successfully.'));
                    exit;

                } catch (Throwable $e) {
                    $conn->rollback();
                    header("Location: upi_review.php?status=Pending&msg_type=error&msg=" . urlencode('Error: '.$e->getMessage()));
                    exit;
                }
            } else {
                // --- REJECT LOGIC ---
                $stmt = $conn->prepare("UPDATE upi_payments SET status='Rejected', reviewed_at=NOW(), reviewed_by=? WHERE id=?");
                $stmt->bind_param('si', $user_email, $pid);
                $stmt->execute();
                $stmt->close();
                header("Location: upi_review.php?status=Pending&msg_type=warning&msg=" . urlencode('Payment Rejected.'));
                exit;
            }
        }
    }
}

// Prepare Data for View
$status_filter = $_GET['status'] ?? 'Pending';
$where_clause = $status_filter === 'All' ? '' : "WHERE u.status='" . $conn->real_escape_string($status_filter) . "'";

$sql = "SELECT u.*, c.name AS contact_name 
        FROM upi_payments u 
        JOIN contacts c ON u.contact_id=c.id 
        $where_clause 
        ORDER BY u.created_at DESC LIMIT 200";
$rows = $conn->query($sql);

// Feedback check
if (isset($_GET['msg_type']) && isset($_GET['msg'])) {
    $feedback = ['type' => $_GET['msg_type'], 'text' => $_GET['msg']];
}

// --- 2. START HTML OUTPUT ---
include 'topheader.php';
include 'sidenavbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPI Payments Review</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .card-modern { border: none; border-radius: 12px; box-shadow: 0 6px 28px rgba(45, 62, 110, 0.12); background: #fff; }
        .table thead th { background-color: #f8f9fc; color: #858796; font-weight: 600; text-transform: uppercase; font-size: 0.8rem; border-bottom: 2px solid #e3e6f0; }
        .badge-status { min-width: 80px; }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-qr-code text-success me-2"></i>UPI Proofs Review</h3>
        <a href="dash.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Dashboard</a>
    </div>

    <?php if ($feedback): ?>
        <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                icon: "<?= $feedback['type'] === 'success' ? 'success' : ($feedback['type'] === 'warning' ? 'warning' : 'error') ?>",
                title: "<?= ucfirst($feedback['type']) ?>",
                text: "<?= htmlspecialchars($feedback['text']) ?>",
                confirmButtonText: "OK"
            }).then(function(){
                // Remove feedback from URL after showing
                if (window.location.search.match(/msg_type|msg/)) {
                    var url = window.location.origin + window.location.pathname + window.location.search.replace(/([&?])(msg_type|msg)=[^&]*(&)?/g, function(m, p1, p2, p3){ return p3 ? p1 : ''; }).replace(/([&?])$/, '');
                    // Remove any trailing ? or &
                    url = url.replace(/[?&]$/, '');
                    window.location.replace(url);
                }
            });
        });
        </script>
    <?php endif; ?>

    <div class="card card-modern shadow-sm mb-4 p-3">
        <form class="row g-3 align-items-center" method="GET">
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">Filter by Status</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach (['Pending', 'Approved', 'Rejected', 'All'] as $s): ?>
                        <option value="<?= $s ?>" <?= $status_filter === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto mt-auto">
                <p class="text-muted small mb-0">Viewing: <strong><?= htmlspecialchars($status_filter) ?></strong></p>
            </div>
        </form>
    </div>

    <div class="card card-modern p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="sticky-top">
                    <tr>
                        <th width="12%">Date</th>
                        <th width="15%">Contact</th>
                        <th width="10%">Amount</th>
                        <th width="15%">UPI Note</th>
                        <th width="10%">Proof</th>
                        <th width="10%">Status</th>
                        <th width="28%">Action / Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($rows && $rows->num_rows): while ($r = $rows->fetch_assoc()): ?>
                        <tr>
                            <td class="small text-muted text-nowrap">
                                <?= date('d M Y', strtotime($r['created_at'])) ?><br>
                                <small><?= date('h:i A', strtotime($r['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($r['contact_name']) ?></div> 
                                <small class="text-muted">ID: #<?= (int)$r['contact_id'] ?></small>
                                <div class="small text-primary mt-1"><i class="bi bi-phone"></i> <?= htmlspecialchars($r['upi_vpa']) ?></div>
                            </td>
                            <td><span class="fw-bold text-success">₹<?= number_format($r['amount'], 2) ?></span></td>
                            <td><small><?= htmlspecialchars($r['note'] ?? '-') ?></small></td>
                            
                            <td>
                                <?php if (!empty($r['proof_path'])): 
                                    $proof_path_clean = ltrim($r['proof_path'], '/');
                                    $purl = (strpos($proof_path_clean, 'uploads/') === 0) ? $proof_path_clean : 'uploads/' . $proof_path_clean;
                                ?>
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#proofModal" data-src="<?= htmlspecialchars($purl, ENT_QUOTES) ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">No File</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <span class="badge badge-status bg-<?= $r['status'] === 'Approved' ? 'success' : ($r['status'] === 'Rejected' ? 'danger' : 'warning') ?>">
                                    <?= htmlspecialchars($r['status']) ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <div class="d-flex gap-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="pid" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="button" class="btn btn-success btn-sm approve-btn"><i class="bi bi-check2"></i> Approve</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="pid" value="<?= (int)$r['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="button" class="btn btn-outline-danger btn-sm reject-btn"><i class="bi bi-x"></i> Reject</button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <div class="small text-muted">
                                        By: <?= htmlspecialchars($r['reviewed_by'] ?? 'Admin') ?><br>
                                        On: <?= $r['reviewed_at'] ? date('d M, h:i A', strtotime($r['reviewed_at'])) : '-' ?>
                                    </div>
                                    <?php if ($r['status'] === 'Approved' && !empty($r['receipt_payment_id'])): ?>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none mt-1" 
                                                data-bs-toggle="modal" data-bs-target="#receiptModal" 
                                                data-href="payment_receipt.php?id=<?= (int)$r['receipt_payment_id'] ?>">
                                            <i class="bi bi-receipt"></i> Show Receipt
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="7" class="text-center text-muted py-5">No records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<div class="modal fade" id="proofModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-image"></i> Proof Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center bg-dark p-0">
                <img id="proofImg" src="" alt="Proof" style="max-width: 100%; max-height: 80vh; margin: auto;">
            </div>
            <div class="modal-footer">
                <a id="proofDownload" href="#" class="btn btn-primary" download><i class="bi bi-download"></i> Download</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content card-modern h-100">
            <div class="modal-header bg-light">
                <h5 class="modal-title"><i class="bi bi-receipt"></i> Payment Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height: 75vh;">
                <iframe id="receiptFrame" src="" style="width:100%; height:100%; border:0;"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="document.getElementById('receiptFrame').contentWindow.print()">Print</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. Proof Modal Logic
    var proofModal = document.getElementById('proofModal');
    if (proofModal) {
        proofModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var src = btn.getAttribute('data-src');
            document.getElementById('proofImg').src = src;
            document.getElementById('proofDownload').href = src;
        });
        proofModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('proofImg').src = '';
        });
    }

    // 2. Receipt Modal Logic
    var receiptModal = document.getElementById('receiptModal');
    if (receiptModal) {
        receiptModal.addEventListener('show.bs.modal', function(e) {
            var btn = e.relatedTarget;
            var href = btn.getAttribute('data-href');
            document.getElementById('receiptFrame').src = href;
        });
        receiptModal.addEventListener('hidden.bs.modal', function() {
            document.getElementById('receiptFrame').src = 'about:blank';
        });
    }

    // 3. SweetAlert Actions
    document.body.addEventListener('click', function(e) {
        if (e.target.closest('.approve-btn')) {
            var btn = e.target.closest('.approve-btn');
            Swal.fire({
                title: 'Approve Payment?',
                text: "Balance will be updated.",
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#198754',
                confirmButtonText: 'Yes, Approve'
            }).then((result) => {
                if (result.isConfirmed) btn.closest('form').submit();
            });
        }
        if (e.target.closest('.reject-btn')) {
            var btn = e.target.closest('.reject-btn');
            Swal.fire({
                title: 'Reject Payment?',
                text: "User will need to resubmit.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Reject'
            }).then((result) => {
                if (result.isConfirmed) btn.closest('form').submit();
            });
        }
    });

});
</script>

</body>
</html>