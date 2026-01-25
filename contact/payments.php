<?php
// payments.php - Client Payment History View

session_start();

// Load required configuration and utilities
require_once '../admin/database.php'; // Assumes $conn database connection is available
@include_once __DIR__ . '/../admin/analytics_track.php';

// --- Security Check ---
if (!isset($_SESSION['contact_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$contact_id = (int)$_SESSION['contact_id'];

// --- Load Contact Summary Data ---
// Fetch contract total and paid total
$contact = $conn->query("SELECT name, contract_amount, amount_paid FROM contacts WHERE id=$contact_id")->fetch_assoc();

// Calculate remaining due amount
$contract_amount = (float)($contact['contract_amount'] ?? 0);
$amount_paid = (float)($contact['amount_paid'] ?? 0);
$due_amount = max(0, $contract_amount - $amount_paid);

// --- Fetch Payment History ---
$stmt = $conn->prepare("SELECT payment_date, amount, notes FROM contact_payments WHERE contact_id=? ORDER BY payment_date DESC, id DESC");
$stmt->bind_param('i', $contact_id);
$stmt->execute();
$payments = $stmt->get_result(); // Get result set
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../admin/assets/jp_construction_logo.webp" type="image/webp">
    <title>Payment History | Client Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        /* Base Styles */
        body { 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); 
            font-family: 'Inter', sans-serif; 
            padding-top: 80px; 
        }
        .header-title {
            font-weight: 700;
            color: #1a535c; /* A deep, professional color */
            border-bottom: 2px solid #ced4da;
            padding-bottom: 10px;
        }

        /* Stat Cards (Summary) */
        .stat-card {
            border-radius: 12px;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
            padding: 1.5rem;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
        }
        .stat-card .label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        /* Table Card */
        .modern-table-card {
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            overflow: hidden; /* Ensures rounded corners */
        }
        .modern-table-card .card-header {
            background-color: #1a535c;
            color: white;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }
        .table thead th {
            font-weight: 700;
            color: #495057;
            background-color: #f0f3f6;
        }
        .table-hover tbody tr:hover {
            background-color: #f3f7fa;
        }
        .text-paid { color: #28a745 !important; }
        .text-due { color: #dc3545 !important; }
        .text-contract { color: #007bff !important; }

        /* Responsive Table (Mobile View) */
        @media (max-width: 767.98px) {
            .table thead {
                display: none; /* Hide header on small screens */
            }
            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 8px;
            }
            .table tbody td {
                display: block;
                width: 100%;
                padding: 0.75rem 1rem;
                text-align: right !important;
                border: none;
            }
            .table tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #495057;
                float: left;
            }
        }
    </style>
</head>
<body>

<?php 
$contact_show_back_btn = true; 
$contact_back_href = 'dashboard.php'; 
include __DIR__ . '/header.php'; // Include navigation header
?>

<div class="container py-5">
    
    <h2 class="header-title mb-5">
        <i class="bi bi-wallet2 me-2"></i> Your Payment Records
    </h2>
    
    <div class="row g-4 mb-5">
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label"><i class="bi bi-file-earmark-text me-1 text-contract"></i> Contract Value</div> 
                <div class="value text-contract">₹<?= number_format($contract_amount, 2) ?></div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card">
                <div class="label"><i class="bi bi-check-circle-fill me-1 text-paid"></i> Total Paid</div> 
                <div class="value text-paid">₹<?= number_format($amount_paid, 2) ?></div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="stat-card" style="border: 1px solid #dc3545;">
                <div class="label"><i class="bi bi-exclamation-triangle-fill me-1 text-due"></i> Balance Due</div> 
                <div class="value text-due">₹<?= number_format($due_amount, 2) ?></div>
            </div>
        </div>
        
    </div>

    <div class="modern-table-card">
        <div class="card-header">
            <i class="bi bi-list-columns-reverse me-1"></i> Recent Transactions History
        </div>
        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th class="ps-3" style="width:20%;">Date</th>
                        <th class="text-end" style="width:20%;">Amount (₹)</th>
                        <th>Notes / Description</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                if ($payments && $payments->num_rows): 
                    while($row = $payments->fetch_assoc()):
                ?>
                    <tr>
                        <td data-label="Date" class="ps-3" style="white-space: nowrap;"><?= date('d M Y', strtotime($row['payment_date'])) ?></td>
                        <td data-label="Amount" class="text-end text-paid fw-bold">₹<?= number_format($row['amount'], 2) ?></td>
                        <td data-label="Notes"><?= htmlspecialchars($row['notes'] ?? 'N/A') ?></td>
                    </tr>
                <?php 
                    endwhile; 
                else: 
                ?>
                    <tr>
                        <td colspan="3" class="text-center text-muted fst-italic py-5">
                            <i class="bi bi-cash-stack fs-3 mb-2 d-block"></i>
                            No payment records found.
                        </td>
                    </tr>
                <?php 
                endif; 
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/footer.php'; ?>

</body>
</html>