<?php
// upi_payment.php - Client UPI Payment Interface

session_start();

require_once '../admin/database.php';
// This file must define $UPI_VPA, $UPI_PAYEE, $UPI_MOBILE (optional), and $UPI_QR_PATH (optional)
require_once '../admin/upi_config.php'; 

// --- Security Check ---
if (!isset($_SESSION['contact_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$contact_id = (int)$_SESSION['contact_id'];

// --- Database Table Check/Creation ---
// Create table to log UPI payment attempts and proofs
$conn->query(
    "CREATE TABLE IF NOT EXISTS upi_payments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        contact_id INT NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        note VARCHAR(140) NULL,
        upi_vpa VARCHAR(100) NOT NULL,
        upi_payee VARCHAR(120) NOT NULL,
        status ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
        proof_path VARCHAR(255) NULL,
        receipt_payment_id INT NULL, -- Link to the final payment record (if approved)
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL DEFAULT NULL,
        reviewed_by VARCHAR(120) NULL,
        INDEX idx_contact_status (contact_id, status),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$errors = [];
$success = '';

// --- Fetch Contact Data and Due Amount ---
$stmt = $conn->prepare('SELECT name, contract_amount, amount_paid FROM contacts WHERE id=?');
$stmt->bind_param('i', $contact_id);
$stmt->execute();
$c = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$c) { die('Contact not found'); }

$due = (float)$c['contract_amount'] - (float)$c['amount_paid'];
if ($due < 0) $due = 0.0; // Prevent negative due amount display

// --- Handle Proof Upload and Payment Record Creation ---
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['upload_proof'])){
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (!function_exists('csrf_token')) {
        function csrf_token() {
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf_token'];
        }
    }

    // Hidden fields are used to sync the amount/note entered by the user before they clicked 'Generate QR'
    $amountHidden = (float)($_POST['amount_hidden'] ?? 0);
    $noteHidden = trim($_POST['note_hidden'] ?? '');

    if (!isset($_FILES['proof']) || $_FILES['proof']['error'] !== UPLOAD_ERR_OK){
        $errors[] = 'Please upload a screenshot/image.';
    } else {
        $allowed = ['image/jpeg','image/png','image/webp'];
        if (!in_array($_FILES['proof']['type'], $allowed)){
            $errors[] = 'Only JPG/PNG/WEBP files are allowed.';
        } elseif ($_FILES['proof']['size'] > 2*1024*1024) {
            $errors[] = 'File size must be less than 2MB.';
        } else {
            $dir = '../admin/uploads/upi_proofs/';
            if (!is_dir($dir)) { mkdir($dir, 0755, true); }
            $ext = pathinfo($_FILES['proof']['name'], PATHINFO_EXTENSION);
            $fname = 'upi_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
            $path = $dir.$fname;
            if (move_uploaded_file($_FILES['proof']['tmp_name'], $path)){
                $relPath = 'uploads/upi_proofs/'.$fname;
                
                // We create a new payment record here when proof is uploaded
                if ($amountHidden <= 0) {
                    $errors[] = 'Amount must be greater than 0.';
                } elseif ($amountHidden > 999999) {
                    $errors[] = 'Amount too large.';
                } else {
                    $stmt = $conn->prepare('INSERT INTO upi_payments (contact_id, amount, note, upi_vpa, upi_payee, proof_path) VALUES (?,?,?,?,?,?)');
                    $stmt->bind_param('idssss', $contact_id, $amountHidden, $noteHidden, $UPI_VPA, $UPI_PAYEE, $relPath);
                    
                    if ($stmt->execute()) {
                        $success = 'Proof uploaded successfully. Admin will verify shortly.';
                    } else {
                        // Cleanup uploaded file on DB error
                        @unlink($path); 
                        $errors[] = 'Failed to create payment record in database.';
                    }
                    $stmt->close();
                }

            } else {
                $errors[] = 'Failed to save uploaded file.';
            }
        }
    }
}

// --- List Recent Payments ---
$payments = $conn->query("SELECT * FROM upi_payments WHERE contact_id=$contact_id ORDER BY created_at DESC LIMIT 10");

// --- Helper Function: Build UPI URL ---
function buildUpiUrl($vpa, $payee, $amount, $note){
    $params = [
        'pa'=>$vpa,
        'pn'=>$payee,
        'am'=>number_format((float)$amount, 2, '.', ''),
        'cu'=>'INR'
    ];
    // Transaction Note (tn) is optional
    if ($note!=='') $params['tn'] = $note;
    
    // 'upi://pay' is the URI scheme for opening UPI apps
    return 'upi://pay?'.http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UPI Payment | Client Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" href="../admin/assets/smrticon.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        .upi-info { background-color: #f0f7ff; border-left: 5px solid #0d6efd; }
    </style>
</head>
<body class="bg-light">

    <?php $contact_show_back_btn = true; $contact_back_href = 'dashboard.php'; include __DIR__ . '/header.php'; ?>

    <div class="container py-4" style="margin-top: 70px;">
        <h2 class="mb-4 text-primary fw-bold"><i class="bi bi-wallet2 me-2"></i> UPI Payment Gateway</h2>
        
        <div class="card shadow-lg mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">1. Payment Details</h5>
                    <div>
                        <?php if (!empty($UPI_QR_PATH)): ?>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#adminQRModal">
                                <i class="bi bi-qr-code"></i> View Admin QR
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($UPI_MOBILE)): ?>
                            <a class="btn btn-sm btn-outline-success" href="tel:<?= htmlspecialchars($UPI_MOBILE) ?>">
                                <i class="bi bi-telephone"></i> Call Payee
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                
                <div class="alert upi-info p-3 mb-4" role="alert">
                    <div class="row">
                        <div class="col-md-4"><strong>Payee Name:</strong> <?= htmlspecialchars($UPI_PAYEE) ?></div>
                        <div class="col-md-4"><strong>UPI VPA:</strong> <?= htmlspecialchars($UPI_VPA) ?></div>
                        <?php if (!empty($UPI_MOBILE)): ?><div class="col-md-4"><strong>Payee Mobile:</strong> <?= htmlspecialchars($UPI_MOBILE) ?></div><?php endif; ?>
                    </div>
                </div>

                <div class="alert alert-warning" role="alert">
                    <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle-fill me-1"></i> Important Instructions (महत्वपूर्ण नोट्स)</div>
                    <ol class="mb-0 ps-3 small">
                        <li class="mb-1">Scan the QR generated below using your UPI app. The name displayed in your UPI app **must match** the payee name: **"<?= htmlspecialchars($UPI_PAYEE) ?>"**. Proceed only if the name matches.</li>
                        <li class="mb-1">If QR scanning fails, you can pay using the VPA or the mobile number<?php if (!empty($UPI_MOBILE)): ?>: **<?= htmlspecialchars($UPI_MOBILE) ?>**<?php endif; ?>. Again, ensure the payee name **"<?= htmlspecialchars($UPI_PAYEE) ?>"** appears in your app.</li>
                        <li>**After successful payment**, take a screenshot of the confirmation and upload it using the form below. The admin will verify the payment before it is officially approved and a receipt is made available.</li>
                    </ol>
                </div>

                <?php foreach($errors as $e): ?>
                    <div class="alert alert-danger"><i class="bi bi-x-circle-fill me-2"></i><?= htmlspecialchars($e) ?></div>
                <?php endforeach; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <form method="POST" class="row g-3 border-bottom pb-3 mb-3" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Total Due Amount (₹)</label>
                        <input type="text" class="form-control" value="₹<?= number_format($due, 2) ?>" readonly>
                    </div>
                    <div class="col-md-4">
                        <label for="payAmount" class="form-label fw-semibold">Amount to Pay (₹)<span class="text-danger">*</span></label>
                        <input type="number" id="payAmount" name="pay_amount_input" min="1" step="0.01" class="form-control" placeholder="Enter amount" required value="<?= $due > 0 ? number_format($due, 2, '.', '') : '' ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="payNote" class="form-label fw-semibold">Note / Remarks (optional)</label>
                        <input type="text" id="payNote" name="pay_note_input" maxlength="80" class="form-control" placeholder="e.g., Part payment, Invoice #123">
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary" id="btnGenerate"><i class="bi bi-qr-code-scan me-1"></i> Generate QR / Payment Link</button>
                    </div>
                </form>

                <div id="qrSection" class="row g-4 align-items-center d-none border-bottom pb-4 mb-4">
                    <h5 class="fw-bold"><i class="bi bi-cash-stack me-1"></i> 2. Pay Now</h5>
                    <div class="col-md-4 text-center">
                        <div id="qrBox" class="border border-primary rounded p-3 d-inline-block" style="background:#fff;"></div>
                        <p class="mt-2 small text-muted">Scan this QR code with any UPI app.</p>
                    </div>
                    <div class="col-md-8">
                        <p class="lead">**Payment Summary**</p>
                        <ul class="list-unstyled">
                            <li class="mb-2"><strong>Payee:</strong> <?= htmlspecialchars($UPI_PAYEE) ?></li>
                            <li class="mb-2"><strong>VPA:</strong> <?= htmlspecialchars($UPI_VPA) ?></li>
                            <li class="mb-2"><strong>Amount:</strong> <span id="amtShow" class="fw-bold text-success">₹—</span></li>
                            <li class="mb-3 d-none" id="noteRow"><strong>Note:</strong> <span id="noteShow" class="fst-italic"></span></li>
                        </ul>
                        <a id="upiLink" class="btn btn-lg btn-success" href="#" target="_blank"><i class="bi bi-arrow-right-circle-fill me-1"></i> Pay in UPI App</a>
                    </div>
                </div>

                <h5 class="fw-bold mt-4"><i class="bi bi-upload me-1"></i> 3. Upload Payment Proof</h5>
                <p class="text-muted">Once payment is successful, upload the transaction screenshot below.</p>
                <form id="proofForm" method="POST" enctype="multipart/form-data" class="row g-3">
                    <input type="hidden" name="amount_hidden" id="amountHidden" value="">
                    <input type="hidden" name="note_hidden" id="noteHidden" value="">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Select Screenshot (JPG/PNG/WEBP)<span class="text-danger">*</span></label>
                        <input type="file" name="proof" accept="image/jpeg,image/png,image/webp" class="form-control" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end justify-content-end">
                        <button class="btn btn-lg btn-outline-primary" name="upload_proof"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Upload Proof for Verification</button>
                    </div>
                </form>

                <hr class="mt-5">
                
                <h5 class="mt-4 mb-3 fw-bold"><i class="bi bi-list-task me-1"></i> Your Recent UPI Payments (Last 10)</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="white-space:nowrap;">Date/Time</th>
                                <th>Amount</th>
                                <th>Note</th>
                                <th>Status</th>
                                <th>Receipt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($payments && $payments->num_rows): ?>
                                <?php while($p=$payments->fetch_assoc()): ?>
                                    <tr>
                                        <td style="white-space:nowrap;" class="small text-muted"><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
                                        <td class="fw-bold">₹<?= number_format($p['amount'], 2) ?></td>
                                        <td><?= htmlspecialchars($p['note'] ?? '—') ?></td>
                                        <td>
                                            <?php 
                                            $status = $p['status'];
                                            $badge = $status === 'Approved' ? 'success' : ($status === 'Rejected' ? 'danger' : 'warning');
                                            ?>
                                            <span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                        </td>
                                        <!-- Proof and QR columns removed for cleaner table -->
                                        <td>
                                            <?php if (($p['status'] ?? '')==='Approved' && !empty($p['receipt_payment_id'])): ?>
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#receiptModal" data-id="<?= (int)$p['id'] ?>"><i class="bi bi-receipt"></i> Receipt</button>
                                            <?php else: ?>
                                                —
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center text-muted py-3">No UPI payments history found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="modal fade" id="adminQRModal" tabindex="-1" aria-labelledby="adminQRModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="adminQRModalLabel"><i class="bi bi-qr-code"></i> Admin Static QR</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <?php if (!empty($UPI_QR_PATH)): ?>
                                    <img src="../admin/<?= htmlspecialchars($UPI_QR_PATH) ?>" alt="Admin QR" class="img-fluid" style="max-height:360px; border-radius:12px; border:1px solid #eee;">
                                <?php else: ?>
                                    <div class="text-muted">Static QR not uploaded by admin.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="proofModal" tabindex="-1" aria-labelledby="proofModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="proofModalLabel"><i class="bi bi-image"></i> Payment Proof</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="proofImg" src="" alt="Proof" class="img-fluid" style="max-height:70vh;border-radius:8px;border:1px solid #eee;">
                            </div>
                            <div class="modal-footer">
                                <a id="proofDownload" href="#" class="btn btn-outline-primary" download>
                                    <i class="bi bi-download"></i> Download
                                </a>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="receiptModal" tabindex="-1" aria-labelledby="receiptModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content rounded-4 shadow-lg border-0">
                            <div class="modal-header bg-primary text-white rounded-top-4">
                                <h5 class="modal-title d-flex align-items-center gap-2" id="receiptModalLabel">
                                    <i class="bi bi-receipt fs-3"></i>
                                    <span class="fw-bold">Payment Receipt</span>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body bg-light" style="height:75vh;">
                                <div class="d-flex justify-content-center align-items-center h-100">
                                    <div class="bg-white rounded-4 shadow p-3 w-100" style="max-width:900px; min-height:60vh; border:1px solid #e0e8ff;">
                                        <div id="receiptContent"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-white rounded-bottom-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-primary me-2" id="printReceiptBtn"><i class="bi bi-printer"></i> Print / Save PDF</button>
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <script>
                                // Pass payment data to JS for modal rendering
                                window.upiPaymentsData = <?php
                                    $jsPayments = [];
                                    if ($payments && $payments->num_rows) {
                                        $payments->data_seek(0);
                                        while($p = $payments->fetch_assoc()) {
                                            if ($p['status'] === 'Approved' && !empty($p['receipt_payment_id'])) {
                                                $jsPayments[] = [
                                                    'id' => $p['id'],
                                                    'created_at' => date('d M Y, h:i A', strtotime($p['created_at'])),
                                                    'admin_name' => $p['upi_payee'],
                                                    'admin_vpa' => $p['upi_vpa'],
                                                    'amount' => $p['amount'],
                                                    'note' => $p['note'],
                                                    'status' => $p['status'],
                                                    'customer_name' => isset($c['name']) ? $c['name'] : '',
                                                    'customer_phone' => isset($c['phone']) ? $c['phone'] : '',
                                                    'proof_path' => !empty($p['proof_path']) ? '../admin/' . ltrim($p['proof_path'], '/') : '',
                                                    'reviewed_by' => $p['reviewed_by'],
                                                    'reviewed_at' => $p['reviewed_at'] ? date('d M Y, h:i A', strtotime($p['reviewed_at'])) : '',
                                                ];
                                            }
                                        }
                                    }
                                    echo json_encode($jsPayments);
                                ?>;
                                // Modern receipt modal rendering
                                (function(){
                                    var rm = document.getElementById('receiptModal');
                                    if (!rm) return;
                                    rm.addEventListener('show.bs.modal', function(ev){
                                        var btn = ev.relatedTarget; if (!btn) return;
                                        var pid = btn.getAttribute('data-id');
                                        var payments = window.upiPaymentsData || [];
                                        var p = payments.find(x => x.id == pid);
                                        var rc = document.getElementById('receiptContent');
                                        if (!p || !rc) {
                                            rc.innerHTML = '<div class="text-danger">Receipt data not found.</div>';
                                            return;
                                        }
                                        rc.innerHTML = `
                                            <div class="receipt-modern-card p-0" style="background: #fff; border-radius: 20px; box-shadow: 0 8px 32px rgba(0,0,0,0.10); max-width: 540px; margin: 0 auto; overflow: hidden;">
                                                <div style="background: linear-gradient(90deg, #3f51b5 0%, #2196f3 100%); height: 60px; position: relative;">
                                                    <svg viewBox="0 0 540 60" width="100%" height="60" style="position:absolute;bottom:0;left:0;"><path d="M0,60 Q270,0 540,60 Z" fill="#f0f4ff"/></svg>
                                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center ps-4">
                                                        <i class="bi bi-receipt text-white" style="font-size:2.2rem;"></i>
                                                        <span class="ms-3 text-white fw-bold fs-4">Payment Receipt</span>
                                                    </div>
                                                </div>
                                                <div class="p-4">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <div class="text-muted small"><i class="bi bi-hash me-1"></i>Receipt ID: <span class="fw-bold">#${p.id}</span></div>
                                                        <div class="text-muted small"><i class="bi bi-calendar me-1"></i>${p.created_at}</div>
                                                    </div>
                                                    <div class="row mb-3">
                                                        <div class="col-6">
                                                            <div class="fw-semibold text-primary"><i class="bi bi-person-badge me-1"></i>Admin</div>
                                                            <div class="fw-bold">${p.admin_name}</div>
                                                            <div class="small text-muted">Phone: <span class="text-primary">${p.admin_phone || '—'}</span></div>
                                                        </div>
                                                        <div class="col-6 text-end">
                                                            <div class="fw-semibold text-success"><i class="bi bi-person-circle me-1"></i>Customer</div>
                                                            <div class="fw-bold">${p.customer_name || '—'}</div>
                                                            <div class="small text-muted">Phone: <span class="text-success">${p.customer_phone || '—'}</span></div>
                                                        </div>
                                                    </div>
                                                    <div class="mb-3">
                                                        <table class="table table-borderless mb-0">
                                                            <tbody>
                                                                <tr>
                                                                    <td class="fw-semibold"><i class="bi bi-currency-rupee me-1"></i>Amount</td>
                                                                    <td class="text-end fw-bold fs-5 text-success">₹${parseFloat(p.amount).toFixed(2)}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Note</td>
                                                                    <td class="text-end">${p.note || '—'}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold"><i class="bi bi-shield-check me-1"></i>Status</td>
                                                                    <td class="text-end"><span class="badge bg-success px-4 py-2 fs-6">PAID</span></td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="fw-semibold"><i class="bi bi-calendar-check me-1"></i>Review Date</td>
                                                                    <td class="text-end">${p.reviewed_at || '—'}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="text-center mt-4">
                                                        <span class="text-muted small">This is a system-generated receipt for your UPI payment.</span>
                                                    </div>
                                                </div>
                                            </div>
                                        `;
                                    });
                                    document.getElementById('printReceiptBtn').onclick = function(){
                                        var card = document.querySelector('.receipt-modern-card');
                                        if (!card) return;
                                        html2canvas(card, {backgroundColor: null, scale: 2}).then(function(canvas) {
                                            var link = document.createElement('a');
                                            link.download = 'UPI_Receipt_'+Date.now()+'.png';
                                            link.href = canvas.toDataURL('image/png');
                                            link.click();
                                        });
                                    };
                                    rm.addEventListener('hidden.bs.modal', function(){
                                        var rc = document.getElementById('receiptContent');
                                        if (rc) rc.innerHTML = '';
                                    });
                                })();
                                </script>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="rowQRModal" tabindex="-1" aria-labelledby="rowQRModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="rowQRModalLabel"><i class="bi bi-qr-code"></i> Payment QR for Record</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body text-center">
                                <div id="rowQRBox" class="border rounded p-3 d-inline-block" style="background:#fff;"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            </div>
                        </div>
                    </div>
                </div>

            </div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>

    <script>
        // Client-side JS logic for QR generation and modal wiring

        // Build UPI URL (Duplicate of PHP function for client-side use)
        function buildUpiUrl(vpa, payee, amount, note){
            const p = new URLSearchParams({ 
                pa:vpa, 
                pn:payee, 
                am:amount.toFixed(2), 
                cu:'INR' 
            });
            if (note) p.set('tn', note);
            return 'upi://pay?' + p.toString();
        }

        // --- Event Listener for QR Generation ---
        document.getElementById('btnGenerate')?.addEventListener('click', function(){
            var amtInput = document.getElementById('payAmount');
            var noteInput = document.getElementById('payNote');
            
            var amt = parseFloat(amtInput.value || '0');
            var note = (noteInput.value || '').trim();

            if (!(amt > 0)) { alert('Please enter a valid amount.'); amtInput.focus(); return; }
            if (amt > 999999) { alert('Amount too large.'); return; }
            
            var vpa = <?= json_encode($UPI_VPA) ?>;
            var payee = <?= json_encode($UPI_PAYEE) ?>;
            var url = buildUpiUrl(vpa, payee, amt, note);

            // 1. Generate QR Code
            var qrEl = document.getElementById('qrBox');
            if (qrEl && window.QRCode){ 
                qrEl.innerHTML=''; 
                new QRCode(qrEl, { 
                    text:url, 
                    width: 220, 
                    height: 220, 
                    colorDark:'#0d6efd', // Use primary color for dark contrast
                    colorLight:'#fff', 
                    correctLevel: QRCode.CorrectLevel.H 
                }); 
            }

            // 2. Update Payment Link and Display Info
            document.getElementById('upiLink').href = url;
            document.getElementById('amtShow').textContent = '₹' + amt.toFixed(2);
            
            var noteRow = document.getElementById('noteRow');
            if (note){ 
                document.getElementById('noteShow').textContent = note; 
                noteRow.classList.remove('d-none'); 
            } else { 
                noteRow.classList.add('d-none'); 
            }

            // 3. Show QR Section
            document.getElementById('qrSection').classList.remove('d-none');
            
            // 4. Pre-fill hidden fields for proof upload
            document.getElementById('amountHidden').value = amt.toString();
            document.getElementById('noteHidden').value = note;
        });

        // --- Proof Form Submit Sync ---
        document.getElementById('proofForm')?.addEventListener('submit', function(){
            // Re-syncs amount/note right before submission in case user edited fields without re-generating QR
            document.getElementById('amountHidden').value = document.getElementById('payAmount').value || '';
            document.getElementById('noteHidden').value = document.getElementById('payNote').value || '';
            
            // Basic final check
            if (!parseFloat(document.getElementById('amountHidden').value) > 0) {
                 alert('Please enter the payment amount before uploading proof.');
                 event.preventDefault(); // Stop form submission
            }
        });
        
        // --- Modal Wiring for Proof, Receipt, and Row QR ---

        // Wire Proof Modal
        (function(){
            var pm = document.getElementById('proofModal');
            if (!pm) return;
            pm.addEventListener('show.bs.modal', function(ev){
                var btn = ev.relatedTarget; if (!btn) return;
                var src = btn.getAttribute('data-src');
                var img = document.getElementById('proofImg');
                var dn  = document.getElementById('proofDownload');
                if (img) img.src = src || '';
                if (dn) dn.href = src || '#';
            });
            pm.addEventListener('hidden.bs.modal', function(){
                document.getElementById('proofImg').src = ''; // Clear source on hide
            });
        })();

        // Wire Receipt Modal
        (function(){
            var rm = document.getElementById('receiptModal');
            if (!rm) return;
            rm.addEventListener('show.bs.modal', function(ev){
                var btn = ev.relatedTarget; if (!btn) return;
                var href = btn.getAttribute('data-href');
                var frame = document.getElementById('receiptFrame');
                var openL = document.getElementById('receiptOpen');
                if (frame) frame.src = href || 'about:blank';
                if (openL) openL.href = href || '#';
            });
            rm.addEventListener('hidden.bs.modal', function(){
                document.getElementById('receiptFrame').src = 'about:blank'; // Clear source on hide
            });
        })();
        
        // Wire Per-row QR Modal
        (function(){
            var modalEl = document.getElementById('rowQRModal');
            if (!modalEl) return;
            modalEl.addEventListener('show.bs.modal', function (ev) {
                var btn = ev.relatedTarget;
                if (!btn) return;
                var upi = btn.getAttribute('data-upi');
                try {
                    var box = document.getElementById('rowQRBox');
                    if (box) { box.innerHTML = ''; }
                    if (window.QRCode && box && upi) {
                        new QRCode(box, { 
                            text: upi, 
                            width: 220, 
                            height: 220, 
                            colorDark: '#000000', 
                            colorLight: '#ffffff', 
                            correctLevel: QRCode.CorrectLevel.H 
                        });
                    }
                } catch(e) { /* ignore QR generation error */ }
            });
            modalEl.addEventListener('hidden.bs.modal', function(){
                document.getElementById('rowQRBox').innerHTML = ''; // Clear QR on hide
            });
        })();
    </script>

    <?php include __DIR__ . '/footer.php'; ?>

</body>
</html>