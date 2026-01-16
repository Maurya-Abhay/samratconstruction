<?php
// dash.php - Ultimate Modern Admin Dashboard
session_start();

// --- 1. Security & Authentication ---
if (!isset($_SESSION['email'])) {
    header('Location: index.php');
    exit;
}

// --- 2. URL Cleanup (Prevent path traversal issues) ---
if (isset($_SERVER['REQUEST_URI'])) {
    $uri = $_SERVER['REQUEST_URI'];
    if (stripos($uri, '/C:/') !== false || preg_match('#/admin/dash$#i', $uri)) {
        header('Location: dash.php');
        exit;
    }
}

// --- 3. Includes ---
// These files must exist in your directory. If they don't, the page layout might break but logic will run.
include 'topheader.php'; 
include 'sidenavbar.php';
include 'database.php'; // Ensure DB connection is active

// --- 4. Database Helper Functions ---

/**
 * Check if a table exists in the database.
 */
function table_exists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return ($result && $result->num_rows > 0);
}

/**
 * Safely get the count of rows from a table. Returns 0 if table doesn't exist.
 */
function get_safe_count($conn, $table, $where = "") {
    if (table_exists($conn, $table)) {
        $sql = "SELECT COUNT(*) FROM $table $where";
        $res = $conn->query($sql);
        if ($res) {
            $row = $res->fetch_row();
            return (int)$row[0];
        }
    }
    return 0;
}

// --- 5. Ensure Critical Tables Exist (Auto-fix for fresh installs) ---
$conn->query("CREATE TABLE IF NOT EXISTS worker_payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  worker_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  method VARCHAR(50) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_worker_date (worker_id, payment_date)
) ENGINE=InnoDB");

// --- 6. Fetch Data Statistics ---

// HR & Workforce
$worker_count = get_safe_count($conn, 'workers');
$attendance_users = get_safe_count($conn, 'attendence_users');

// Face Registration (File based)
$faces_registered = 0;
if (file_exists('workers_face_data.json')) {
    $json_data = json_decode(file_get_contents('workers_face_data.json'), true);
    $faces_registered = is_array($json_data) ? count($json_data) : 0;
}

// Attendance & Leaves
$today = date('Y-m-d');
$attendance_today = get_safe_count($conn, 'worker_attendance', "WHERE date='$today' AND status='Present'");
$pending_leaves   = get_safe_count($conn, 'worker_leaves', "WHERE status='Pending'");

// Operations & Clients
$service_count     = get_safe_count($conn, 'services');
$contact_count     = get_safe_count($conn, 'contacts');
$contracts_active  = get_safe_count($conn, 'contacts', "WHERE status='Active' OR status='In Progress'");
$notice_count      = get_safe_count($conn, 'notices');
$reports_count     = get_safe_count($conn, 'reports');
$holidays_count    = get_safe_count($conn, 'holidays');

// Finance
$payment_records = get_safe_count($conn, 'worker_payments');
$pending_upi     = get_safe_count($conn, 'upi_payments', "WHERE status='Pending'");

// Handle Budget table name variation
$budget_count = 0;
if (table_exists($conn, 'budget')) {
    $budget_count = get_safe_count($conn, 'budget');
} elseif (table_exists($conn, 'budgets')) {
    $budget_count = get_safe_count($conn, 'budgets');
}

// Support
$new_messages = get_safe_count($conn, 'contact_messages', "WHERE status='Open'");

// User Info Logic
$user_name = $_SESSION['name'] ?? 'Admin'; // Default to session
// Try to fetch specific admin name if ID is set
if (isset($_SESSION['admin_id'])) {
    $aid = (int)$_SESSION['admin_id'];
    $ares = $conn->query("SELECT name FROM admin WHERE id=$aid");
    if ($ares && $arow = $ares->fetch_assoc()) {
        $user_name = $arow['name'];
    }
}

