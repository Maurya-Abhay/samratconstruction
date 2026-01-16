

<?php

session_start();

if (!isset($_SESSION['email'])) {

    header('Location: index.php');

    exit;

}

// Canonicalize URL to avoid malformed paths like /C:/... or /admin/dash without .php

if (isset($_SERVER['REQUEST_URI'])) {

  $uri = $_SERVER['REQUEST_URI'];

  if (stripos($uri, '/C:/') !== false || preg_match('#/admin/dash$#i', $uri)) {

    header('Location: dash.php');

    exit;

  }

}



include 'topheader.php';

include 'sidenavbar.php';



// Fetch counts from DB

$conn->query("CREATE TABLE IF NOT EXISTS worker_payments (

  id INT AUTO_INCREMENT PRIMARY KEY,

  worker_id INT NOT NULL,

  amount DECIMAL(10,2) NOT NULL,

  payment_date DATE NOT NULL,

  method VARCHAR(50) NULL,

  notes TEXT NULL,

  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

  INDEX idx_worker_date (worker_id, payment_date),

  CONSTRAINT fk_wp_worker_dash FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE

) ENGINE=InnoDB");



$service_count = (int)$conn->query("SELECT COUNT(*) FROM services")->fetch_row()[0];

$contact_count = (int)$conn->query("SELECT COUNT(*) FROM contacts")->fetch_row()[0];

$customer_count = $contact_count; // Contacts are customers in this app

$worker_count = (int)$conn->query("SELECT COUNT(*) FROM workers")->fetch_row()[0];

$attendance_count = (int)$conn->query("SELECT COUNT(*) FROM worker_attendance")->fetch_row()[0];



// Attendance Users count (from attendence module manage_users table)

$attendance_users_count = 0;

if ($res = $conn->query("SHOW TABLES LIKE 'attendence_users'")) {

  if ($res->num_rows > 0) {

    $row = $conn->query("SELECT COUNT(*) FROM attendence_users")->fetch_row();

    $attendance_users_count = (int)($row[0] ?? 0);

  }

}



// Face register count (workers with face data registered)

$face_register_count = 0;

if (file_exists('workers_face_data.json')) {

  $face_data = json_decode(file_get_contents('workers_face_data.json'), true);

  $face_register_count = is_array($face_data) ? count($face_data) : 0;

}

// Guard count for worker_payments in case of fresh DB

$wp_count_res = $conn->query("SELECT COUNT(*) FROM worker_payments");

$worker_payments_count = $wp_count_res ? (int)($wp_count_res->fetch_row()[0] ?? 0) : 0;



// Leave count (if table exists)

$leave_count = 0;

if ($res = $conn->query("SHOW TABLES LIKE 'worker_leaves'")) {

  if ($res->num_rows > 0) {

    $row = $conn->query("SELECT COUNT(*) FROM worker_leaves")->fetch_row();

    $leave_count = (int)($row[0] ?? 0);

  }

}



// Holidays count (if table exists)

$holiday_count = 0;

if ($res = $conn->query("SHOW TABLES LIKE 'holidays'")) {

  if ($res->num_rows > 0) {

    $row = $conn->query("SELECT COUNT(*) FROM holidays")->fetch_row();

    $holiday_count = (int)($row[0] ?? 0);

  }

}



// Notices count (if table exists)

$notice_count = 0;

if ($res = $conn->query("SHOW TABLES LIKE 'notices'")) {

  if ($res->num_rows > 0) {

    $row = $conn->query("SELECT COUNT(*) FROM notices")->fetch_row();

    $notice_count = (int)($row[0] ?? 0);

  }

}



// Reports count (optional table)

$reports_count = 0;

if ($res = $conn->query("SHOW TABLES LIKE 'reports'")) {

  if ($res->num_rows > 0) {

    $row = $conn->query("SELECT COUNT(*) FROM reports")->fetch_row();

    $reports_count = (int)($row[0] ?? 0);

  }

}



// Contracts count maps to contacts table in this app

