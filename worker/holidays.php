<?php
$page_title = "Holidays";
$show_back_btn = true;
include 'header.php';

if (!$worker_id) {
    header('Location: login.php');
    exit();
}

require_once '../admin/database.php';

// Create holidays table if it doesn't exist
$conn->query("CREATE TABLE IF NOT EXISTS holidays (
    id INT AUTO_INCREMENT PRIMARY KEY,
    holiday_name VARCHAR(255) NOT NULL,
    holiday_date DATE NOT NULL,
    description TEXT,
    is_active ENUM('Yes', 'No') DEFAULT 'Yes',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default Indian holidays if table is empty
$holiday_count = $conn->query("SELECT COUNT(*) as count FROM holidays")->fetch_assoc()['count'];
if ($holiday_count == 0) {
    $default_holidays = [
        ['New Year’s Day', '2025-01-01', 'Start of the year'],
    ['Republic Day', '2025-01-26', 'India celebrates Republic Day'],
    ['Lohri', '2025-01-13', 'Festival in North India'],
    ['Makar Sankranti', '2025-01-14', 'Sun enters Capricorn'],
    ['Holi', '2025-03-14', 'Festival of Colors'],
    ['Good Friday', '2025-04-18', 'Christian holiday'],
    ['Eid al‑Fitr', '2025-04-10', 'End of Ramadan'],
    ['Eid al‑Adha', '2025-06-17', 'Festival of Sacrifice'],
    ['Independence Day', '2025-08-15', 'India’s Independence Day'],
    ['Janmashtami', '2025-08-19', 'Birthday of Lord Krishna'],
    ['Ganesh Chaturthi', '2025-09-02', 'Ganesh festival'],
    ['Gandhi Jayanti', '2025-10-02', 'Mahatma Gandhi’s Birthday'],
    ['Dussehra (Vijayadashami)', '2025-10-22', 'Victory of good over evil'],
    // Chhath Puja (in Bihar / regions where it is observed)
    ['Chhath Puja – Nahay Khay', '2025-10-25', 'First day – bathing and preparation'],  
    ['Chhath Puja – Kharna', '2025-10-26', 'Second day – fasting and offerings'],  
    ['Chhath Puja – Sandhya Arghya (Evening offering)', '2025-10-27', 'Offerings to setting sun'],  
    ['Chhath Puja – Usha Arghya / Parana', '2025-10-28', 'Morning offering and breaking fast'],  
    // Diwali and related
    ['Dhanteras', '2025-10-18', 'First day of Diwali festival'],  
    ['Naraka Chaturdashi (Choti Diwali)', '2025-10-20', 'Pre‑Diwali observance'],  
    ['Diwali (Lakshmi Puja)', '2025-10-21', 'Main Diwali day – worship of Goddess Lakshmi'],  
    ['Govardhan Puja', '2025-10-22', 'Worship of Govardhan Hill'],  
    ['Bhai Dooj', '2025-10-23', 'Brother‑Sister festival'],  
    ['Karva Chauth', '2025-11-15', 'Fasting festival for married women'],  
    ['Christmas', '2025-12-25', 'Christmas celebration'],
];
    
    foreach ($default_holidays as $holiday) {
        $name = mysqli_real_escape_string($conn, $holiday[0]);
        $date = $holiday[1];
        $desc = mysqli_real_escape_string($conn, $holiday[2]);
        $conn->query("INSERT INTO holidays (holiday_name, holiday_date, description) VALUES ('$name', '$date', '$desc')");
    }
}

// Fetch all active holidays
$holidays = $conn->query("SELECT * FROM holidays WHERE is_active='Yes' ORDER BY holiday_date ASC");

// Find next upcoming holiday
$today = date('Y-m-d');
$next_holiday = $conn->query("SELECT * FROM holidays WHERE holiday_date >= '$today' AND is_active='Yes' ORDER BY holiday_date ASC LIMIT 1")->fetch_assoc();

// Count holidays by status
$total_holidays = $conn->query("SELECT COUNT(*) as count FROM holidays WHERE is_active='Yes'")->fetch_assoc()['count'];
$upcoming_holidays = $conn->query("SELECT COUNT(*) as count FROM holidays WHERE holiday_date >= '$today' AND is_active='Yes'")->fetch_assoc()['count'];
$past_holidays = $conn->query("SELECT COUNT(*) as count FROM holidays WHERE holiday_date < '$today' AND is_active='Yes'")->fetch_assoc()['count'];

// Calculate days until next holiday
$days_until_next = null;
if ($next_holiday) {
    $next_date = new DateTime($next_holiday['holiday_date']);
    $today_date = new DateTime($today);
    $days_until_next = $today_date->diff($next_date)->days;
    if ($next_holiday['holiday_date'] == $today) {
        $days_until_next = 0;
    }
}
?>

<style>
    .holiday-card {
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 4px 16px rgba(0,0,0,0.08);
        overflow: hidden;
    }
    .holiday-header {
        background: linear-gradient(135deg, #ff6b6b, #ffa500);
        color: white;
        padding: 1.5rem;
        text-align: center;
    }
    .summary-card {
        border-radius: 12px;
        transition: transform 0.3s ease;
        border: none;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .summary-card:hover {
        transform: translateY(-5px);
    }
    .holiday-icon {
        font-size: 3rem;
        margin-bottom: 0.5rem;
    }
    .next-holiday-box {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 16px;
        padding: 2rem;
        text-align: center;
        margin-bottom: 2rem;
        position: relative;
        overflow: hidden;
    }
    .next-holiday-box::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="3" fill="white" opacity="0.1"/><circle cx="40" cy="60" r="1" fill="white" opacity="0.1"/></svg>');
        animation: float 20s infinite linear;
    }
    @keyframes float {
        0% { transform: translateX(-50%) translateY(-50%) rotate(0deg); }
        100% { transform: translateX(-50%) translateY(-50%) rotate(360deg); }
    }
    .holiday-list-item {
        background: #fff;
        border-radius: 12px;
        padding: 1.2rem;
        margin-bottom: 1rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        border-left: 4px solid #transparent;
    }
    .holiday-list-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }
    .holiday-today {
        border-left: 4px solid #28a745;
        background: linear-gradient(90deg, #d4edda, #fff);
    }
    .holiday-upcoming {
        border-left: 4px solid #007bff;
    }
    .holiday-past {
        border-left: 4px solid #6c757d;
        opacity: 0.7;
    }
    .holiday-date {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.5rem;
        text-align: center;
        min-width: 80px;
    }
    .countdown {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 1rem 0;
    }
    .festival-emoji {
        font-size: 2rem;
        margin-right: 0.5rem;
    }
</style>

<div class="container py-3">
    <!-- Next Holiday Highlight -->
    <?php if ($next_holiday): ?>
        <div class="next-holiday-box">
            <div style="position: relative; z-index: 1;">
                <span class="festival-emoji"><?= getHolidayEmoji($next_holiday['holiday_name']) ?></span>
                <h3 class="mb-2"><?= htmlspecialchars($next_holiday['holiday_name']) ?></h3>
                <div class="countdown">
                    <?php if ($days_until_next == 0): ?>
                        🎉 Today! 🎉
                    <?php elseif ($days_until_next == 1): ?>
                        Tomorrow
                    <?php else: ?>
                        <?= $days_until_next ?> Days to Go
                    <?php endif; ?>
                </div>
                <p class="mb-0 opacity-75"><?= date('l, F j, Y', strtotime($next_holiday['holiday_date'])) ?></p>
                <small class="opacity-75"><?= htmlspecialchars($next_holiday['description']) ?></small>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
            <h5>No Upcoming Holidays</h5>
            <p class="mb-0">No holidays are scheduled for the rest of the year.</p>
        </div>
    <?php endif; ?>

    <!-- Holiday Statistics -->
    <div class="holiday-card mb-4">
        <div class="holiday-header">
            <i class="bi bi-calendar-event holiday-icon"></i>
            <h3 class="mb-1">Holiday Overview</h3>
            <p class="mb-0 opacity-75">Company holiday calendar and celebrations</p>
        </div>
        
        <div class="p-4">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="card summary-card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-check fs-1 mb-2"></i>
                            <h6 class="card-title">Total Holidays</h6>
                            <div class="fs-3 fw-bold"><?= $total_holidays ?></div>
                            <small>This year</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-plus fs-1 mb-2"></i>
                            <h6 class="card-title">Upcoming</h6>
                            <div class="fs-3 fw-bold"><?= $upcoming_holidays ?></div>
                            <small>Holidays ahead</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card summary-card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="bi bi-calendar-x fs-1 mb-2"></i>
                            <h6 class="card-title">Completed</h6>
                            <div class="fs-3 fw-bold"><?= $past_holidays ?></div>
                            <small>Past holidays</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Holiday List -->
    <div class="card shadow-sm">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Holidays</h5>
        </div>
        <div class="card-body">
            <?php if ($holidays && $holidays->num_rows > 0): ?>
                <div class="row">
                    <?php while($holiday = $holidays->fetch_assoc()): 
                        $holiday_date = new DateTime($holiday['holiday_date']);
                        $is_today = $holiday['holiday_date'] == $today;
                        $is_upcoming = $holiday['holiday_date'] > $today;
                        $is_past = $holiday['holiday_date'] < $today;
                    ?>
                        <div class="col-md-6 mb-3">
                            <div class="holiday-list-item <?= $is_today ? 'holiday-today' : ($is_upcoming ? 'holiday-upcoming' : 'holiday-past') ?>">
                                <div class="d-flex align-items-center">
                                    <div class="holiday-date me-3">
                                        <div class="fw-bold text-primary"><?= $holiday_date->format('d') ?></div>
                                        <small class="text-muted"><?= $holiday_date->format('M') ?></small>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="festival-emoji" style="font-size: 1.5rem;"><?= getHolidayEmoji($holiday['holiday_name']) ?></span>
                                            <h6 class="mb-0 fw-bold"><?= htmlspecialchars($holiday['holiday_name']) ?></h6>
                                        </div>
                                        <p class="text-muted mb-1 small"><?= htmlspecialchars($holiday['description']) ?></p>
                                        <div class="d-flex align-items-center">
                                            <small class="text-muted me-2">
                                                <i class="bi bi-calendar me-1"></i><?= $holiday_date->format('l, F j, Y') ?>
                                            </small>
                                            <?php if ($is_today): ?>
                                                <span class="badge bg-success">Today</span>
                                            <?php elseif ($is_upcoming): ?>
                                                <span class="badge bg-primary">Upcoming</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Past</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="bi bi-calendar-x text-muted" style="font-size: 4rem;"></i>
                    <h5 class="text-muted mt-3">No Holidays Found</h5>
                    <p class="text-muted">No holidays have been added to the calendar yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Holiday Information -->
    <div class="alert alert-info mt-4">
        <h6><i class="bi bi-info-circle me-2"></i>Holiday Policy Information</h6>
        <ul class="mb-0">
            <li>All listed holidays are company-approved off days</li>
            <li>Some holidays may be optional based on your location and role</li>
            <li>Festival dates may vary based on lunar calendar</li>
            <li>Additional regional holidays may be announced separately</li>
        </ul>
    </div>
</div>

<?php
// Function to get emoji for different holidays
function getHolidayEmoji($holiday_name) {
    $holiday_lower = strtolower($holiday_name);
    
    if (strpos($holiday_lower, 'diwali') !== false) return '🪔';
    if (strpos($holiday_lower, 'holi') !== false) return '🌈';
    if (strpos($holiday_lower, 'christmas') !== false) return '🎄';
    if (strpos($holiday_lower, 'eid') !== false) return '🌙';
    if (strpos($holiday_lower, 'republic') !== false) return '🇮🇳';
    if (strpos($holiday_lower, 'independence') !== false) return '🇮🇳';
    if (strpos($holiday_lower, 'gandhi') !== false) return '🕊️';
    if (strpos($holiday_lower, 'dussehra') !== false) return '🏹';
    if (strpos($holiday_lower, 'good friday') !== false) return '✝️';
    if (strpos($holiday_lower, 'karva') !== false) return '🌙';
    if (strpos($holiday_lower, 'bhai dooj') !== false) return '👫';
    
    return '🎉'; // Default emoji
}
?>

<?php include 'footer.php'; ?>