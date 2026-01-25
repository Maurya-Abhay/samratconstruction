<?php
// holidays.php - Client/Contact Holiday View

session_start();

require_once '../admin/database.php';

@include_once __DIR__ . '/../admin/analytics_track.php';

// --- Security Check ---
if (!isset($_SESSION['contact_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

// --- Database Table Check/Creation ---
$conn->query("
    CREATE TABLE IF NOT EXISTS holidays (
        id INT AUTO_INCREMENT PRIMARY KEY,
        holiday_name VARCHAR(255) NOT NULL,
        holiday_date DATE NOT NULL,
        description TEXT,
        is_active ENUM('Yes','No') DEFAULT 'Yes',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");


// --- Data Retrieval ---
$today = date('Y-m-d');

// Fetch ALL active holidays (for the list)
$holidays = $conn->query("SELECT * FROM holidays WHERE is_active='Yes' ORDER BY holiday_date ASC");

// Fetch the NEXT upcoming holiday (for the prominent display)
$next_holiday_res = $conn->query("SELECT * FROM holidays WHERE holiday_date >= '$today' AND is_active='Yes' ORDER BY holiday_date ASC LIMIT 1");
$next_holiday = $next_holiday_res ? $next_holiday_res->fetch_assoc() : null;

$days_until_next = null;
if ($next_holiday) {
    try {
        $nd = new DateTime($next_holiday['holiday_date']);
        $td = new DateTime($today);
        $diff = $td->diff($nd);
        $days_until_next = $diff->days;
    } catch (Exception $e) {
        // Handle date error gracefully
        $days_until_next = null; 
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="../admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Holidays</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background:#f7f9fc; padding-top: 70px; }
        .next-box { 
            /* Modern, vibrant gradient */
            background: linear-gradient(135deg, #1e3c72, #2a5298); 
            color:#fff; 
            border-radius:16px; 
            padding:2rem; 
            text-align:center; 
            margin-bottom:1.75rem; 
            box-shadow: 0 10px 25px rgba(30, 60, 114, 0.4);
        }
        .badge-soft { 
            background: rgba(255,255,255,0.9); 
            color:#1e3c72; 
            border-radius:12px; 
            padding:.5rem 1rem; 
            font-weight: 700;
        }
        .holiday-card { 
            background:#fff; 
            border-radius:12px; 
            box-shadow:0 4px 12px rgba(0,0,0,.08); 
            padding:1.25rem; 
            margin-bottom:1rem; 
        }
    </style>
</head>
<body>

<?php 
// Assuming header.php handles fixed navigation bar and needs these vars
$contact_show_back_btn = true; 
$contact_back_href = 'dashboard.php'; 
include __DIR__ . '/header.php'; 
?>

<div class="container py-4">
    <h2 class="mb-4 text-primary fw-bold"><i class="bi bi-calendar2-heart me-2"></i> Company Holiday Calendar</h2>

    <?php if ($next_holiday): ?>
        <div class="next-box">
            <h4 class="mb-1 text-uppercase opacity-75">Your Next Day Off</h4>
            <div class="display-6 fw-bold mb-1"><?= htmlspecialchars($next_holiday['holiday_name']) ?></div>
            <div class="fs-5 opacity-85 mb-3"><?= date('l, F j, Y', strtotime($next_holiday['holiday_date'])) ?></div>
            
            <div class="badge-soft d-inline-block shadow-sm">
                <?php if ($days_until_next === 0): ?><i class="bi bi-gift-fill me-1"></i> Today!
                <?php elseif ($days_until_next === 1): ?><i class="bi bi-arrow-right-short me-1"></i> Tomorrow
                <?php else: ?><i class="bi bi-clock-history me-1"></i> In **<?= $days_until_next ?>** days<?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info">No upcoming holidays scheduled. Keep up the great work!</div>
    <?php endif; ?>

    <h3 class="mt-4 mb-3 text-secondary"><i class="bi bi-list-task me-2"></i> All Active Holidays</h3>

    <?php if ($holidays && $holidays->num_rows): ?>
        <div class="row">
            <?php while ($h = $holidays->fetch_assoc()): ?>
                <?php
                    $dt = new DateTime($h['holiday_date']);
                    $is_today = $h['holiday_date'] == $today;
                    $is_upcoming = $h['holiday_date'] > $today;
                    
                    $border_class = $is_today ? 'border-success' : ($is_upcoming ? 'border-primary' : 'border-secondary');
                    $badge_class = $is_today ? 'bg-success' : ($is_upcoming ? 'bg-primary' : 'bg-secondary');
                    $status_text = $is_today ? 'Today' : ($is_upcoming ? 'Upcoming' : 'Past');
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="holiday-card border-start <?= $border_class ?> border-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="fw-bold mb-1"><?= htmlspecialchars($h['holiday_name']) ?></div>
                                <div class="text-muted small">
                                    <i class="bi bi-calendar me-1"></i>
                                    **<?= $dt->format('l, d M Y') ?>**
                                </div>
                                <?php if (!empty($h['description'])): ?>
                                    <div class="small mt-2 text-dark opacity-75"><?= htmlspecialchars($h['description']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="ms-3 flex-shrink-0">
                                <span class="badge <?= $badge_class ?> fw-normal"><?= $status_text ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-secondary">No active holidays are currently scheduled in the system.</div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>