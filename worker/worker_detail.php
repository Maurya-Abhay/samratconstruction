<?php
// worker_detail.php - Worker Dashboard

require_once __DIR__ . '/../admin/lib_common.php';

// --- Session Check ---
if (!isset($_SESSION['worker_id'])) {
    header('Location: login');
    exit();
}

$worker_id = $_SESSION['worker_id'];
$worker = null;
$error = null;
$success = null;

// --- Worker Data Fetch ---
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param('i', $worker_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$worker) {
    echo '<div class="alert alert-danger">Worker not found!</div>';
    exit();
}

// Determine initial photo path
$photoPath = $worker['photo'] ?? '';
if (!empty($photoPath) && strpos($photoPath, 'http') === false) {
    // If photo is still a local path (not Cloudinary URL), prepend admin path
    $photoPath = '../admin/' . (strpos($photoPath, 'uploads/') === 0 ? $photoPath : 'uploads/' . $photoPath);
}


// --- Cloudinary Photo Upload Logic (Must be handled before fetching data for display) ---
// Assuming the form method for photo upload is POST and the input name is 'photo'
// Since this is a detail page, we assume a separate form/logic might be needed,
// but based on your fragmented code, I'm placing the logic here for security.

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp','image/gif'];
    $tmp = $_FILES['photo']['tmp_name'];
    $type = $_FILES['photo']['type'];
    $size = $_FILES['photo']['size'];
    
    if (!in_array($type, $allowed)) {
        $error = 'Only JPG, JPEG, PNG, WEBP, GIF files are allowed.';
    } elseif (getimagesize($tmp) === false) {
        $error = 'This is not a valid image file.';
    } elseif ($size > 2*1024*1024) {
        $error = 'File size must be less than 2MB.';
    } else {
        // Assuming upload_to_cloudinary() function exists and is defined in lib_common.php
        $cloud_url = upload_to_cloudinary($tmp, 'worker_photos');
        
        if ($cloud_url) {
            // Update worker photo in DB
            $stmt = $conn->prepare("UPDATE workers SET photo=? WHERE id=?");
            $stmt->bind_param('si', $cloud_url, $worker_id);
            if ($stmt->execute()) {
                $success = 'Photo uploaded securely to Cloudinary!';
                $photoPath = $cloud_url; // Update path for immediate display
                $worker['photo'] = $cloud_url; // Update worker array
            } else {
                $error = 'Failed to update photo in database.';
            }
            $stmt->close();
        } else {
            $error = 'Cloudinary upload failed. Check the connection or API settings.';
        }
    }
}


// --- Fetch payment history ---
$stmt = $conn->prepare("SELECT * FROM worker_payments WHERE worker_id = ? ORDER BY payment_date DESC");
$stmt->bind_param('i', $worker_id);
$stmt->execute();
$payments = $stmt->get_result();
$stmt->close();

$total_paid = 0;

if ($payments && $payments->num_rows > 0) {
    foreach ($payments as $prow) {
        $total_paid += floatval($prow['amount']);
    }
    // Rewind result pointer for later display
    $payments->data_seek(0); 
}

// --- Fetch attendance history and summary ---
$stmt = $conn->prepare("SELECT * FROM worker_attendance WHERE worker_id = ? ORDER BY date DESC");
$stmt->bind_param('i', $worker_id);
$stmt->execute();
$attendance = $stmt->get_result();
$stmt->close();

$summary = ['Present'=>0, 'Absent'=>0, 'Leave'=>0];

if ($attendance && $attendance->num_rows > 0) {
    foreach ($attendance as $row) {
        if (isset($summary[$row['status']])) $summary[$row['status']]++;
    }
    // Rewind result pointer for later display
    $attendance->data_seek(0);
}

// --- Check Today's Attendance Status ---
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT status FROM worker_attendance WHERE worker_id = ? AND date = ?");
$stmt->bind_param('is', $worker_id, $today);
$stmt->execute();
$attendanceStatus = $stmt->get_result()->fetch_assoc();
$stmt->close();

