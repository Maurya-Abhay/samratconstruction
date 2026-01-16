<?php
// worker_pdf_report.php

require_once 'lib_common.php';

// --- 1. Validation & Logic ---
if (!isset($_GET['id'], $_GET['start_date'], $_GET['end_date'])) {
    die('<div style="font-family:sans-serif;text-align:center;padding:50px;color:red;">Error: Missing Parameters.</div>');
}

date_default_timezone_set('Asia/Kolkata');

$worker_id = intval($_GET['id']);
$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

// --- 2. Fetch Data ---
// Worker
$stmt = $conn->prepare("SELECT * FROM workers WHERE id = ?");
$stmt->bind_param("i", $worker_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$worker) die("Worker not found.");

// Attendance
$att_stmt = $conn->prepare("SELECT * FROM worker_attendance WHERE worker_id = ? AND date BETWEEN ? AND ? ORDER BY date ASC");
$att_stmt->bind_param("iss", $worker_id, $start_date, $end_date);
$att_stmt->execute();
$attendance_data = $att_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$att_stmt->close();

$summary = ['Present' => 0, 'Absent' => 0, 'Half Day' => 0, 'Leave' => 0];
foreach ($attendance_data as $row) $summary[$row['status']]++;

// Payments
$pay_stmt = $conn->prepare("SELECT * FROM worker_payments WHERE worker_id = ? AND payment_date BETWEEN ? AND ? ORDER BY payment_date ASC");
$pay_stmt->bind_param("iss", $worker_id, $start_date, $end_date);
$pay_stmt->execute();
$payment_data = $pay_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$pay_stmt->close();

// Financials
$total_paid = 0;
foreach ($payment_data as $p) $total_paid += floatval($p['amount']);

$daily_wage = floatval($worker['salary'] ?? 0);
$total_earned = ($summary['Present'] * $daily_wage) + ($summary['Half Day'] * ($daily_wage / 2));
$balance_due = $total_earned - $total_paid;