$contract_count = $contact_count;



// Budget count (try 'budget' then 'budgets')

$budget_count = 0;

$budgetTbl = null;

if ($res = $conn->query("SHOW TABLES LIKE 'budget'")) { if ($res->num_rows > 0) { $budgetTbl = 'budget'; } }

if (!$budgetTbl) { if ($res = $conn->query("SHOW TABLES LIKE 'budgets'")) { if ($res->num_rows > 0) { $budgetTbl = 'budgets'; } } }

if ($budgetTbl) {

  $row = $conn->query("SELECT COUNT(*) FROM $budgetTbl")->fetch_row();

  $budget_count = (int)($row[0] ?? 0);

}



  // Open contact messages count

  $open_messages_count = 0;

  if ($res = $conn->query("SHOW TABLES LIKE 'contact_messages'")) {

    if ($res->num_rows > 0) {

      $row = $conn->query("SELECT COUNT(*) FROM contact_messages WHERE status='Open'")->fetch_row();

      $open_messages_count = (int)($row[0] ?? 0);

    }

  }



  // Pending UPI reviews count

  $pending_upi = 0;

  if ($res = $conn->query("SHOW TABLES LIKE 'upi_payments'")) {

    if ($res->num_rows > 0) {

      $row = $conn->query("SELECT COUNT(*) FROM upi_payments WHERE status='Pending'")->fetch_row();

      $pending_upi = (int)($row[0] ?? 0);

    }

  }



// Get user info from session

// Prefer admin name from DB if available

$user_name = isset($admin['name']) && $admin['name'] ? $admin['name'] : ($_SESSION['name'] ?? 'Admin');

$user_photo = isset($admin['photo']) && $admin['photo'] ? $admin['photo'] : ($_SESSION['photo'] ?? 'https://randomuser.me/api/portraits/men/1.jpg');

?>



  <div class="dashboard-welcome">

    <div class="emoji">👋</div>

    <div>

      <div class="title">Welcome back, <?php echo htmlspecialchars($user_name); ?>!</div>

    </div>

  </div>

  <div class="stats-row" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.2rem;padding:1rem 1rem 0.5rem 1rem;">

    <!-- Stat cards here (existing code) -->

  <a href="/samrat/admin/worker_attendance_history.php" class="stat-card" style="background: linear-gradient(90deg, #00b09b 60%, #96c93d 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-clock-history"></i></span>

    <div>

      <div class="stat-label">Attendance History</div>

      <div class="stat-value"><?= $attendance_count ?></div>

    </div>

  </div>

</a>



<a href="attendance_marking.php" class="stat-card" style="background: linear-gradient(90deg, #2193b0 60%, #6dd5ed 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-qr-code-scan"></i></span>

    <div>

      <div class="stat-label">Mark Attendance</div>

      <div class="stat-value"><i class="bi bi-plus-circle"></i></div>

    </div>

  </div>

</a>



<a href="services.php" class="stat-card" style="background: linear-gradient(90deg, #43cea2 60%, #185a9d 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-grid"></i></span>

    <div>

      <div class="stat-label">Services</div>

      <div class="stat-value"><?= $service_count ?></div>

    </div>

  </div>

</a>



<a href="workers.php" class="stat-card" style="background: linear-gradient(90deg, #ff512f 60%, #dd2476 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-people"></i></span>

    <div>

      <div class="stat-label">Workers</div>

      <div class="stat-value"><?= $worker_count ?></div>

    </div>

  </div>

</a>



<a href="customers.php" class="stat-card" style="background: linear-gradient(90deg, #36d1dc 60%, #5b86e5 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-person-lines-fill"></i></span>

    <div>

      <div class="stat-label">Customers</div>

      <div class="stat-value"><?= $customer_count ?></div>

    </div>

  </div>

</a>



<a href="reports.php" class="stat-card" style="background: linear-gradient(90deg, #1e3c72 60%, #2a5298 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-bar-chart-line"></i></span>

    <div>

      <div class="stat-label">Reports</div>

      <div class="stat-value"><?= $reports_count ?></div>

    </div>

  </div>

