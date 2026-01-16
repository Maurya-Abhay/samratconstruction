<?php
// payment_receipt.php

require_once 'database.php';

// Sanitize and validate ID
$id = (int)($_GET['id'] ?? 0);

// Join payments (p) with contacts (c) to get customer details
$stmt = $conn->prepare("SELECT p.*, c.name as customer_name, c.email, c.phone FROM contact_payments p JOIN contacts c ON p.contact_id=c.id WHERE p.id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

if (!$row){ die('Receipt not found'); }

// Company Information
$company = 'Samrat Construction Pvt. Ltd.';
$addr = 'Patna, Bihar, India - 800001';
$gst = '20AAAAA0000A1Z2'; // Example GSTIN
$logo = 'https://placehold.co/150x50/4e73df/ffffff?text=COMPANY+LOGO'; // High-quality placeholder image
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?= (int)$row['id'] ?></title>
    <!-- Using Bootstrap for structure and utilities -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    
    <style>
        body { 
            background: #f1f2f6; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .receipt-wrapper { 
            background: #fff; 
            border: 1px solid #e0e0e0; 
            border-radius: 12px; 
            padding: 40px; 
            max-width: 800px; 
            margin: 30px auto; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        }
        .header-section {
            padding-bottom: 20px;
            border-bottom: 3px solid #0d6efd; /* Primary color separator */
        }
        .receipt-title { 
            font-size: 1.8rem; 
            font-weight: 700; 
            color: #2c3e50; 
        }
        .meta-info { 
            font-size: 0.9rem; 
            color: #7f8c8d; 
        }
        .section-header {
            font-size: 1.1rem;
            font-weight: 600;
            color: #0d6efd;
            border-bottom: 1px solid #ecf0f1;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        .detail-row {
            padding: 10px 0;
            border-bottom: 1px dashed #ecf0f1;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .total-amount {
            font-size: 2rem;
            font-weight: 700;
            color: #27ae60; /* Success color for amount */
        }
        
        /* Print Styles */
        @media print {
            body { 
                background: #fff; 
                margin: 0; 
                padding: 0;
            }
            .receipt-wrapper {
                margin: 0;
                padding: 20px;
                max-width: none;
                border: none;
                box-shadow: none;
            }
            .print-hidden {
                display: none !important;
            }
        }
    </style>
</head>
<body>

<div class="receipt-wrapper">

    <!-- Header & Logo -->
    <div class="header-section mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($company) ?> Logo" style="height:50px; object-fit: contain;">
                <div class="text-muted small mt-2">
                    <?= htmlspecialchars($addr) ?><br>
                    <?php if ($gst): ?>GSTIN: <?= htmlspecialchars($gst) ?><?php endif; ?>
                </div>
            </div>
            <div class="text-end">
                <div class="receipt-title text-uppercase">Payment Receipt</div>
                <div class="meta-info">#RPT-<?= str_pad((int)$row['id'], 6, '0', STR_PAD_LEFT) ?></div>
            </div>
        </div>
    </div>

    <!-- Payment Metadata -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="section-header">Received From</div>
            <p class="mb-1"><strong><?= htmlspecialchars($row['customer_name']) ?></strong></p>
            <div class="meta-info">
                Email: <?= htmlspecialchars($row['email']) ?><br>
                Phone: <?= htmlspecialchars($row['phone']) ?>
            </div>
        </div>
        <div class="col-md-6 text-md-end">
            <div class="section-header">Date & Status</div>
            <div class="meta-info">
                Payment Date: <strong><?= date('d F Y', strtotime($row['payment_date'])) ?></strong><br>
                Status: <span class="badge bg-success fw-bold p-2">PAID</span>
            </div>
        </div>
    </div>

    <!-- Transaction Details Table/Card -->
    <div class="card bg-light border-0 mb-4">
        <div class="card-body p-4">
            <h5 class="mb-3 text-dark">Transaction Summary</h5>
            
            <div class="row detail-row">
                <div class="col-6 fw-bold">Amount Received</div>
                <div class="col-6 text-end">₹<?= number_format($row['amount'], 2) ?></div>
            </div>

            <div class="row detail-row">
                <div class="col-6 fw-bold">Payment Method</div>
                <div class="col-6 text-end text-uppercase"><?= htmlspecialchars($row['method'] ?? 'N/A') ?></div>
            </div>

            <?php if (!empty($row['transaction_id'])): ?>
            <div class="row detail-row">
                <div class="col-6 fw-bold">Transaction ID/Reference</div>
                <div class="col-6 text-end"><?= htmlspecialchars($row['transaction_id']) ?></div>
            </div>
            <?php endif; ?>

            <?php if (!empty($row['notes'])): ?>
            <div class="row detail-row">
                <div class="col-12 fw-bold mb-1">Notes / Description</div>
                <div class="col-12 meta-info"><?= htmlspecialchars($row['notes']) ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grand Total -->
    <div class="row justify-content-end mb-4">
        <div class="col-md-6">
            <div class="d-flex justify-content-between align-items-center p-3 bg-success-subtle rounded-3">
                <span class="fs-5 fw-bold text-success">TOTAL PAID</span>
                <span class="total-amount">₹<?= number_format($row['amount'], 2) ?></span>
            </div>
        </div>
    </div>

    <!-- Footer Notes & Signature -->
    <div class="row mt-5">
        <div class="col-12 text-center text-muted small border-top pt-3">
            <p class="mb-1">This is an electronically generated receipt and does not require a physical signature.</p>
            <p>Thank you for your business. We look forward to serving you again.</p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="text-center mt-4 print-hidden">
        <button onclick="window.print();" class="btn btn-primary"><i class="bi bi-printer me-2"></i> Print Receipt</button>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>