// Greeting Logic
$hour = date('H');
$greeting = ($hour < 12) ? 'Good Morning' : (($hour < 18) ? 'Good Afternoon' : 'Good Evening');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Dashboard | Samrat Construction</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <style>
        :root {
            --primary: #4361ee;
            --secondary: #3f37c9;
            --success: #4cc9f0;
            --info: #4895ef;
            --warning: #f72585;
            --light: #f8f9fc;
            --card-bg: #ffffff;
            --border-radius: 16px;
            --shadow: 0 10px 30px 0 rgba(0,0,0,0.05);
        }

        body {
            background-color: var(--light);
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #444;
            overflow-x: hidden;
        }

        /* --- 1. Welcome Card --- */
        .welcome-card {
            background: linear-gradient(135deg, #4361ee 0%, #3a0ca3 100%);
            border-radius: var(--border-radius);
            padding: 0.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(67, 97, 238, 0.4);
            margin-bottom: 1rem;
        }
        .welcome-card::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .refresh-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            backdrop-filter: blur(5px);
            transition: 0.3s;
            text-decoration: none;
        }
        .refresh-btn:hover { background: white; color: var(--primary); }
        
        /* Animation for refresh icon */
        .spin-anim { animation: spin 1s linear infinite; }
        @keyframes spin { 100% { transform: rotate(360deg); } }

        /* --- 2. Stat Cards --- */
        .stat-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: none;
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none; /* Remove underline from links */
            color: inherit;
        }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 35px rgba(0,0,0,0.1); }
        
        .stat-icon {
            width: 56px; height: 56px;
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem;
        }
        
        /* Icon Backgrounds */
        .bg-soft-primary { background: rgba(67, 97, 238, 0.1); color: var(--primary); }
        .bg-soft-success { background: rgba(76, 201, 240, 0.1); color: #00b4d8; }
        .bg-soft-warning { background: rgba(247, 37, 133, 0.1); color: var(--warning); }
        .bg-soft-purple  { background: rgba(114, 9, 183, 0.1); color: #7209b7; }

        /* --- 3. Module Links --- */
        .section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #8898aa;
            margin: 1.5rem 0 0.8rem 0;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }

        .module-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.2rem;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            border: 1px solid #f1f1f1;
            text-decoration: none;
            color: #555;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .module-card:hover {
            border-color: var(--primary);
            background: #f8f9ff;
            transform: translateX(5px);
            color: var(--primary);
        }
        .module-icon {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 38px; height: 38px;
            background: #f8f9fa;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            color: #6c757d;
            transition: 0.2s;
        }
        .module-card:hover .module-icon {
            background: var(--primary);
            color: white;
        }
        .module-count {
            margin-left: auto;
            font-weight: 700;
            background: #eee;
            color: #333;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 0.8rem;
        }

        /* --- 4. Widgets --- */
        .widget-card {
            background: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        /* Responsive Tweaks */
        @media (max-width: 768px) {
            .welcome-card { padding: 1.5rem; text-align: center; }
            .welcome-card .d-flex { flex-direction: column; gap: 1rem; }
            .stat-card { padding: 1rem; }
        }
    </style>
</head>
<body>

<div class="container-fluid px-3 py-3">
    
    <div class="welcome-card d-flex justify-content-between align-items-center">
        <div>
            <h2 class="fw-bold mb-1"><?= $greeting ?>, <?= htmlspecialchars($user_name) ?>! 👋</h2>
            <p class="mb-0 opacity-75">Admin Dashboard Overview</p>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-xl-3 col-md-6">
            <a href="admin_staff_attendance.php" class="stat-card">
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Staff Dashboard</div>
                    <div class="h2 mb-0 fw-bold text-dark" id="disp_staff_count"><?= $attendance_users ?></div>
                    <div class="small text-info mt-1"><i class="bi bi-person-badge"></i> Staff Attendance</div>
                </div>
                <div class="stat-icon bg-soft-primary"><i class="bi bi-person-badge"></i></div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="worker_attendance_history.php" class="stat-card">
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Present Today</div>
                    <div class="h2 mb-0 fw-bold text-dark" id="disp_attendance_today"><?= $attendance_today ?></div>
                    <div class="small text-muted mt-1">Date: <?= date('d M') ?></div>
                </div>
                <div class="stat-icon bg-soft-success"><i class="bi bi-calendar-check"></i></div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="messages.php" class="stat-card">
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">New Messages</div>
                    <div class="h2 mb-0 fw-bold text-dark" id="disp_new_messages"><?= $new_messages ?></div>
                    <div class="small text-danger mt-1"><?= $pending_upi ?> UPI Pending</div>
                </div>
                <div class="stat-icon bg-soft-warning"><i class="bi bi-chat-square-dots"></i></div>
            </a>
        </div>
        <div class="col-xl-3 col-md-6">
            <a href="customers.php" class="stat-card">
                <div>
                    <div class="text-muted small fw-bold text-uppercase mb-1">Customers</div>
                    <div class="h2 mb-0 fw-bold text-dark" id="disp_contact_count"><?= $contact_count ?></div>
                    <div class="small text-muted mt-1"><?= $contracts_active ?> Active Contracts</div>
                </div>
                <div class="stat-icon bg-soft-purple"><i class="bi bi-briefcase"></i></div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-8">
            
            <div class="section-title"><i class="bi bi-person-workspace me-2"></i>HR & Workforce</div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <a href="workers.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-person-badge"></i></div>
                        <span>Workers List</span>
                        <span class="module-count"><?= $worker_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="worker_attendance_history.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-clock-history"></i></div>
                        <span>Attendance History</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="attendance_marking.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-fingerprint"></i></div>
                        <span>Mark Attendance</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="approve_digital_attendance.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-person-check"></i></div>
                        <span>Approve Digital Attendance</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="leave_management.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-calendar-x"></i></div>
                        <span>Leave Requests</span>
                        <?php if($pending_leaves > 0): ?><span class="badge bg-danger ms-auto"><?= $pending_leaves ?></span><?php endif; ?>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="register_worker_face.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-person-bounding-box"></i></div>
                        <span>Face Register</span>
                        <span class="module-count"><?= $faces_registered ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="manage_users.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-people"></i></div>
                        <span>App Users</span>
                        <span class="module-count"><?= $attendance_users ?></span>
                    </a>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-wallet2 me-2"></i>Finance & Operations</div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <a href="worker_payments_history.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-cash-stack"></i></div>
                        <span>Worker Payments</span>
                        <span class="module-count"><?= $payment_records ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="worker_make_payment.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-plus-circle"></i></div>
                        <span>Make Payment</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="upi_review.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-qr-code"></i></div>
                        <span>UPI Reviews</span>
                        <?php if($pending_upi > 0): ?><span class="badge bg-warning text-dark ms-auto"><?= $pending_upi ?></span><?php endif; ?>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="budget.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-pie-chart"></i></div>
                        <span>Project Budget</span>
                        <span class="module-count"><?= $budget_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="customers.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-building"></i></div>
                        <span>Customers</span>
                        <span class="module-count"><?= $contact_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="contract_status.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-file-earmark-text"></i></div>
                        <span>Contracts</span>
                        <span class="module-count"><?= $contracts_active ?></span>
                    </a>
                </div>
            </div>

            <div class="section-title"><i class="bi bi-tools me-2"></i>Tools</div>
            <div class="row g-3">
                <div class="col-md-6 col-lg-4">
                    <a href="messages.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-chat-dots"></i></div>
                        <span>Messages</span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="services.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-grid-fill"></i></div>
                        <span>Services</span>
                        <span class="module-count"><?= $service_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="reports.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-file-bar-graph"></i></div>
                        <span>Reports</span>
                        <span class="module-count"><?= $reports_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="holidays.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-calendar-event"></i></div>
                        <span>Holidays</span>
                        <span class="module-count"><?= $holidays_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="notice_management.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-megaphone"></i></div>
                        <span>Notices</span>
                        <span class="module-count"><?= $notice_count ?></span>
                    </a>
                </div>
                <div class="col-md-6 col-lg-4">
                    <a href="worker_pdf_selector.php" class="module-card">
                        <div class="module-icon"><i class="bi bi-file-pdf"></i></div>
                        <span>Generate PDF</span>
                    </a>
                </div>
            </div>

        </div>

        <div class="col-lg-4">
            
            <div class="widget-card">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="fw-bold m-0">Traffic Overview</h5>
                    <select id="trafficRange" class="form-select form-select-sm w-auto bg-light border-0">
                        <option value="7">7 Days</option>
                        <option value="30" selected>30 Days</option>
                    </select>
                </div>
                <div style="height: 250px; position: relative;">
                    <canvas id="trafficChart"></canvas>
                </div>
            </div>

            <div class="widget-card bg-primary text-white">
                <h5 class="fw-bold mb-3">Quick Actions</h5>
                <div class="d-grid gap-2">
                    <a href="worker_make_payment.php" class="btn btn-light text-primary fw-bold text-start"><i class="bi bi-plus-circle me-2"></i> Record Payment</a>
                    <a href="notice_management.php" class="btn btn-outline-light text-start"><i class="bi bi-megaphone me-2"></i> Post Notice</a>
                    <a href="workers.php" class="btn btn-outline-light text-start"><i class="bi bi-person-plus me-2"></i> Add Worker</a>
                </div>
            </div>
            
            <div class="widget-card text-center">
                <div class="text-muted small">System Time</div>
                <div class="h3 fw-bold my-2 text-primary" id="liveTime"><?= date('h:i A') ?></div>
                <div class="small text-muted"><?= date('l, d F Y') ?></div>
            </div>

        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    // 1. Manual Refresh Animation & Reload
    function manualRefresh() {
        const icon = document.getElementById('refreshIcon');
        icon.classList.add('spin-anim');
        
        // Fetch latest stats via AJAX (dashboard_data.php should return JSON)
        // Or simply reload page for simplicity in this context
        location.reload();
    }

    // 2. Live Time Widget
    setInterval(() => {
        const now = new Date();
        document.getElementById('liveTime').innerText = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }, 1000);

    // 3. Traffic Chart Logic
    (function(){
        const ctx = document.getElementById('trafficChart');
        if(!ctx) return; // Exit if chart canvas missing
        
        let chart;

        async function loadTraffic(days){
            try {
                // Ensure this path is correct relative to dash.php
                const res = await fetch('analytics_data.php?days=' + days, { cache: 'no-store' });
                
                if (!res.ok) throw new Error('Network response was not ok');
                
                const data = await res.json();
                
                const labels = data.labels.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
                });

                const context = ctx.getContext('2d');
                let gradient = context.createLinearGradient(0, 0, 0, 300);
                gradient.addColorStop(0, 'rgba(67, 97, 238, 0.4)');
                gradient.addColorStop(1, 'rgba(67, 97, 238, 0.0)');

                const config = {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Visitors',
                            data: data.counts,
                            fill: true,
                            backgroundColor: gradient,
                            borderColor: '#4361ee',
                            borderWidth: 2,
                            tension: 0.4, // Curved lines
                            pointRadius: 0,
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { display: false },
                            y: { display: false }
                        },
                        interaction: { mode: 'index', intersect: false }
                    }
                };

                if(chart) chart.destroy();
                chart = new Chart(context, config);
            } catch(e) { 
                console.warn('Chart data could not be loaded. Ensure analytics_data.php exists.'); 
            }
        }

        document.getElementById('trafficRange').addEventListener('change', (e) => loadTraffic(e.target.value));
        loadTraffic(30); // Initial Load
    })();

    // Auto-refresh every 1 minute for admin panel
    setInterval(function() {
        location.reload();
    }, 60000);
    
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>