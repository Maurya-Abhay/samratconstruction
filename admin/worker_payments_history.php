<?php
// worker_payments.php
// Single-file complete implementation: Fixed Layout for Mobile & Desktop

include 'topheader.php';
include 'sidenavbar.php';

/* --- Ensure payments table exists (safe create) --- */
$conn->query("CREATE TABLE IF NOT EXISTS worker_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method VARCHAR(50),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_worker_date (worker_id, payment_date),
    CONSTRAINT fk_wp_worker_hist FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

/* --- Fetch payments with worker details --- */
$sql = "SELECT wp.*, w.name AS worker_name
        FROM worker_payments wp
        JOIN workers w ON wp.worker_id = w.id
        ORDER BY wp.payment_date DESC, wp.id DESC
        LIMIT 200";
$res = $conn->query($sql);

/* --- Fetch site settings & admin name --- */
$settings = [];
$sres = $conn->query("SELECT setting_key, setting_value FROM site_settings");
if ($sres) {
    while ($row = $sres->fetch_assoc()) $settings[$row['setting_key']] = $row['setting_value'];
}
$admin_name = 'Manager';
if (!empty($_SESSION['email'])) {
    $stmt = $conn->prepare("SELECT name FROM admin WHERE email=? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $_SESSION['email']);
        $stmt->execute();
        $stmt->bind_result($admin_name);
        $stmt->fetch();
        $stmt->close();
    }
}

/* helper to safely output JS data attributes */
function js_attr($s){
    return htmlspecialchars($s ?? '', ENT_QUOTES);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Worker Payments</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<style>
:root{
  --accent:#f7931e;
  --muted:#6c757d;
  --page-bg:#f3f4f6;
}
body{ background:var(--page-bg); font-family: "Segoe UI", Roboto, Arial, sans-serif; }

/* Page table card */
.card-modern{ border:0; border-radius:8px; box-shadow:0 6px 20px rgba(0,0,0,0.06); }

/* --- RECEIPT STYLING (FIXED LAYOUT) --- */
/* Mobile Update: Force fixed width even on small screens */
.receipt-wrapper{
  width: 750px !important;      /* Fixed width matching A4 proportions */
  min-width: 750px !important;  /* Never shrink */
  background: #fff;
  margin: 0 auto;
  border-radius: 6px;
  box-shadow: 0 8px 28px rgba(0,0,0,0.12);
  overflow: hidden;
  position: relative;
}

/* Modal Adjustments for Scrolling */
.modal-body { 
    padding: 0 !important; 
    background: transparent; 
}

/* The holder allows horizontal scrolling on mobile */
#receiptHolder {
    width: 100%;
    overflow-x: auto;          /* Key for mobile: Allow swipe */
    -webkit-overflow-scrolling: touch; 
    padding: 20px 10px;        /* Spacing around receipt */
    text-align: center;        /* Center it if screen is big */
    display: block;
}

/* Inner content alignment override */
#receiptHolder .receipt-wrapper {
    display: inline-block;     /* Keeps it centered via text-align */
    text-align: left;          /* Reset text inside receipt */
}

/* Modal Dialog Sizing */
.modal-dialog { max-width: 820px; margin: 1.75rem auto; }

/* Mobile Modal Fixes */
@media (max-width: 600px){
  .modal-dialog { 
      max-width: 98%; 
      margin: 10px auto; 
  }
}

/* Print specific: Only print the receipt, A4 size */
@media print{
  body *{ visibility:hidden !important; }
  .receipt-wrapper, .receipt-wrapper * { visibility:visible !important; }
  .receipt-wrapper{ 
      position: absolute; 
      left: 0; 
      top: 0; 
      width: 100% !important; 
      margin: 0 !important;
      box-shadow: none !important; 
      border-radius: 0 !important; 
  }
  #receiptModal { position: absolute; left: 0; top: 0; margin: 0; padding: 0; overflow: visible !important; }
  .modal-dialog { margin: 0; max-width: 100%; }
}

