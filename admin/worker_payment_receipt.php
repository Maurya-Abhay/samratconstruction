<?php
// ====== DYNAMIC DATA FROM BACKEND / DATABASE ======
$receipt_no = "001245";
$date = "30 April 2025";

$worker = [
    "name" => "John Doe",
    "id" => "WKR-83427",
    "account" => "0123456789",
    "bank" => "ABC Bank"
];

// TABLE ITEMS (add/edit/remove freely)
$items = [
    ["desc" => "Labour Charge", "rate" => 50, "qty" => 5],
    ["desc" => "Overtime",      "rate" => 20, "qty" => 3],
    ["desc" => "Bonus",         "rate" => 40, "qty" => 1]
];

// ====== CALCULATE TOTAL ======
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item["rate"] * $item["qty"];
}
$tax = 10;
$total = $subtotal + $tax;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Worker Payment Receipt</title>

<style>
    body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
    .invoice-container { width: 800px; margin: 0 auto; background: #fff; padding: 20px 40px; }

    .header { background: #f7931e; padding: 25px; color: #fff; position: relative; }
    .header h1 { margin: 0; font-size: 28px; font-weight: bold; }
    .header-right { position: absolute; right: 40px; top: 25px; text-align: right; font-size: 14px; }
    .header-right div { margin-bottom: 5px; }

    .payment-box { margin-top: 25px; border: 2px solid #f7931e; padding: 15px; }
    .section-title { font-weight: bold; font-size: 18px; margin-bottom: 10px; }
    .info-row { display: flex; justify-content: space-between; font-size: 15px; }

    table { width: 100%; border-collapse: collapse; margin-top: 25px; font-size: 15px; }
    table th { background: #f7931e; color: #fff; padding: 10px; text-align: center; }
    table td { padding: 10px; border-bottom: 1px solid #ddd; text-align: center; }

    .totals { width: 250px; float: right; margin-top: 10px; font-size: 15px; }
    .totals div { display: flex; justify-content: space-between; margin-bottom: 5px; }

    .note-box { margin-top: 50px; }
    .note-box h3 { margin-bottom: 5px; }
    .note-lines { border: 1px solid #bbb; height: 80px; margin-top: 5px; }

    .signature { margin-top: 50px; text-align: right; font-size: 15px; }
    .signature-line { width: 200px; border-top: 1px solid #000; margin-left: auto; margin-bottom: 5px; }

    .footer { margin-top: 60px; background: #f7931e; padding: 20px; color: #fff; text-align: center; }
</style>

</head>
<body>
<div class="invoice-container">

    <!-- HEADER -->
    <div class="header">
        <h1>WORKER PAYMENT RECEIPT</h1>

        <div class="header-right">
            <div><strong>Receipt No:</strong> <?= $receipt_no ?></div>
            <div><strong>Date:</strong> <?= $date ?></div>
        </div>
    </div>

    <!-- PAYMENT INFO -->
    <div class="payment-box">
        <div class="section-title">Payment Info:</div>

        <div class="info-row">
            <div><strong>Worker Name:</strong> <?= $worker["name"] ?></div>
            <div><strong>Worker ID:</strong> <?= $worker["id"] ?></div>
        </div>

        <div class="info-row" style="margin-top:10px;">
            <div><strong>Account No:</strong> <?= $worker["account"] ?></div>
            <div><strong>Bank:</strong> <?= $worker["bank"] ?></div>
        </div>
    </div>

    <!-- TABLE -->
    <table>
        <tr>
            <th>SL</th>
            <th>Description</th>
            <th>Rate</th>
            <th>Qty</th>
            <th>Total</th>
        </tr>

        <?php $i=1; foreach ($items as $item): ?>
        <tr>
            <td><?= $i++ ?></td>
            <td><?= $item["desc"] ?></td>
            <td>$<?= $item["rate"] ?></td>
            <td><?= $item["qty"] ?></td>
            <td>$<?= $item["rate"] * $item["qty"] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <!-- TOTALS -->
    <div class="totals">
        <div><span>Subtotal:</span> <span>$<?= $subtotal ?></span></div>
        <div><span>Tax:</span> <span>$<?= $tax ?></span></div>
        <div style="font-weight:bold; font-size:17px;">
            <span>Total:</span> <span>$<?= $total ?></span>
        </div>
    </div>

    <div style="clear:both;"></div>

    <!-- NOTE -->
    <div class="note-box">
        <h3>Note:</h3>
        <div class="note-lines"></div>
    </div>

    <!-- SIGNATURE -->
    <div class="signature">
        <div class="signature-line"></div>
        <div><strong>Authorized Signature</strong></div>
    </div>

    <!-- FOOTER -->
    <div class="footer">
        Contact Us • +123 456 7890 • email@company.com • Street Name, City
    </div>

</div>
</body>
</html>