$marked = $attendanceStatus && in_array($attendanceStatus['status'], ['Present', 'Leave']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="manifest" href="/abhay/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta charset="UTF-8">
    <title>Worker Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" href="../admin/assets/smrticon.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 70px; /* To prevent content from hiding behind fixed navbar */
        }
        .card-title {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .summary-card .card-body span {
            font-size: 1.6rem;
        }
        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        table th, table td {
            vertical-align: middle !important;
        }
        .table-responsive {
            border-radius: 8px;
            overflow: hidden;
        }
        /* Modal ID Card styling for consistency */
        #tdCardContent {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="worker_detail">
            <img src="../admin/assets/111.png" alt="Logo" height="40">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="worker_detail">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../terms">Terms & Conditions</a></li>
                <li class="nav-item"><a class="nav-link" href="../privacy">Privacy Policy</a></li>
                <li class="nav-item"><a class="nav-link" href="../about">About Us</a></li>
            </ul>

            <a href="logout" class="btn btn-outline-danger btn-sm ms-lg-3">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>
<div class="container py-4 mt-5">

    <?php if ($success): ?>
        <div class="alert alert-success d-inline-block w-100 text-center"><i class="fa fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger d-inline-block w-100 text-center"><i class="fa fa-exclamation-triangle"></i> <?= $error ?></div>
    <?php endif; ?>
    
    <div class="mb-3 text-center border rounded shadow-sm bg-white p-3">
        <?php if ($marked): ?>
            <div class="alert alert-success d-inline-block"><i class="fa fa-check-circle"></i> Attendance marked for today!</div>
        <?php else: ?>
            <div id="attendanceNotify" class="alert alert-warning d-inline-block"><i class="fa fa-exclamation-triangle"></i> Attendance not marked yet!</div>
        <?php endif; ?>

        <div class="fw-semibold small mt-2">
            Welcome back, <strong><?= htmlspecialchars($worker['name']) ?></strong>
        </div>
    </div>

    <form class="row g-3 align-items-end mb-4 border rounded shadow-sm bg-white p-4" method="get" action="worker_pdf_report" target="_blank">
        <h5 class="text-center text-secondary mb-3">
            <i class="fas fa-calendar-alt me-2"></i> Select Date and Download Report
        </h5>
        <input type="hidden" name="id" value="<?= $worker_id ?>">
        
        <div class="col-md-4">
            <label class="form-label fw-bold">From</label>
            <input type="date" name="from" class="form-control" required>
        </div>
        
        <div class="col-md-4">
            <label class="form-label fw-bold">To</label>
            <input type="date" name="to" class="form-control" required>
        </div>
        
        <div class="col-md-4">
            <label class="form-label d-block">&nbsp;</label>
            <button type="submit" class="btn btn-danger w-100">
                <i class="fa fa-file-pdf me-1"></i> Download PDF Report
            </button>
        </div>
    </form>

    <div class="row g-4 border rounded shadow-sm bg-white p-4 mt-1">
        <h2 class="mb-4 text-primary">👷 Your Details</h2>
        <div class="col-md-4 text-center">
            <?php
            if (!empty($photoPath)) {
                echo "<img src='" . htmlspecialchars($photoPath) . "' class='img-thumbnail profile-img shadow-sm' alt='Photo'>";
            } else {
                echo "<div class='text-muted'><i class='fas fa-user fa-5x'></i></div>";
            }
            ?>
            <div class="d-flex justify-content-center align-items-center gap-3 mt-3">
                <button id="showTdCardBtn" class="btn btn-primary"><i class="fas fa-id-card"></i> View ID Card</button>
                <button id="showQRBtn" class="btn btn-info"><i class="fa fa-qrcode"></i> Show QR Code</button>
            </div>
        </div>
        <div class="col-md-8">
            <table class="table table-bordered shadow-sm">
                <tbody>
                    <tr><th>Name</th><td><?= htmlspecialchars($worker['name']) ?></td></tr>
                    <tr><th>Email</th><td><?= htmlspecialchars($worker['email']) ?></td></tr>
                    <tr><th>Phone</th><td><?= htmlspecialchars($worker['phone']) ?></td></tr>
                    <tr><th>Address</th><td><?= htmlspecialchars($worker['address'] ?? '-') ?></td></tr>
                    <tr><th>Aadhaar</th><td><?= htmlspecialchars($worker['aadhaar'] ?? '-') ?></td></tr>
                    <tr><th>Joining Date</th><td><?= htmlspecialchars($worker['joining_date'] ?? '-') ?></td></tr>
                    <tr><th>Salary (Daily)</th><td>₹<?= htmlspecialchars($worker['salary'] ?? '-') ?></td></tr>
                    <tr><th>Status</th><td><?= htmlspecialchars($worker['status'] ?? '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <h4 class="mt-5 mb-3 text-primary">📊 Summary</h4>
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card text-center shadow-sm summary-card">
                <div class="card-body">
                    <h6 class="card-title">Present Days</h6>
                    <span class="text-success fw-bold"><?= $summary['Present'] ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm summary-card">
                <div class="card-body">
                    <h6 class="card-title">Absent Days</h6>
                    <span class="text-danger fw-bold"><?= $summary['Absent'] ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm summary-card">
                <div class="card-body">
                    <h6 class="card-title">Leave Days</h6>
                    <span class="text-warning fw-bold"><?= $summary['Leave'] ?></span>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center shadow-sm summary-card">
                <div class="card-body">
                    <h6 class="card-title">Daily Wage</h6>
                    <span class="text-primary fw-bold">₹<?= number_format($worker['salary'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php
    $total_present_payment = $summary['Present'] * floatval($worker['salary']);
    $remaining_due = $total_present_payment - $total_paid;
    ?>
    <div class="alert alert-info shadow-sm">
        <strong>Total Payable (for Present Days):</strong> ₹<?= number_format($total_present_payment, 2) ?><br>
        <strong>Total Paid:</strong> ₹<?= number_format($total_paid, 2) ?><br>
        <strong>Remaining Due:</strong> <span class="text-danger fw-bold">₹<?= number_format($remaining_due, 2) ?></span>
    </div>

    <h4 class="mb-3 text-primary">📅 Attendance History</h4>
    <div class="table-responsive mb-5 shadow-sm rounded">
        <table class="table table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($attendance && $attendance->num_rows > 0): $i = 1; while($row = $attendance->fetch_assoc()): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= htmlspecialchars($row['date']) ?></td>
                        <td>
                            <?php if ($row['status'] == 'Present'): ?>
                                <span class="badge bg-success">Present</span>
                            <?php elseif ($row['status'] == 'Absent'): ?>
                                <span class="badge bg-danger">Absent</span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Leave</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['notes']) ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" class="text-center text-muted">No attendance records found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <h4 class="mb-3 text-primary">Payment History</h4>
    <?php if ($payments && $payments->num_rows > 0) { $payments->data_seek(0); } ?>
    <div class="table-responsive shadow-sm rounded">
        <table class="table table-striped table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#</th>
                    <th>Amount</th>
                    <th>Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($payments && $payments->num_rows > 0): $i=1; while($row = $payments->fetch_assoc()): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td>&#8377; <?= htmlspecialchars($row['amount']) ?></td>
                    <td><?= htmlspecialchars($row['payment_date']) ?></td>
                    <td><?= htmlspecialchars($row['remarks']) ?></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="4" class="text-center text-muted">No payments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="tdCardModal" tabindex="-1" aria-labelledby="tdCardModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow" style="border-radius: 12px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-primary" id="tdCardModalLabel">
                    <i class="bi bi-person-badge-fill me-2"></i>Digital Worker ID Card
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="font-family: 'Segoe UI', sans-serif;">
                <div id="tdCardContent" style="
                    width: 380px;
                    height: 220px;
                    margin: auto;
                    padding: 20px;
                    background: #fff;
                    border: 2px solid #0d6efd;
                    border-radius: 12px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                ">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h5 class="text-primary fw-bold mb-0">👷‍♂️ Worker ID</h5>
                            <small class="text-muted">Powered by Samrat Construction</small>
                        </div>
                        <img src="../admin/assets/111.png" alt="Company Logo" height="40" />
                    </div>

                    <div class="d-flex align-items-center mt-2">
                        <div class="me-3">
                            <img id="tdCardPhoto" src="" alt="Photo" style="width: 80px; height: 80px; border-radius: 50%; border: 3px solid #0d6efd; object-fit: cover;" />
                        </div>
                        <div style="font-size: 13px;">
                            <strong id="tdCardName" class="text-dark"></strong><br/>
                            <span><strong>Email:</strong> <span id="tdCardEmail"></span></span><br/>
                            <span><strong>Phone:</strong> <span id="tdCardPhone"></span></span><br/>
                            <span><strong>Aadhaar:</strong> <span id="tdCardAadhaar"></span></span><br/>
                            <span><strong>Joining:</strong> <span id="tdCardJoining"></span></span>
                        </div>
                    </div>

                    <div class="text-muted mt-2" id="tdCardDateTime" style="font-size: 11px;"></div>
                </div>
            </div>

            <div class="modal-footer border-0 pt-0">
                <button type="button" id="downloadTdCardBtn" class="btn btn-success">
                    <i class="bi bi-download me-1"></i> Download PDF
                </button>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="workerQRModal" tabindex="-1" aria-labelledby="workerQRModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="workerQRModalLabel"><i class="fa fa-qrcode"></i> Your QR Code</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div id="workerQR" style="display:inline-block;background:#fff;padding:12px;border-radius:12px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


<footer class="mt-auto bg-light py-3">
    <div class="container text-center">
        <p class="mb-1">Powered By <strong>Samrat Construction Private Limited</strong></p>
        
        <div class="text-muted small" style="white-space: nowrap;">
            <i class="far fa-clock me-1"></i>
            <span id="currentDateTime"></span>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script src='https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js'></script>


<script>

document.addEventListener('DOMContentLoaded', function () {
    // --- QR Code Logic ---
    document.getElementById('showQRBtn')?.addEventListener('click', function () {
        const workerQR = document.getElementById('workerQR');
        workerQR.innerHTML = '';
        // Ensure QRCode object is available globally
        if (window.QRCode) {
            new window.QRCode(workerQR, {
                text: "<?= $worker_id ?>",
                width: 120,
                height: 120,
                colorDark: "#0d6efd",
                colorLight: "#fff",
                correctLevel: window.QRCode.CorrectLevel.H
            });
        }
        const modal = new bootstrap.Modal(document.getElementById('workerQRModal'));
        modal.show();
    });

    // --- ID Card Modal Logic ---
    document.getElementById('showTdCardBtn')?.addEventListener('click', function () {
        // Data from backend (PHP in this case)
        const photoSrc = "<?= $photoPath ?>";
        const name = "<?= addslashes(htmlspecialchars($worker['name'])) ?>";
        const email = "<?= addslashes(htmlspecialchars($worker['email'])) ?>";
        const phone = "<?= addslashes(htmlspecialchars($worker['phone'])) ?>";
        const aadhaar = "<?= addslashes(htmlspecialchars($worker['aadhaar'] ?? '-')) ?>";
        const joining = "<?= addslashes(htmlspecialchars($worker['joining_date'] ?? '-')) ?>";

        // Fill data
        document.getElementById('tdCardPhoto').src = photoSrc;
        document.getElementById('tdCardName').textContent = name;
        document.getElementById('tdCardEmail').textContent = email;
        document.getElementById('tdCardPhone').textContent = phone;
        document.getElementById('tdCardAadhaar').textContent = aadhaar;
        document.getElementById('tdCardJoining').textContent = joining;

        // Timestamp
        const now = new Date();
        document.getElementById('tdCardDateTime').textContent = 'Generated on: ' + now.toLocaleString('en-IN');

        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('tdCardModal'));
        modal.show();
    });

    // --- Download as PDF ---
    document.getElementById('downloadTdCardBtn')?.addEventListener('click', () => {
        const element = document.getElementById('tdCardContent');
        const opt = {
            margin: 0,
            filename: 'worker-id-card.pdf',
            image: { type: 'jpeg', quality: 0.8 },
            html2canvas: { scale: 4, useCORS: true },
            jsPDF: {
                unit: 'px',
                format: [400, 220],
                orientation: 'landscape'
            }
        };
        html2pdf().set(opt).from(element).save();
    });
    
    // --- Footer Date/Time Update ---
    function updateFooterTime() {
        const now = new Date();
        const options = {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit'
        };
        const dateTimeElement = document.getElementById('currentDateTime');
        if (dateTimeElement) {
            dateTimeElement.textContent = now.toLocaleString('en-IN', options);
        }
    }

    // Initial call
    updateFooterTime();
    // Update every second
    setInterval(updateFooterTime, 1000);

});

</script>

</body>
</html>