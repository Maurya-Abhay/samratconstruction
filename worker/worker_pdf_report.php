<?php
// Note: Assuming $conn, 'header.php', 'footer.php', and database library are correctly included/available.
// Mock data structure based on the image for demonstration. Replace with actual DB calls.
require_once __DIR__ . '/../admin/lib_common.php';

if (!isset($_SESSION['worker_id'])) {
    header('Location: login');
    exit();
}

$worker_id = $_SESSION['worker_id'];
date_default_timezone_set('Asia/Kolkata');

// --- Input Validation and Filtering (Same as before) ---
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '01 Nov 2025'; // Mock date
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '28 Nov 2025'; // Mock date

function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

if (isset($_GET['start_date']) && !validateDate($_GET['start_date'])) $start_date = null;
else if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];

if (isset($_GET['end_date']) && !validateDate($_GET['end_date'])) $end_date = null;
else if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];


// --- Fetch worker info (Same as before) ---
$worker_stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$worker_stmt->bind_param('i', $worker_id);
$worker_stmt->execute();
$worker = $worker_stmt->get_result()->fetch_assoc();
$worker_stmt->close();

if (!$worker) {
    echo '<div class="p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg max-w-lg mx-auto mt-10">Worker not found!</div>';
    exit();
}

// --- Dynamic Query Execution for Attendance (Modified to count half days) ---
$attendance_sql = "SELECT date, status, notes FROM worker_attendance WHERE worker_id=?";
$params = [$worker_id];
$types = "i";

if ($start_date && $end_date && validateDate($start_date) && validateDate($end_date)) {
    $attendance_sql .= " AND date BETWEEN ? AND ?";
    array_push($params, $start_date, $end_date);
    $types .= "ss";
}
$attendance_sql .= " ORDER BY date DESC";

$attendance_stmt = $conn->prepare($attendance_sql);
$attendance_stmt->bind_param($types, ...$params);
$attendance_stmt->execute();
$attendance_result = $attendance_stmt->get_result();

$attendance_records = [];
// Initialize summary counts including Half Days
$summary = ['Present' => 0, 'Absent' => 0, 'Leave' => 0, 'Half Day' => 0];
if ($attendance_result && $attendance_result->num_rows > 0) {
    while ($row = $attendance_result->fetch_assoc()) {
        $attendance_records[] = $row;
        // Check for 'Half Day' status explicitly
        $status = $row['status'];
        if ($status === 'Half Day') {
            $summary['Half Day']++;
        } elseif (isset($summary[$status])) {
             $summary[$status]++;
        }
    }
}
$attendance_stmt->close();


// --- Dynamic Query Execution for Payments (Same as before) ---
$payments_sql = "SELECT amount, payment_date, remarks FROM worker_payments WHERE worker_id=?";
$params_pay = [$worker_id];
$types_pay = "i";

if ($start_date && $end_date && validateDate($start_date) && validateDate($end_date)) {
    $payments_sql .= " AND payment_date BETWEEN ? AND ?";
    array_push($params_pay, $start_date, $end_date);
    $types_pay .= "ss";
}
$payments_sql .= " ORDER BY payment_date DESC";

$payments_stmt = $conn->prepare($payments_sql);
$payments_stmt->bind_param($types_pay, ...$params_pay);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();

$payment_records = [];
$total_paid = 0;
if ($payments_result && $payments_result->num_rows > 0) {
    while ($row = $payments_result->fetch_assoc()) {
        $payment_records[] = $row;
        $total_paid += floatval($row['amount']);
    }
}
$payments_stmt->close();


// --- Calculate Financial Summary ---
$daily_wage = floatval($worker['salary'] ?? 0);
// Calculation: (Present Days + (Half Days * 0.5)) * Daily Wage
$total_earning_days = $summary['Present'] + ($summary['Half Day'] * 0.5);
$total_earned = $total_earning_days * $daily_wage;
$remaining_due = $total_earned - $total_paid;


