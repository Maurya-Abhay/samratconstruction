<?php

// send_reminders.php - filter, preview, and send reminders

session_start(); 
if (!isset($_SESSION['email'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/lib_common.php';

// --- Configuration ---
$brevoKey = get_setting('rem_brevo_api_key', '');
$waToken  = get_setting('rem_wa_token', '');
$waPhone  = get_setting('rem_wa_phone_id', '');
$subjTpl  = get_setting('rem_email_subject', 'Payment Reminder - Due Amount');
$emailTpl = get_setting('rem_email_body', 'Dear {{name}}, this is a gentle reminder that your due amount is ₹{{amount}}. Please clear it at the earliest.');
$smsTpl   = get_setting('rem_sms_tpl', 'Hi {{name}}, a payment of ₹{{amount}} is due. Please check your email for details.');

// --- Get Filter Parameters (via GET) ---
$minAmount = floatval($_GET['min_amount'] ?? 10); // Increased default minimum amount
$minDays   = intval($_GET['min_days'] ?? 0);
$channel   = $_GET['channel'] ?? 'auto'; // auto|email|whatsapp|sms
$dryRun    = isset($_GET['dry']) ? true : false;
$searchQ   = $_GET['q'] ?? '';

// --- Compute Dues and Filter Contacts ---
$sql_where = "status='Active'";
if ($searchQ !== '') {
    $q_safe = '%' . $conn->real_escape_string($searchQ) . '%';
    $sql_where .= " AND (name LIKE '$q_safe' OR email LIKE '$q_safe' OR phone LIKE '$q_safe')";
}

$rows = $conn->query("SELECT id,name,email,phone,contract_amount,amount_paid FROM contacts WHERE $sql_where ORDER BY id DESC");
$list = [];

// FIX: Corrected PHP logic to fetch all results in one clean loop
if ($rows) {
    while ($r = $rows->fetch_assoc()) {
        $due = max(0.0, ($r['contract_amount'] ?: 0) - ($r['amount_paid'] ?: 0));
        
        // Filter by minimum amount
        if ($due >= $minAmount) {
            $list[] = [
                'id'=>$r['id'],
                'name'=>$r['name'],
                'email'=>$r['email'],
                'phone'=>$r['phone'],
                'amount'=>$due,
                // Assuming days overdue calculation (currently placeholder)
                'days'=> $minDays 
            ];
        }
    }
}


// --- Sending Logic (via POST) ---
$sent = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send']) && !$dryRun) {
    
    $selected_ids = $_POST['selected_ids'] ?? [];
    
    // Create a list of contacts to send based on selected IDs
    $send_list = array_filter($list, function($c) use ($selected_ids) {
        return in_array($c['id'], $selected_ids);
    });

    foreach ($send_list as $c) {
        $vars = [
            'name'=>$c['name']?:'Customer',
            'amount'=>number_format($c['amount'],2),
            'days'=>$c['days'],
            'mobile'=>$c['phone'],
            'email'=>$c['email']
        ];
        
        $subject = render_tpl($subjTpl,$vars);
        $body    = render_tpl($emailTpl,$vars);
        $smsBody = render_tpl($smsTpl,$vars);

        $used = 'none';
        
        // Determine channel based on settings and contact info
        if ($channel==='email' || ($channel==='auto' && $brevoKey && !empty($c['email']))) {
            // TODO: Brevo integration
            $used = 'email';
        } elseif ($channel==='whatsapp' || ($channel==='auto' && $waToken && $waPhone && !empty($c['phone']))) {
            // TODO: WhatsApp Cloud API call
            $used = 'whatsapp';
        } else {
            // Default/Fallback SMS
            $used = 'sms';
        }
        
        $sent[] = ['id'=>$c['id'],'channel'=>$used,'subject'=>$subject,'body'=>$used==='email'?$body:$smsBody];
    }
    
    log_audit('reminders_sent', 'count='.count($sent).', channel='.$channel.', mode=' . ($dryRun ? 'Dry' : 'Live'));
}


include 'topheader.php'; 
include 'sidenavbar.php';
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
    .card-reminder {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }
    .table-reminder th {
        font-weight: 600;
        background-color: #f8f9fa;
        color: #495057;
    }
    .filter-group .form-control-sm, .filter-group .form-select-sm {
        border-radius: 8px;
    }
    .badge-due {
        font-size: 0.9em;
        padding: 5px 10px;
        border-radius: 6px;
    }
</style>

<div class="container-fluid px-4 py-4">

    <h3 class="fw-bold mb-4"><i class="bi bi-bell-fill text-warning me-2"></i>Payment Reminders</h3>

    <?php if ($sent): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i> Successfully initiated sending of <strong><?= count($sent) ?></strong> reminder(s) via <strong><?= htmlspecialchars($channel) ?></strong> channel.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filter Form -->
    <div class="card card-reminder p-3 mb-4">
        <form class="row g-3 align-items-end filter-group" method="get">
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Min Due (₹)</label>
                <input class="form-control form-control-sm" name="min_amount" type="number" step="0.01" value="<?= htmlspecialchars($minAmount) ?>" placeholder="Min Amount">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted mb-1">Min Days Overdue</label>
                <input class="form-control form-control-sm" name="min_days" type="number" value="<?= htmlspecialchars($minDays) ?>" placeholder="Min Days">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Search Contact</label>
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($searchQ) ?>" placeholder="Name, Email, or Phone">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted mb-1">Preferred Channel</label>
                <select class="form-select form-select-sm" name="channel">
                    <option value="auto" <?= $channel==='auto'?'selected':'' ?>>Auto (WA > Email > SMS)</option>
                    <option value="email" <?= $channel==='email'?'selected':'' ?>>Email Only</option>
                    <option value="whatsapp" <?= $channel==='whatsapp'?'selected':'' ?>>WhatsApp Only</option>
                    <option value="sms" <?= $channel==='sms'?'selected':'' ?>>SMS Only</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button class="btn btn-primary btn-sm w-100" type="submit"><i class="bi bi-search me-1"></i> Preview</button>
            </div>
        </form>
    </div>

    <!-- Results and Send Form -->
    <form method="POST">
        <!-- Hidden inputs for non-table filters for POST submission context -->
        <input type="hidden" name="min_amount" value="<?= htmlspecialchars($minAmount) ?>">
        <input type="hidden" name="min_days" value="<?= htmlspecialchars($minDays) ?>">
        <input type="hidden" name="channel" value="<?= htmlspecialchars($channel) ?>">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="m-0 text-dark">Filtered Contacts (<span id="contact-count"><?= count($list) ?></span> found)</h5>
            <div class="d-flex gap-2 align-items-center">
                <div class="form-check me-3">
                    <input class="form-check-input" type="checkbox" name="dry" id="dry_run_check" onchange="toggleSendButton(this.checked)" <?= $dryRun?'checked':'' ?>>
                    <label class="form-check-label small" for="dry_run_check">Dry Run (Preview Only)</label>
                </div>
                <button class="btn btn-danger btn-sm" name="send" value="1" id="send_button" <?= $dryRun ? 'disabled' : '' ?>>
                    <i class="bi bi-send-fill me-1"></i> Send Reminders (<span id="selected-count">0</span>)
                </button>
            </div>
        </div>

        <div class="card card-reminder">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 table-reminder">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input class="form-check-input" type="checkbox" id="select_all_checkbox">
                            </th>
                            <th>#</th>
                            <th>Name</th>
                            <th>Contact Info</th>
                            <th>Due Amount (₹)</th>
                            <th>Preview Message (<?= $channel ?>)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i=1; foreach ($list as $c): 
                            $vars=[
                                'name'=>$c['name']?:'Customer',
                                'amount'=>number_format($c['amount'],2),
                                'days'=>$c['days'],
                                'mobile'=>$c['phone'],
                                'email'=>$c['email']
                            ]; 
                            
                            // Generate appropriate preview based on channel
                            $preview_text = '';
                            if ($channel === 'email' || ($channel === 'auto' && $brevoKey && !empty($c['email']))) {
                                $preview_text = render_tpl($emailTpl, $vars);
                            } else {
                                $preview_text = render_tpl($smsTpl, $vars);
                            }
                        ?>
                        <tr>
                            <td>
                                <input class="form-check-input contact-checkbox" type="checkbox" name="selected_ids[]" value="<?= $c['id'] ?>">
                            </td>
                            <td><?= $i++ ?></td>
                            <td><i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($c['name']) ?></td>
                            <td>
                                <small class="text-muted">E: <?= htmlspecialchars($c['email']) ?: '-' ?></small><br>
                                <small class="text-muted">P: <?= htmlspecialchars($c['phone']) ?: '-' ?></small>
                            </td>
                            <td>
                                <span class="badge bg-warning text-dark badge-due">
                                    ₹<?= number_format($c['amount'],2) ?>
                                </span>
                            </td>
                            <td class="text-truncate" style="max-width:300px" title="<?= htmlspecialchars($preview_text) ?>">
                                <?= htmlspecialchars($preview_text) ?>
                            </td>
                        </tr>
                        <?php endforeach; if (!$list): ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">No active contacts match the current filters or have a positive due amount.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const selectAll = document.getElementById('select_all_checkbox');
        const checkboxes = document.querySelectorAll('.contact-checkbox');
        const sendButton = document.getElementById('send_button');
        const selectedCount = document.getElementById('selected-count');
        const dryRunCheck = document.getElementById('dry_run_check');

        function updateSendButton() {
            const checkedCount = document.querySelectorAll('.contact-checkbox:checked').length;
            selectedCount.textContent = checkedCount;
            sendButton.disabled = checkedCount === 0 || dryRunCheck.checked;
        }

        selectAll.addEventListener('change', function() {
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            updateSendButton();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                updateSendButton();
                // If any box is unchecked, uncheck the select all box
                if (!this.checked) {
                    selectAll.checked = false;
                }
            });
        });
        
        // Initial state update
        updateSendButton();
        
        window.toggleSendButton = function(isDryRun) {
            dryRunCheck.checked = isDryRun;
            updateSendButton();
        };

        // Select all contacts by default if there are contacts
        if (checkboxes.length > 0) {
            selectAll.checked = true;
            selectAll.dispatchEvent(new Event('change'));
        }
    });
</script>

<?php include 'downfooter.php'; ?>