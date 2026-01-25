<?php
// --- PHP Configuration and Security ---
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
session_start();

// Ensure the database connection file is included
require_once '../admin/database.php'; 

// Optional: Include analytics tracking
@include_once __DIR__ . '/../admin/analytics_track.php'; 

// Authentication Check
if (!isset($_SESSION['contact_id'])) {
    header('Location: login.php');
    exit();
}
$contact_id = (int)$_SESSION['contact_id'];

// --- Data Fetching ---
$stmt = $conn->prepare("SELECT id, name, email, phone, photo, contract_amount, amount_paid, joining_date, aadhaar FROM contacts WHERE id=? LIMIT 1");
$stmt->bind_param('i', $contact_id);
$stmt->execute();
$contact = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Variable Setup ---
$name = $contact['name'] ?? 'Customer';
$photo = $contact['photo'] ?? '';
// Photo path resolution
$photoPath = (!empty($photo) && preg_match('#^https?://#', $photo)) ? $photo : 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0d6efd&color=fff&size=96';

// Calculate Due Amount
$due_amount = max(0, ($contact['contract_amount'] ?? 0) - ($contact['amount_paid'] ?? 0));

// --- Holiday Logic ---
$conn->query("CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_date DATE NOT NULL,
    description TEXT,
    is_active ENUM('Yes', 'No') DEFAULT 'Yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$today = date('Y-m-d');
$next_holiday = $conn->query("SELECT * FROM holidays WHERE holiday_date >= '$today' AND is_active='Yes' ORDER BY holiday_date ASC LIMIT 1")->fetch_assoc();

$holiday_title = $next_holiday['holiday_name'] ?? 'No Upcoming Holidays';
$holiday_date_display = $next_holiday ? date('d M Y', strtotime($next_holiday['holiday_date'])) : '--';
$holiday_when = 'Enjoy your work!';
if ($next_holiday) {
    if ($next_holiday['holiday_date'] === $today) {
        $holiday_when = 'Today!';
    } else {
        $d = (new DateTime($today))->diff(new DateTime($next_holiday['holiday_date']))->days;
        $holiday_when = "In $d days";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../admin/assets/jp_construction_logo.webp" type="image/webp">
    <title><?= htmlspecialchars($name) ?> - Client Dashboard</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>

    <style>
        :root {
            --primary: #4f46e5; /* Indigo Blue */
            --primary-dark: #4338ca;
            --accent: #F37021; /* Orange for Highlights */
            --bg-light: #f1f5f9;
            --text-dark: #1e293b;
            --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--bg-light);
        }

        /* --- Blue Header Section --- */
        .dashboard-header {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            padding: 80px 0 80px; /* Space for navbar + content */
            color: white;
            border-bottom-left-radius: 20px;
            border-bottom-right-radius: 20px;
            position: relative;
            margin-bottom: -60px; /* Overlap effect */
            z-index: 1;
        }

        .header-content h5 { font-weight: 400; opacity: 0.9; margin-bottom: 5px; }
        .header-content h1 { font-weight: 800; font-size: 2.2rem; }

        /* --- QR Button in Header --- */
        .btn-qr-header {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            padding: 5px 10px;
            border-radius: 12px;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }
        .btn-qr-header:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); color: white;}

        /* --- Main Layout Grid --- */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            position: relative;
            z-index: 10;
        }

        /* --- Cards --- */
        .custom-card {
            background: white;
            border-radius: 20px;
            border: none;
            box-shadow: var(--card-shadow);
            padding: 25px;
            height: 100%;
            transition: transform 0.2s;
        }
        
        /* --- Left Column: Holiday & Quick Actions --- */
        
        /* Holiday Card */
        .holiday-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
        }
        .holiday-icon {
            font-size: 2rem;
            color: var(--primary);
            background: #e0e7ff;
            width: 60px; height: 60px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 15px;
            margin-right: 20px;
        }

        /* Small Stats */
        .mini-stat-card {
            background: white;
            border-radius: 18px;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        .mini-icon {
            width: 45px; height: 45px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.2rem;
            margin-right: 15px;
        }

        /* Quick Actions Grid */
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }
        .action-tile {
            background: white;
            border-radius: 20px;
            padding: 25px 15px;
            text-align: center;
            text-decoration: none;
            color: var(--text-dark);
            box-shadow: var(--card-shadow);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            aspect-ratio: 1/1; /* Square tiles */
        }
        .action-tile:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .action-icon-box {
            width: 50px; height: 50px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 12px;
        }
        .action-label { font-weight: 600; font-size: 0.95rem; }
        .action-sub { font-size: 0.75rem; color: #64748b; margin-top: 4px; }

        /* --- Right Column: Profile --- */
        .profile-card {
            text-align: center;
            padding-top: 40px;
            position: relative;
        }
        .profile-img-container {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%);
        }
        .profile-avatar {
            width: 100px; height: 100px;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: var(--card-shadow);
            object-fit: cover;
        }
        .status-dot {
            width: 15px; height: 15px;
            background: #10b981;
            border: 2px solid white;
            border-radius: 50%;
            position: absolute;
            bottom: 5px; right: 5px;
        }
        
        .info-row {
            display: flex; justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px dashed #e2e8f0;
            font-size: 0.9rem;
        }
        .info-row:last-child { border-bottom: none; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-val { font-weight: 700; color: var(--text-dark); }

        .btn-view-id {
            background: var(--primary);
            color: white;
            width: 100%;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            margin-top: 20px;
        }
        .btn-view-id:hover { background: var(--primary-dark); color: white; }

        /* ID Card Modal Styles (Preserved) */
        .id-card-modern {
            width: 300px; background: #fff; border-radius: 15px;
            overflow: hidden; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin: auto; border: 1px solid #e9ecef;
        }

        @media (max-width: 768px) {
            .actions-grid { grid-template-columns: repeat(2, 1fr); }
            .dashboard-header { padding: 60px 0 60px; text-align: center; }
            .header-content { margin-bottom: 20px; }
            .header-actions { justify-content: center; }
        }
    </style>
</head>
<body>

<?php $contact_show_back_btn = false; include __DIR__ . '/header.php'; ?>

<div class="dashboard-header">
    <div class="main-container">
        <div class="row align-items-center">
            <div class="col-md-8 text-md-start text-center header-content">
                <h5>Good Morning,</h5>
                <h1><?= htmlspecialchars($name) ?></h1>
            </div>
            <div class="col-md-4 text-md-end text-center header-actions">
                <button class="btn btn-qr-header" data-bs-toggle="modal" data-bs-target="#viewCardModal">
                    <i class="bi bi-qr-code-scan me-2"></i> Show ID Card
                </button>
            </div>
        </div>
    </div>
</div>

<div class="main-container">
    <div class="row g-3">
        
        <div class="col-lg-8">
            
            <div class="holiday-card">
                <div class="d-flex align-items-center">
                    <div class="holiday-icon">
                        <i class="bi bi-calendar-event"></i>
                    </div>
                    <div>
                        <div class="text-uppercase text-muted small fw-bold">Upcoming Holiday</div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($holiday_title) ?></h4>
                        <div class="text-primary fw-semibold">
                            <i class="bi bi-clock me-1"></i> <?= $holiday_when ?> 
                            <span class="text-muted ms-1">(<?= $holiday_date_display ?>)</span>
                        </div>
                    </div>
                </div>
                <a href="holidays.php" class="btn btn-outline-secondary rounded-pill btn-sm px-3">View All</a>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mini-stat-card">
                        <div class="mini-icon" style="background: #fee2e2; color: #dc2626;">
                            <i class="bi bi-exclamation-triangle"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5">₹<?= number_format($due_amount, 2) ?></div>
                            <div class="text-muted small">Current Due</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mini-stat-card">
                        <div class="mini-icon" style="background: #dcfce7; color: #16a34a;">
                            <i class="bi bi-check-circle"></i>
                        </div>
                        <div>
                            <div class="fw-bold fs-5">Active</div>
                            <div class="text-muted small">Contract Status</div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="text-uppercase text-muted fw-bold mb-3 mt-2">Quick Actions</h6>
            <div class="actions-grid">
                
                <a href="payments.php" class="action-tile">
                    <div class="action-icon-box" style="background: #e0f2fe; color: #0284c7;">
                        <i class="bi bi-wallet2"></i>
                    </div>
                    <div class="action-label">My Payments</div>
                    <div class="action-sub">History & Dues</div>
                </a>

                <a href="upi_pay.php" class="action-tile">
                    <div class="action-icon-box" style="background: #fef3c7; color: #d97706;">
                        <i class="bi bi-qr-code"></i>
                    </div>
                    <div class="action-label">Pay via UPI</div>
                    <div class="action-sub">Instant Pay</div>
                </a>

                <a href="messages.php" class="action-tile">
                    <div class="action-icon-box" style="background: #fce7f3; color: #db2777;">
                        <i class="bi bi-chat-text"></i>
                    </div>
                    <div class="action-label">Messages</div>
                    <div class="action-sub">Support Chat</div>
                </a>

                <a href="profile.php" class="action-tile">
                    <div class="action-icon-box" style="background: #f3f4f6; color: #4b5563;">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div class="action-label">Profile</div>
                    <div class="action-sub">Edit Details</div>
                </a>

                <a href="contact_work_status.php" class="action-tile">
                    <div class="action-icon-box" style="background: #dcfce7; color: #16a34a;">
                        <i class="bi bi-bar-chart-steps"></i>
                    </div>
                    <div class="action-label">Work Status</div>
                    <div class="action-sub">Track Progress</div>
                </a>

                <a href="holidays.php" class="action-tile">
                    <div class="action-icon-box" style="background: #ffedd5; color: #c2410c;">
                        <i class="bi bi-calendar3"></i>
                    </div>
                    <div class="action-label">Holidays</div>
                    <div class="action-sub">Yearly List</div>
                </a>

            </div>
        </div>

        <div class="col-lg-4">
            <div class="custom-card profile-card">
                <div class="profile-img-container">
                    <img src="<?= htmlspecialchars($photoPath) ?>" alt="Profile" class="profile-avatar">
                    <div class="status-dot"></div>
                </div>
                
                <h4 class="fw-bold mt-4 mb-1"><?= htmlspecialchars($name) ?></h4>
                <div class="text-muted small mb-4">Valued Client</div>

                <div class="text-start px-2">
                    <div class="info-row">
                        <span class="info-label">Client ID</span>
                        <span class="info-val">#<?= str_pad($contact_id, 4, '0', STR_PAD_LEFT) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-val"><?= htmlspecialchars($contact['phone']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Joined</span>
                        <span class="info-val"><?= date('M d, Y', strtotime($contact['joining_date'])) ?></span>
                    </div>
                </div>

                <button class="btn-view-id" data-bs-toggle="modal" data-bs-target="#viewCardModal">
                    <i class="bi bi-eye me-2"></i> View Digital ID
                </button>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="viewCardModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 rounded-4 p-0 bg-transparent shadow-none">
            <div class="id-card-modern position-relative">
                <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 z-3" data-bs-dismiss="modal"></button>
                
                <div style="background:#4f46e5;padding:20px;text-align:center;color:white;">
                    <h6 class="mb-0 fw-bold ls-1">SAMRAT CONSTRUCTION</h6>
                    <small style="opacity:0.8;">Client Identity Card</small>
                </div>
                
                <div class="bg-white text-center pt-4 pb-3 px-3">
                    <img src="<?= htmlspecialchars($photoPath) ?>" style="width:90px;height:90px;border-radius:50%;object-fit:cover;border:3px solid #4f46e5;margin-top:-65px;background:white;">
                    <h5 class="fw-bold mt-2 text-dark"><?= htmlspecialchars($name) ?></h5>
                    <div class="badge bg-light text-primary border mb-3">ID: <?= $contact_id ?></div>
                    
                    <div class="text-start small text-muted border-top pt-3">
                        <div class="d-flex justify-content-between mb-1"><span>Phone:</span> <span class="fw-bold text-dark"><?= $contact['phone'] ?></span></div>
                        <div class="d-flex justify-content-between mb-1"><span>Joined:</span> <span class="fw-bold text-dark"><?= $contact['joining_date'] ?></span></div>
                    </div>

                    <div class="bg-light rounded p-3 mt-3 d-flex justify-content-center">
                        <div id="contactCardQRDisplay"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // ID Card Logic
    var contactId = <?= json_encode($contact_id) ?>;
    var contactQrData = 'CLIENT_ID:' + contactId;
    var viewCardModal = document.getElementById('viewCardModal');
    
    viewCardModal.addEventListener('shown.bs.modal', function () {
        var qrElement = document.getElementById('contactCardQRDisplay');
        qrElement.innerHTML = '';
        new QRCode(qrElement, {
            text: contactQrData,
            width: 100,
            height: 100,
            colorDark : "#2c3e50",
            colorLight : "#f8f9fa",
            correctLevel : QRCode.CorrectLevel.H
        });
    });

    // Auto Refresh
    setInterval(function () { window.location.reload(); }, 60000);
});
</script>
<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>