</a>



<a href="leave_management.php" class="stat-card" style="background: linear-gradient(90deg, #ff416c 60%, #ff4b2b 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-calendar-x"></i></span>

    <div>

      <div class="stat-label">Leave Management</div>

      <div class="stat-value"><?= $leave_count ?></div>

    </div>

  </div>

</a>



<a href="holidays.php" class="stat-card" style="background: linear-gradient(90deg, #7F00FF 60%, #E100FF 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-calendar-event"></i></span>

    <div>

      <div class="stat-label">Holidays</div>

      <div class="stat-value"><?= $holiday_count ?></div>

    </div>

  </div>

</a>



<a href="notice_management.php" class="stat-card" style="background: linear-gradient(90deg, #ff6a00 60%, #ee0979 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-megaphone"></i></span>

    <div>

      <div class="stat-label">Notice Management</div>

      <div class="stat-value"><?= $notice_count ?></div>

    </div>

  </div>

</a>



<a href="worker_payments_history.php" class="stat-card" style="background: linear-gradient(90deg, #f7971e 60%, #ffd200 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-currency-rupee"></i></span>

    <div>

      <div class="stat-label">Payments History</div>

      <div class="stat-value"><?= $worker_payments_count ?></div>

    </div>

  </div>

</a>



<a href="worker_make_payment.php" class="stat-card" style="background: linear-gradient(90deg, #f2994a 60%, #f2c94c 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-plus-circle"></i></span>

    <div>

      <div class="stat-label">Worker Payment</div>

      <div class="stat-value">+</div>

    </div>

  </div>

</a>



<a href="contract_status.php" class="stat-card" style="background: linear-gradient(90deg, #56ab2f 60%, #a8e063 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-file-text"></i></span>

    <div>

      <div class="stat-label">Contracts</div>

      <div class="stat-value"><?= $contract_count ?></div>

    </div>

  </div>

</a>



<a href="budget.php" class="stat-card" style="background: linear-gradient(90deg, #f7971e 60%, #ffd200 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-cash-coin"></i></span>

    <div>

      <div class="stat-label">Budget</div>

      <div class="stat-value"><?= $budget_count ?></div>

    </div>

  </div>

</a>



<a href="register_worker_face.php" class="stat-card" style="background: linear-gradient(90deg, #8E2DE2 60%, #4A00E0 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-person-badge"></i></span>

    <div>

      <div class="stat-label">Face Register</div>

      <div class="stat-value"><?= $face_register_count ?></div>

    </div>

  </div>

</a>



<a href="messages.php" class="stat-card" style="background: linear-gradient(90deg, #00c6ff 60%, #0072ff 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-chat-dots"></i></span>

    <div>

      <div class="stat-label">Messages</div>

      <div class="stat-value"><?= $open_messages_count ?></div>

    </div>

  </div>

</a>



<a href="upi_review.php" class="stat-card" style="background: linear-gradient(90deg, #11998e 60%, #38ef7d 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-qr-code"></i></span>

    <div>

      <div class="stat-label">UPI Reviews</div>

      <div class="stat-value"><?= $pending_upi ?></div>

    </div>

  </div>

</a>



<a href="manage_users.php" class="stat-card" style="background: linear-gradient(90deg, #7F7FD5 60%, #91EAE4 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-person-badge"></i></span>

    <div>

      <div class="stat-label">Attendance Users</div>

      <div class="stat-value"><?= $attendance_users_count ?></div>

    </div>

  </div>

</a>



<a href="worker_pdf_selector.php" class="stat-card" style="background: linear-gradient(90deg, #ff4b1f 60%, #ff9068 100%);text-decoration:none;">

  <div class="stat-inner">

    <span class="stat-icon"><i class="bi bi-filetype-pdf"></i></span>

    <div>

      <div class="stat-label">Worker PDF Report</div>

      <div class="stat-value"><i class="bi bi-arrow-right"></i></div>

    </div>

  </div>