/* small styling for tables inside receipt */
.receipt-table th{ background:var(--accent); color:#fff; padding:10px; font-weight:700; border:1px solid #e7e7e7; text-align:left; }
.receipt-table td{ padding:10px; border:1px solid #eee; vertical-align:middle; }
.receipt-note{ border:1px solid #ddd; background:#fafafa; padding:10px; min-height:56px; border-radius:6px; }

/* footer icons spacing */
.footer-icons a{ color:#fff; text-decoration:none; margin-left:6px; font-size:18px; }
</style>
</head>
<body>

<div class="container-fluid p-4">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="m-0"><i class="bi bi-cash-stack text-success me-2"></i>Worker Payments</h4>
    <a href="worker_make_payment.php" class="btn btn-success"><i class="bi bi-plus-lg me-1"></i>New Payment</a>
  </div>

  <div class="card card-modern">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th width="16%">Date</th>
              <th width="26%">Worker</th>
              <th width="12%">Amount</th>
              <th width="12%">Method</th>
              <th>Notes</th>
              <th class="text-end" width="10%">Receipt</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($res && $res->num_rows): ?>
              <?php while($row = $res->fetch_assoc()): ?>
                <tr>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars(date('d M Y', strtotime($row['payment_date']))) ?></div>
                    <small class="text-muted"><?= htmlspecialchars(date('h:i A', strtotime($row['created_at']))) ?></small>
                  </td>
                  <td>
                    <div class="fw-bold"><?= htmlspecialchars($row['worker_name']) ?></div>
                    <small class="text-muted">ID: #<?= (int)$row['worker_id'] ?></small>
                  </td>
                  <td><span class="fw-bold text-success">₹<?= number_format($row['amount'],2) ?></span></td>
                  <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($row['method'] ?: '-') ?></span></td>
                  <td><small class="text-muted"><?= htmlspecialchars($row['notes'] ?: '-') ?></small></td>
                  <td class="text-end">
                    <button class="btn btn-sm btn-outline-primary view-receipt-btn"
                      type="button"
                      data-id="<?= (int)$row['id'] ?>"
                      data-worker="<?= js_attr($row['worker_name']) ?>"
                      data-amount="<?= js_attr(number_format($row['amount'],2)) ?>"
                      data-method="<?= js_attr($row['method']) ?>"
                      data-date="<?= js_attr(date('d M Y', strtotime($row['payment_date']))) ?>"
                      data-notes="<?= js_attr($row['notes']) ?>"
                      data-admin="<?= js_attr($admin_name) ?>"
                      data-address="<?= js_attr($settings['office_address'] ?? '') ?>"
                      data-phone="<?= js_attr($settings['contact_phone'] ?? '') ?>"
                      data-email="<?= js_attr($settings['contact_email'] ?? '') ?>"
                      data-facebook="<?= js_attr($settings['facebook_url'] ?? '') ?>"
                      data-twitter="<?= js_attr($settings['twitter_url'] ?? '') ?>"
                      data-instagram="<?= js_attr($settings['instagram_url'] ?? '') ?>"
                      data-linkedin="<?= js_attr($settings['linkedin_url'] ?? '') ?>"
                      data-youtube="<?= js_attr($settings['youtube_url'] ?? '') ?>"
                    >
                      <i class="bi bi-receipt"></i>
                    </button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="6" class="text-center py-4 text-muted">No payment records found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>

<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content" style="background:transparent;border:none;">
      
      <div style="text-align:right; padding-bottom:5px;">
           <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" style="background-color:white; opacity:1; padding:10px; border-radius:50%;"></button>
      </div>

      <div class="modal-body">
        <div id="receiptHolder">
          </div>
      </div>
      
      <div class="modal-footer bg-transparent border-0 justify-content-center pt-2">
        <div class="btn-group shadow-sm" role="group">
          <button id="printBtn" type="button" class="btn btn-light border"><i class="bi bi-printer me-1"></i> Print</button>
          <button id="pngBtn" type="button" class="btn btn-light border"><i class="bi bi-filetype-png text-info me-1"></i> PNG</button>
          <button id="jpgBtn" type="button" class="btn btn-light border"><i class="bi bi-image text-warning me-1"></i> JPG</button>
          <button id="pdfBtn" type="button" class="btn btn-success"><i class="bi bi-file-earmark-pdf me-1"></i> PDF</button>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include 'downfooter.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* Helper for safe HTML */
function escHtml(s){ if(!s) return ''; return String(s).replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;').replaceAll('"','&quot;').replaceAll("'",'&#039;'); }
function escAttr(s){ if(!s) return ''; return String(s).replaceAll('"','&quot;').replaceAll("'",'&#039;'); }

document.addEventListener('click', function(e){
  var btn = e.target.closest('.view-receipt-btn');
  if (!btn) return;
  var d = btn.dataset;
  
  // Prepare Data
  var data = {
    id: d.id, worker: d.worker, amount: d.amount, method: d.method, date: d.date,
    notes: d.notes, admin: d.admin, address: d.address, phone: d.phone, email: d.email,
    facebook: d.facebook, twitter: d.twitter, instagram: d.instagram, linkedin: d.linkedin, youtube: d.youtube
  };

  // Build Invoice HTML (FIXED WIDTH STYLES INJECTED)
  var accent = getComputedStyle(document.documentElement).getPropertyValue('--accent') || '#f7931e';
  var html = `
    <div class="receipt-wrapper" style="font-family:Arial, sans-serif; background:#fff;">
      <div style="background:${accent}; color:#fff; padding:28px 36px; display:flex; justify-content:space-between; align-items:flex-start;">
        <div style="min-width:200px;">
          <div style="font-size:20px; font-weight:800; letter-spacing:1px;">SAMRAT CONSTRUCTION</div>
          <div style="opacity:.95; margin-top:6px;">Tagline Here</div>
        </div>
        <div style="text-align:right; min-width:200px;">
          <div style="font-size:20px; font-weight:800;">INVOICE</div>
          <div style="margin-top:10px; font-size:14px;">
            <div>Account No : <b>0123456789</b></div>
            <div>Invoice No : <b>#WP-${escHtml(data.id)}</b></div>
            <div>Date : <b>${escHtml(data.date)}</b></div>
          </div>
        </div>
      </div>

      <div style="padding:22px 36px 8px 36px;">
        <div style="font-size:16px; font-weight:700; color:#333; margin-bottom:10px;">Payment Info:</div>
        <table style="width:100%; font-size:14px;">
          <tr><td style="width:140px; padding:3px 0;"><b>Account No:</b></td><td>0123456789</td></tr>
          <tr><td style="padding:3px 0;"><b>Name:</b></td><td>${escHtml(data.worker)}</td></tr>
          <tr><td style="padding:3px 0;"><b>Method:</b></td><td>${escHtml(data.method)}</td></tr>
          <tr><td style="padding:3px 0;"><b>Notes:</b></td><td>${escHtml(data.notes)}</td></tr>
        </table>
      </div>

      <div style="padding:12px 36px 0 36px;">
        <table style="width:100%; border-collapse:collapse; font-size:14px;">
          <thead>
            <tr style="background:${accent}; color:#fff;">
              <th style="padding:10px; border:1px solid #e7e7e7; width:60px;">SL</th>
              <th style="padding:10px; border:1px solid #e7e7e7;">Description</th>
              <th style="padding:10px; border:1px solid #e7e7e7; text-align:right; width:110px;">Price</th>
              <th style="padding:10px; border:1px solid #e7e7e7; text-align:center; width:80px;">Qty</th>
              <th style="padding:10px; border:1px solid #e7e7e7; text-align:right; width:110px;">Total</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding:10px; border:1px solid #eee; text-align:center;">1</td>
              <td style="padding:10px; border:1px solid #eee;">Worker Payment</td>
              <td style="padding:10px; border:1px solid #eee; text-align:right;">₹${escHtml(data.amount)}</td>
              <td style="padding:10px; border:1px solid #eee; text-align:center;">1</td>
              <td style="padding:10px; border:1px solid #eee; text-align:right;">₹${escHtml(data.amount)}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div style="padding:18px 36px 6px 36px;">
        <div style="float:right; width:300px; font-size:14px;">
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Subtotal:</span><span>₹${escHtml(data.amount)}</span></div>
          <div style="display:flex; justify-content:space-between; margin-bottom:6px;"><span>Tax:</span><span>₹0.00</span></div>
          <div style="display:flex; justify-content:space-between; font-weight:800; color:${accent}; font-size:15px;"><span>TOTAL</span><span>₹${escHtml(data.amount)}</span></div>
        </div>
        <div style="clear:both;"></div>
      </div>

      <div style="padding:6px 36px 0 36px;">
        <div style="font-weight:700; color:${accent}; margin-bottom:8px;">Note:</div>
        <div style="border:1px solid #ddd; background:#fafafa; padding:10px; border-radius:6px; min-height:56px; font-size:14px;">
          ${escHtml(data.notes).replace(/\n/g,'<br>')}
        </div>
      </div>

      <div style="padding:22px 36px 10px 36px; text-align:right;">
        <div style="width:180px; border-top:1px solid #222; margin-left:auto; margin-bottom:6px;"></div>
        <div style="font-size:14px;"><b>${escHtml(data.admin)}</b><br><small>Manager</small></div>
      </div>

      <div style="background:${accent}; color:#fff; padding:18px 36px; margin-top:20px;">
        <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
          <div>
            <div style="font-weight:700; margin-bottom:6px;">GET IN TOUCH</div>
            <div style="font-size:13px;">${escHtml(data.address)}<br>${escHtml(data.phone)}<br>${escHtml(data.email)}</div>
          </div>
          <div style="font-size:20px;">
            ${data.facebook ? `<a href="${escAttr(data.facebook)}" target="_blank" style="color:#fff; margin-left:6px;"><i class="bi bi-facebook"></i></a>`:''}
            ${data.twitter ? `<a href="${escAttr(data.twitter)}" target="_blank" style="color:#fff; margin-left:6px;"><i class="bi bi-twitter"></i></a>`:''}
            ${data.instagram ? `<a href="${escAttr(data.instagram)}" target="_blank" style="color:#fff; margin-left:6px;"><i class="bi bi-instagram"></i></a>`:''}
            ${data.linkedin ? `<a href="${escAttr(data.linkedin)}" target="_blank" style="color:#fff; margin-left:6px;"><i class="bi bi-linkedin"></i></a>`:''}
            ${data.youtube ? `<a href="${escAttr(data.youtube)}" target="_blank" style="color:#fff; margin-left:6px;"><i class="bi bi-youtube"></i></a>`:''}
          </div>
        </div>
      </div>
    </div>
  `;

  document.getElementById('receiptHolder').innerHTML = html;
  new bootstrap.Modal(document.getElementById('receiptModal')).show();
});

/* --- DOWNLOAD & PRINT FUNCTIONS --- */

/* Print: Opens new window to ensure styles render correctly */
document.getElementById('printBtn').addEventListener('click', function(){
  var content = document.querySelector('.receipt-wrapper').outerHTML;
  var w = window.open('', '_blank');
  w.document.write('<html><head><title>Print Receipt</title>');
  w.document.write('<style>body{margin:0;display:flex;justify-content:center;} .receipt-wrapper{margin:0; box-shadow:none!important;}</style>');
  w.document.write('</head><body>');
  w.document.write(content);
  w.document.write('</body></html>');
  w.document.close();
  setTimeout(() => { w.focus(); w.print(); }, 500);
});

/* Image Download */
function dlImg(type){
  var el = document.querySelector('.receipt-wrapper');
  if(!el) return;
  
  html2canvas(el, { scale: 2, useCORS: true }).then(canvas => {
    var a = document.createElement('a');
    a.download = 'Receipt_' + Date.now() + '.' + (type=='jpeg'?'jpg':'png');
    a.href = canvas.toDataURL('image/'+type);
    document.body.appendChild(a); a.click(); a.remove();
  });
}
document.getElementById('pngBtn').onclick = () => dlImg('png');
document.getElementById('jpgBtn').onclick = () => dlImg('jpeg');

/* PDF Download (Auto-Fit A4) */
document.getElementById('pdfBtn').onclick = async () => {
  var el = document.querySelector('.receipt-wrapper');
  if(!el) return;
  
  try {
    var canvas = await html2canvas(el, { scale: 3, useCORS: true });
    var imgData = canvas.toDataURL('image/png');
    
    const { jsPDF } = window.jspdf;
    var pdf = new jsPDF('p', 'mm', 'a4');
    var pdfW = pdf.internal.pageSize.getWidth();
    var pdfH = pdf.internal.pageSize.getHeight();
    
    var imgProps = pdf.getImageProperties(imgData);
    var newW = pdfW - 20; // 10mm margins
    var newH = (imgProps.height * newW) / imgProps.width;
    
    pdf.addImage(imgData, 'PNG', 10, 10, newW, newH);
    pdf.save('Receipt_' + Date.now() + '.pdf');
  } catch(e) {
    alert('PDF Error: ' + e.message);
  }
};
</script>

</body>
</html>