// --- Worker Photo Path ---
$photoExists = false;
$photoPath = '';
if (!empty($worker['photo'])) {
    $photoPath = '../admin/' . (strpos($worker['photo'], 'uploads/') === 0 ? $worker['photo'] : 'uploads/' . $worker['photo']);
    // Check server side for file existence for web view
    $photoExists = !empty($worker['photo']) && file_exists('../admin/' . (strpos($worker['photo'], 'uploads/') === 0 ? $worker['photo'] : 'uploads/' . $worker['photo']));
}

// --- Format Dates for Header ---
$period_start = date('d M Y', strtotime($start_date ?? '01 Nov 2025'));
$period_end = date('d M Y', strtotime($end_date ?? '28 Nov 2025'));
$generated_time = date('d Nov Y, h:i A'); // Mock generation time
?>
<?php include 'header.php'; // Include header markup ?>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f1f5f9; /* Slate 100 */
    }
    .report-container {
        max-width: 900px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
        border-radius: 16px;
        overflow: hidden;
    }
    .worker-info-box {
        background-color: #f8fafc; /* Slate 50 */
        border-bottom: 2px solid #e2e8f0;
    }
    .worker-photo {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #3b82f6; /* Blue 500 */
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .summary-card-lg {
        padding: 1.5rem 1rem;
        border-radius: 12px;
        color: white;
        font-weight: 800;
        text-align: center;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.2s;
    }
    .summary-card-lg h2 {
        font-size: 0.9rem;
        font-weight: 600;
        opacity: 0.8;
        margin-bottom: 0.5rem;
    }
    .summary-card-lg span {
        font-size: 1.75rem; /* Large font for amount */
        display: block;
    }
    /* Specific Large Card Styles */
    .card-earned { background-color: #4f46e5; /* Indigo 600 */ }
    .card-paid { background-color: #10b981; /* Emerald 500 */ }
    .card-due { 
        background-color: #f59e0b; /* Amber 500 */ 
        color: #3f3f46; /* Dark text for contrast */
    }
    .card-due.negative { background-color: #ef4444; /* Red 500 */ }

    /* Small Attendance Cards */
    .summary-card-sm {
        padding: 1rem;
        border-radius: 12px;
        text-align: center;
        background-color: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    .summary-card-sm span {
        font-size: 1.5rem;
        font-weight: 900;
        display: block;
        margin-top: 0.25rem;
    }
    .status-badge {
        padding: 0.3rem 0.75rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        display: inline-block;
    }
    .badge-Present { background-color: #d1fae5; color: #059669; }
    .badge-Absent { background-color: #fee2e2; color: #dc2626; }
    .badge-Leave { background-color: #fef9c3; color: #a16207; }
    .badge-HalfDay { background-color: #bfdbfe; color: #1d4ed8; }

    /* Table Styles */
    .data-table th { background-color: #f3f4f6; color: #4b5563; font-weight: 700; }
    .data-table td { color: #374151; font-size: 0.875rem; }
    
    /* PDF Button Style */
    #downloadPdfBtn {
        transition: background-color 0.2s, transform 0.1s;
    }
    #downloadPdfBtn:hover {
        background-color: #3b82f6;
    }

    /* Print/PDF-specific styling */
    @media print {
        #downloadPdfBtn, .filter-form { display: none !important; }
        .report-container { box-shadow: none !important; border-radius: 0 !important; max-width: none !important; }
        body { background-color: white !important; padding: 0 !important; margin: 0 !important; }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>

<div class="p-2 md:p-8">
    <div class="bg-white report-container mx-auto" id="reportContent">

        <div class="p-4 border-b border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center">
                <div class="flex items-center gap-3 mb-2 sm:mb-0">
                    <a href="attendance.php" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-indigo-100 text-indigo-600 hover:bg-indigo-200 transition" title="Back">
                        <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    </a>
                    <h1 class="text-2xl font-black text-indigo-600 tracking-wider">SAMRAT CONSTRUCTION</h1>
                </div>
                <button id="downloadPdfBtn" class="bg-blue-600 text-white font-semibold py-2 px-4 rounded-lg flex items-center gap-1 shadow-md hover:bg-blue-700 w-full sm:w-auto">
                    <i data-lucide="file-text" class="w-4 h-4"></i> Download PDF
                </button>
            </div>
            <div class="text-xs text-gray-500 mt-2 flex justify-between">
                <span>Worker Performance Report</span>
                <div>
                    <span class="mr-3">Generated: <?= $generated_time ?> IST</span>
                    <span>Period: <?= $period_start ?> to <?= $period_end ?></span>
                </div>
            </div>
        </div>
        
        <form method="GET" class="filter-form p-4 border-b border-gray-100 flex flex-col md:flex-row gap-4 items-end bg-gray-50">
            <div class="w-full md:w-auto flex-grow">
                <label for="start_date" class="block text-sm font-medium text-gray-600 mb-1">Start Date</label>
                <input type="date" name="start_date" id="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($start_date) ?>">
            </div>
            <div class="w-full md:w-auto flex-grow">
                <label for="end_date" class="block text-sm font-medium text-gray-600 mb-1">End Date</label>
                <input type="date" name="end_date" id="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-md" value="<?= htmlspecialchars($end_date) ?>">
            </div>
            <div class="w-full md:w-auto">
                <button type="submit" class="w-full md:w-auto bg-indigo-500 hover:bg-indigo-600 text-white font-bold py-2 px-6 rounded-md">
                    <i data-lucide="filter" class="w-4 h-4 inline mr-1"></i> Filter Report
                </button>
            </div>
        </form>

        <div class="worker-info-box p-6 flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <div class="flex-shrink-0">
                <?php if ($photoExists): ?>
                    <img src="<?= htmlspecialchars($photoPath) ?>" alt="Worker Photo" class="worker-photo" />
                <?php else: ?>
                    <div class="worker-photo flex items-center justify-center text-gray-400 text-4xl bg-white border-dashed">
                        <i data-lucide="user" class="w-8 h-8"></i>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="grid grid-cols-2 gap-y-2 gap-x-8 text-sm w-full">
                <div><span class="font-semibold text-gray-600 block">NAME</span><span class="text-lg font-bold text-gray-800"><?= htmlspecialchars($worker['name']) ?></span></div>
                <div><span class="font-semibold text-gray-600 block">PHONE</span><span class="text-lg font-bold text-gray-800"><?= htmlspecialchars($worker['phone']) ?></span></div>
                <div><span class="font-semibold text-gray-600 block">DAILY WAGE</span><span class="text-lg font-bold text-green-600">₹<?= number_format($daily_wage, 2) ?></span></div>

                <div><span class="font-semibold text-gray-600 block">ADDRESS</span><span class="text-gray-700"><?= htmlspecialchars($worker['address'] ?? '-') ?></span></div>
                <div><span class="font-semibold text-gray-600 block">AADHAAR</span><span class="text-gray-700"><?= htmlspecialchars($worker['aadhaar'] ?? '-') ?></span></div>
                <div><span class="font-semibold text-gray-600 block">STATUS</span><span class="text-gray-700"><?= htmlspecialchars($worker['status'] ?? 'Active') ?></span></div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="summary-card-lg card-earned">
                <h2>TOTAL EARNED</h2>
                <span>₹<?= number_format($total_earned, 2) ?></span>
            </div>
            <div class="summary-card-lg card-paid">
                <h2>TOTAL PAID</h2>
                <span>₹<?= number_format($total_paid, 2) ?></span>
            </div>
            <div class="summary-card-lg card-due <?= $remaining_due < 0 ? 'negative' : '' ?>">
                <h2>BALANCE DUE</h2>
                <?php $display_due = abs($remaining_due); ?>
                <span>
                    <?= $remaining_due < 0 ? '-₹' : '₹' ?><?= number_format($display_due, 2) ?>
                </span>
                <p class="text-xs mt-1 font-semibold opacity-90">
                    <?= $remaining_due < 0 ? 'Worker has taken ADVANCE payment.' : 'Remaining amount due to worker.' ?>
                </p>
            </div>
        </div>

        <div class="px-6 pb-6 grid grid-cols-3 gap-4">
            <div class="summary-card-sm">
                <span class="text-gray-500 font-medium">Present Days</span>
                <span class="text-green-600"><?= $summary['Present'] ?></span>
            </div>
            <div class="summary-card-sm">
                <span class="text-gray-500 font-medium">Half Days</span>
                <span class="text-blue-600"><?= $summary['Half Day'] ?></span>
            </div>
            <div class="summary-card-sm">
                <span class="text-gray-500 font-medium">Absent Days</span>
                <span class="text-red-600"><?= $summary['Absent'] ?></span>
            </div>
        </div>

        <div class="p-6 pt-0 flex flex-col md:flex-row gap-6">
            
            <div class="w-full md:w-1/2">
                <h5 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2"><i data-lucide="calendar-check" class="w-5 h-5 text-indigo-500"></i> Attendance Log</h5>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 shadow-sm data-table">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/4">Date</th>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/4">Status</th>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/2">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (count($attendance_records) > 0): foreach($attendance_records as $row): ?>
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-900"><?= htmlspecialchars($row['date']) ?></td>
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="status-badge badge-<?= str_replace(' ', '', $row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                                <td class="px-3 py-2 text-gray-500 truncate max-w-xs"><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3" class="px-3 py-4 text-center text-sm italic text-gray-500">No attendance records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="w-full md:w-1/2">
                <h5 class="text-lg font-bold text-gray-800 mb-3 flex items-center gap-2"><i data-lucide="dollar-sign" class="w-5 h-5 text-indigo-500"></i> Payment History</h5>
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 shadow-sm data-table">
                        <thead>
                            <tr>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/4">Date</th>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/4">Amount</th>
                                <th class="px-3 py-3 text-left text-xs uppercase tracking-wider w-1/2">Remarks</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-100">
                            <?php if (count($payment_records) > 0): foreach($payment_records as $row): ?>
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap text-gray-900"><?= htmlspecialchars($row['payment_date']) ?></td>
                                <td class="px-3 py-2 whitespace-nowrap font-bold text-green-600">₹<?= number_format($row['amount'], 2) ?></td>
                                <td class="px-3 py-2 text-gray-500 truncate max-w-xs"><?= htmlspecialchars($row['remarks'] ?? '-') ?></td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3" class="px-3 py-4 text-center text-sm italic text-gray-500">No payment records found.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="border-t border-gray-200 pt-3 pb-4 px-6 text-center text-xs text-gray-500">
            This is a computer-generated report. | **Samrat Construction Pvt. Ltd.**
        </div>

    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.getElementById('downloadPdfBtn').addEventListener('click', () => {
    // Hide controls before generating PDF
    const controls = document.querySelectorAll('.filter-form, #downloadPdfBtn');
    controls.forEach(el => el.style.display = 'none');

    const element = document.getElementById('reportContent');
    
    // Optimized options for better PDF quality and layout
    const opt = {
        margin: [0.5, 0.4, 0.5, 0.4], 
        filename: 'worker_report_<?= $worker_id ?>_<?= date('YmdHis') ?>.pdf',
        image: { type: 'jpeg', quality: 0.95 },
        html2canvas: { 
            scale: 3, 
            logging: false, 
            dpi: 300, 
            letterRendering: true 
        },
        jsPDF: { 
            unit: 'in', 
            format: 'a4', 
            orientation: 'portrait' 
        }
    };
    
    // Generate and save the PDF
    html2pdf().set(opt).from(element).save().then(() => {
        // Show controls again after PDF generation
        controls.forEach(el => el.style.display = '');
    });
});
</script>
<?php include 'footer.php'; // Include footer markup ?>