</a>

</div>



  <!-- Three History Cards -->

  <div style="display:flex;gap:1.5rem;padding:1rem 2rem 2rem 2rem;flex-wrap:wrap;">

    <!-- Attendance History -->

    <div style="flex:1;min-width:300px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(45,62,110,0.12);padding:1.5rem;max-height:400px;overflow-y:auto;">

      <h5 style="font-weight:600;color:#2d3e6e;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">

        <i class="bi bi-calendar-check text-success"></i> Attendance History

      </h5>

      <div style="font-size:0.9rem;">

        <?php

        $today = date('Y-m-d');

        // Get all workers with their attendance status for today

        $all_workers = $conn->query("SELECT w.id, w.name, wa.status 

                                     FROM workers w 

                                     LEFT JOIN worker_attendance wa ON w.id = wa.worker_id AND wa.date='$today' 

                                     ORDER BY w.name ASC");

        if ($all_workers && $all_workers->num_rows > 0) {

          while ($row = $all_workers->fetch_assoc()) {

            if ($row['status'] == 'Present') {

              $status_badge = '<span style="background:#28a745;color:#fff;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.75rem;">Present</span>';

            } elseif ($row['status'] == 'Absent') {

              $status_badge = '<span style="background:#dc3545;color:#fff;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.75rem;">Absent</span>';

            } elseif ($row['status'] == 'Half Day') {

              $status_badge = '<span style="background:#ffc107;color:#000;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.75rem;">Half Day</span>';

            } else {

              $status_badge = '<span style="background:#6c757d;color:#fff;padding:0.2rem 0.4rem;border-radius:4px;font-size:0.75rem;">Not Marked</span>';

            }

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Status',html:'<div style=\'padding:0.7rem;border-bottom:1px solid #eee;display:flex;justify-content:space-between;align-items:center;\'></div>',showConfirmButton:false,timer:2000});</script>";

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Worker Name',html:'<div><strong>" . htmlspecialchars($row['name']) . "</strong><br><small style=\'color:#687887;\'>Today - " . date('d M Y') . "</small></div>',showConfirmButton:false,timer:2000});</script>";

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Status Badge',html:'<div style=\'text-align:right;\'>" . $status_badge . "</div>',showConfirmButton:false,timer:2000});</script>";

            echo '</div>';

          }

        } else {

          echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'warning',title:'No Workers',text:'No workers found.',showConfirmButton:false,timer:2000});</script>";

        }

        ?>

      </div>

    </div>

    

    <!-- Leave History -->

    <div style="flex:1;min-width:300px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(45,62,110,0.12);padding:1.5rem;max-height:400px;overflow-y:auto;">

      <h5 style="font-weight:600;color:#2d3e6e;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">

        <i class="bi bi-calendar-x text-danger"></i> Leave History

      </h5>

      <div style="font-size:0.9rem;">

        <?php

        $today = date('Y-m-d');

        // Check if worker_leaves table exists

        $table_check = $conn->query("SHOW TABLES LIKE 'worker_leaves'");

        if ($table_check && $table_check->num_rows > 0) {

          $leave_history = $conn->query("SELECT l.*, w.name FROM worker_leaves l JOIN workers w ON l.worker_id=w.id WHERE '$today' BETWEEN l.start_date AND l.end_date ORDER BY l.start_date DESC LIMIT 10");

          if ($leave_history && $leave_history->num_rows > 0) {

            while ($row = $leave_history->fetch_assoc()) {

              $status_color = $row['status'] == 'Approved' ? '#28a745' : ($row['status'] == 'Rejected' ? '#dc3545' : '#ffc107');

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Status',html:'<div style=\'padding:0.7rem;border-bottom:1px solid #eee;\'></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Status',html:'<div style=\'display:flex;justify-content:space-between;align-items:center;margin-bottom:0.3rem;\'></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Worker Name',html:'<strong>" . htmlspecialchars($row['name']) . "</strong>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Status',html:'<span style=\'background:" . $status_color . ";color:#fff;padding:0.2rem 0.5rem;border-radius:4px;font-size:0.75rem;\'>" . htmlspecialchars($row['status']) . "</span>',showConfirmButton:false,timer:2000});</script>";

              echo '</div>';

              echo '<small style="color:#687887;">' . date('d M', strtotime($row['start_date'])) . ' - ' . date('d M Y', strtotime($row['end_date'])) . '</small>';

              echo '</div>';

            }

          } else {

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Leave Status',text:'No one is on leave today.',showConfirmButton:false,timer:2000});</script>";

          }

        } else {

          echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'warning',title:'Leave Management',text:'Leave management not configured yet.',showConfirmButton:false,timer:2000});</script>";

        }

        ?>

      </div>

    </div>

    

    <!-- Holiday History -->

    <div style="flex:1;min-width:300px;background:#fff;border-radius:16px;box-shadow:0 4px 24px rgba(45,62,110,0.12);padding:1.5rem;max-height:400px;overflow-y:auto;">

      <h5 style="font-weight:600;color:#2d3e6e;margin-bottom:1rem;display:flex;align-items:center;gap:0.5rem;">

        <i class="bi bi-calendar-event text-primary"></i> Holiday History

      </h5>

      <div style="font-size:0.9rem;">

        <?php

        $today = date('Y-m-d');

        $next_25_days = date('Y-m-d', strtotime('+25 days'));

        

        // Check if holidays table exists

        $table_check = $conn->query("SHOW TABLES LIKE 'holidays'");

        if ($table_check && $table_check->num_rows > 0) {

          // Check for today's holiday

          $holiday_today = $conn->query("SELECT * FROM holidays WHERE holiday_date='$today' AND is_active='Yes'");

          if ($holiday_today && $holiday_today->num_rows > 0) {

            while ($row = $holiday_today->fetch_assoc()) {

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday',html:'<div style=\'padding:0.7rem;border:2px solid #ffc107;border-radius:8px;background:#fffbf0;margin-bottom:0.5rem;\'></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday',html:'<div style=\'display:flex;align-items:center;gap:0.5rem;margin-bottom:0.3rem;\'><i class=\'bi bi-star-fill text-warning\'></i><strong style=\'color:#2d3e6e;\'>Today\'s Holiday</strong></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday Name',html:'<div style=\'font-size:1.1rem;font-weight:600;color:#2d3e6e;\'>" . htmlspecialchars($row['holiday_name']) . "</div>',showConfirmButton:false,timer:2000});</script>";

              echo '<small style="color:#687887;">' . date('d M Y', strtotime($row['holiday_date'])) . '</small>';

              if ($row['description']) {

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday Description',html:'<div style=\'margin-top:0.3rem;font-size:0.85rem;color:#687887;\'>" . htmlspecialchars($row['description']) . "</div>',showConfirmButton:false,timer:2000});</script>";

              }

              echo '</div>';

            }

          } else {

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday',text:'No holiday today.',showConfirmButton:false,timer:2000});</script>";

          }

          

          // Show upcoming holidays in next 25 days

          $upcoming_holidays = $conn->query("SELECT * FROM holidays WHERE holiday_date > '$today' AND holiday_date <= '$next_25_days' AND is_active='Yes' ORDER BY holiday_date ASC");

          if ($upcoming_holidays && $upcoming_holidays->num_rows > 0) {

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Upcoming Holidays',html:'<div style=\'margin-top:1rem;padding-top:1rem;border-top:1px solid #eee;\'><strong style=\'color:#2d3e6e;font-size:0.9rem;\'>Upcoming Holidays (Next 25 Days)</strong></div>',showConfirmButton:false,timer:2000});</script>";

            while ($row = $upcoming_holidays->fetch_assoc()) {

              $days_away = (strtotime($row['holiday_date']) - strtotime($today)) / (60 * 60 * 24);

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday',html:'<div style=\'padding:0.5rem 0;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;\'></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday Name',html:'<div><strong style=\'font-size:0.9rem;\'>" . htmlspecialchars($row['holiday_name']) . "</strong></div>',showConfirmButton:false,timer:2000});</script>";

              if ($row['description']) {

                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday Description',html:'<br><small style=\'color:#999;\'>" . htmlspecialchars($row['description']) . "</small>',showConfirmButton:false,timer:2000});</script>";

              }

              echo '</div>';

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Holiday Date',html:'<div style=\'text-align:right;\'><small style=\'color:#687887;\'>" . date('d M Y', strtotime($row['holiday_date'])) . "</small></div>',showConfirmButton:false,timer:2000});</script>";

              echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Days Away',html:'<br><small style=\'color:#007bff;font-weight:600;\'>In " . intval($days_away) . " days</small></div>',showConfirmButton:false,timer:2000});</script>";

              echo '</div>';

            }

          } else {

            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'info',title:'Upcoming Holidays',text:'No upcoming holidays in next 25 days.',showConfirmButton:false,timer:2000});</script>";

          }

        } else {

          echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><script>Swal.fire({icon:'warning',title:'Holiday Management',text:'Holiday management not configured yet.',showConfirmButton:false,timer:2000});</script>";

        }

        ?>

      </div>

    </div>

    </div>



     <!-- Traffic Analytics Chart -->

  <div style="padding:0 1rem 1rem 1rem;">

    <div style="background:#fff;border-radius:16px;box-shadow:0 6px 28px rgba(45,62,110,0.12);padding:0.5rem 1rem;">

      <div class="d-flex justify-content-between align-items-center px-1 pt-2">

        <h5 style="margin:0;color:#2d3e6e;font-weight:700;">Website Traffic (Last 30 Days)</h5>

        <div class="d-flex align-items-center gap-2">

          <select id="trafficRange" class="form-select form-select-sm" style="width:auto;">

            <option value="7">7 days</option>

            <option value="30" selected>30 days</option>

            <option value="60">60 days</option>

            <option value="90">90 days</option>

          </select>

        </div>

      </div>

      <div style="width:100%;height:50vh;min-height:320px;">

        <canvas id="trafficChart" style="width:100%;height:100%;"></canvas>

      </div>

    </div>

  </div>



  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <script>

    (function(){

      const ctx = document.getElementById('trafficChart').getContext('2d');

      let chart;

      async function loadTraffic(days){

        try{

          const res = await fetch('analytics_data.php?days=' + days, { cache: 'no-store' });

          const data = await res.json();

          const labels = data.labels.map(d => new Date(d).toLocaleDateString());

          const gradient = ctx.createLinearGradient(0,0,0,300);

          gradient.addColorStop(0,'rgba(63,114,255,0.35)');

          gradient.addColorStop(1,'rgba(63,114,255,0.05)');

          const ds = {

            label: 'Visits',

            data: data.counts,

            fill: true,

            backgroundColor: gradient,

            borderColor: '#3f72ff',

            tension: 0.35,

            pointRadius: 2,

            pointHoverRadius: 4

          };

          const config = {

            type: 'line',

            data: { labels, datasets: [ds] },

            options: {

              responsive: true,

              maintainAspectRatio: false,

              plugins: {

                legend: { display: false },

                tooltip: { mode: 'index', intersect: false }

              },

              interaction: { mode: 'index', intersect: false },

              scales: {

                x: { grid: { display:false } },

                y: { beginAtZero:true, grid: { color:'#eef2ff' } }

              }

            }

          };

          if(chart){ chart.destroy(); }

          chart = new Chart(ctx, config);

        }catch(e){ console.error('Traffic load failed', e); }

      }

      const rangeSel = document.getElementById('trafficRange');

      rangeSel.addEventListener('change', ()=> loadTraffic(rangeSel.value));

      loadTraffic(rangeSel.value);

      // Resize handling (Chart.js handles natively)

    })();

  </script>

    <!-- Footer Include -->

    <?php include 'downfooter.php'; ?>

  </div>

  

</body>

</html>