$photoPath = !empty($worker['photo']) ? $worker['photo'] : 'assets/default-avatar.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statement - <?= htmlspecialchars($worker['name']) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <style>
        :root {
            --brand-color: #2563eb;   /* Professional Blue */
            --brand-dark: #1e3a8a;    /* Navy */
            --brand-accent: #f59e0b;  /* Amber */
            --text-main: #1f2937;
            --text-light: #6b7280;
            --bg-color: #f3f4f6;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            margin: 0;
            padding: 40px 0;
            -webkit-print-color-adjust: exact;
        }

        /* --- The Paper Sheet --- */
        .sheet {
            background: white;
            width: 210mm; /* A4 Width */
            min-height: 297mm; /* A4 Height */
            margin: 0 auto;
            padding: 40px 50px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            box-sizing: border-box;
        }

        /* --- Header Section --- */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }

        .brand h1 {
            font-size: 24px;
            font-weight: 800;
            color: var(--brand-dark);
            text-transform: uppercase;
            margin: 0;
            letter-spacing: -0.5px;
        }
        
        .brand p { margin: 5px 0 0; font-size: 12px; color: var(--text-light); }

        .report-meta { text-align: right; }
        .report-badge {
            background: var(--brand-color);
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 8px;
        }
        .meta-row { font-size: 12px; color: var(--text-main); margin-bottom: 4px; }
        .meta-row span { font-weight: 600; color: var(--text-light); margin-right: 5px; }

        /* --- Worker Identity Card --- */
        .worker-card {
            display: flex;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            align-items: center;
        }

        .worker-img {
            width: 80px;
            height: 80px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid white;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            margin-right: 25px;
        }

        .worker-details { flex: 1; }
        .worker-name { font-size: 20px; font-weight: 700; color: var(--text-main); margin: 0 0 5px 0; }
        .worker-role { font-size: 13px; color: var(--brand-color); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        
        .worker-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px dashed #cbd5e1;
        }
        
        .w-item label { font-size: 10px; text-transform: uppercase; color: var(--text-light); font-weight: 600; display: block; }
        .w-item div { font-size: 13px; font-weight: 600; color: var(--text-main); }

        /* --- Financial Dashboard --- */
        .finance-deck {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 35px;
        }

        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-card.highlight { background: #eff6ff; border-color: #bfdbfe; }
        .stat-card.due { background: #fef2f2; border-color: #fecaca; }

        .stat-label { font-size: 11px; text-transform: uppercase; color: var(--text-light); font-weight: 700; margin-bottom: 5px; }
        .stat-val { font-size: 18px; font-weight: 800; color: var(--text-main); }
        .stat-val.red { color: #dc2626; }
        .stat-val.green { color: #16a34a; }
        .stat-val.blue { color: var(--brand-color); }

        /* --- Tables Section --- */
        .sections-wrapper {
            display: flex;
            gap: 30px;
        }

        .section-col { flex: 1; }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-main);
            border-bottom: 2px solid var(--text-main);
            padding-bottom: 8px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modern-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .modern-table th {
            text-align: left;
            padding: 8px 10px;
            color: var(--text-light);
            font-weight: 600;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
        }

        .modern-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #f3f4f6;
            color: var(--text-main);
        }
        
        .modern-table tr:last-child td { border-bottom: none; }

        .status-dot {
            height: 8px; width: 8px; border-radius: 50%; display: inline-block; margin-right: 6px;
        }
        .dot-green { background-color: #16a34a; }
        .dot-red { background-color: #dc2626; }
        .dot-yellow { background-color: #ca8a04; }

        /* --- Footer --- */
        .footer {
            margin-top: 50px;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .signature-box {
            width: 150px;
            text-align: center;
        }
        .signature-line { border-bottom: 1px solid #000; margin-bottom: 5px; height: 40px; }
        .signature-text { font-size: 11px; font-weight: 600; text-transform: uppercase; }
        
        .company-fineprint {
            font-size: 10px;
            color: var(--text-light);
            max-width: 300px;
            line-height: 1.4;
        }

        /* --- UI Controls (Hidden in Print) --- */
        .ui-controls {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 999;
            display: flex;
            gap: 15px;
        }
        
        .btn {
            background: var(--text-main);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-family: 'Inter', sans-serif;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.2s;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .btn:hover { transform: translateY(-2px); }
        .btn-primary { background: var(--brand-color); }
        .btn-secondary { background: white; color: var(--text-main); }

        @media print {
            body { background: white; padding: 0; }
            .sheet { box-shadow: none; padding: 0; width: 100%; margin: 0; }
            .ui-controls { display: none !important; }
        }
    </style>
</head>
<body>

<div class="ui-controls">
    <a href="worker_pdf_selector.php" class="btn btn-secondary"><i class="fa fa-arrow-left"></i> Back</a>
    <button onclick="window.print()" class="btn btn-primary"><i class="fa fa-print"></i> Print / Save PDF</button>
</div>

<div class="sheet">
    
    <header class="header">
        <div class="brand">
            <h1>Samrat Construction</h1>
            <p>Pvt. Ltd. • Construction & Workforce Management</p>
            <p style="font-size: 11px; margin-top: 3px;">Reg. No: SC-2024-8842 • +91 98765 43210</p>
        </div>
        <div class="report-meta">
            <div class="report-badge">Worker Statement</div>
            <div class="meta-row"><span>Statement Date:</span> <?= date('d M Y') ?></div>
            <div class="meta-row"><span>Period From:</span> <?= date('d M Y', strtotime($start_date)) ?></div>
            <div class="meta-row"><span>Period To:</span> <?= date('d M Y', strtotime($end_date)) ?></div>
        </div>
    </header>

    <div class="worker-card">
        <img src="<?= htmlspecialchars($photoPath) ?>" class="worker-img" alt="Worker">
        <div class="worker-details">
            <h2 class="worker-name"><?= htmlspecialchars($worker['name']) ?></h2>
            <div class="worker-role">Registered Worker • ID #<?= str_pad($worker['id'], 4, '0', STR_PAD_LEFT) ?></div>
            
            <div class="worker-grid">
                <div class="w-item">
                    <label>Phone Number</label>
                    <div><?= htmlspecialchars($worker['phone']) ?></div>
                </div>
                <div class="w-item">
                    <label>Daily Wage</label>
                    <div>₹<?= number_format($daily_wage, 2) ?></div>
                </div>
                <div class="w-item">
                    <label>Joining Date</label>
                    <div><?= $worker['joining_date'] ? date('d M Y', strtotime($worker['joining_date'])) : 'N/A' ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="finance-deck">
        <div class="stat-card">
            <div class="stat-label">Total Working Days</div>
            <div class="stat-val"><?= $summary['Present'] + ($summary['Half Day'] * 0.5) ?></div>
        </div>
        <div class="stat-card highlight">
            <div class="stat-label">Total Earnings</div>
            <div class="stat-val blue">₹<?= number_format($total_earned) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Amount Paid</div>
            <div class="stat-val green">₹<?= number_format($total_paid) ?></div>
        </div>
        <div class="stat-card due">
            <div class="stat-label">Balance Due</div>
            <div class="stat-val red">₹<?= number_format($balance_due) ?></div>
        </div>
    </div>

    <div class="sections-wrapper">
        
        <div class="section-col">
            <div class="section-title">
                <span>Attendance Log</span>
                <span style="font-size:11px; color:#666; font-weight:400;">(<?= count($attendance_data) ?> Records)</span>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Shift</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($attendance_data) > 0): ?>
                        <?php foreach ($attendance_data as $row): 
                            $s = $row['status'];
                            $dot = ($s == 'Present') ? 'dot-green' : (($s == 'Absent') ? 'dot-red' : 'dot-yellow');
                        ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($row['date'])) ?></td>
                            <td><span class="status-dot <?= $dot ?>"></span><?= $s ?></td>
                            <td>
                                <?php if($row['check_in']): ?>
                                    <?= date('H:i', strtotime($row['check_in'])) ?> - <?= $row['check_out'] ? date('H:i', strtotime($row['check_out'])) : '...' ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#999;">No attendance records.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="section-col">
            <div class="section-title">
                <span>Payment History</span>
                <span style="font-size:11px; color:#666; font-weight:400;">(<?= count($payment_data) ?> Records)</span>
            </div>
            <table class="modern-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Note</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($payment_data) > 0): ?>
                        <?php foreach ($payment_data as $row): ?>
                        <tr>
                            <td><?= date('d M, Y', strtotime($row['payment_date'])) ?></td>
                            <td style="font-weight: 700; color: #16a34a;">₹<?= number_format($row['amount']) ?></td>
                            <td style="color:#6b7280; font-size: 11px;"><?= htmlspecialchars($row['notes'] ?? '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#999;">No payment records.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="footer">
        <div class="company-fineprint">
            <p><strong>Terms & Conditions:</strong><br>This report is computer generated. Any discrepancies must be reported to the admin office within 7 days.</p>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-text">Authorized Signatory</div>
        </div>
    </div>

</div>

</body>
</html>