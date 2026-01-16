<?php
// messages.php - Integrated Messaging/Ticket System

include 'topheader.php';
include 'sidenavbar.php';

// Include database connection (assumed from includes)

// --- Database Table Check/Creation (Ensuring all fields are present for the logic below) ---
// Note: The original SQL assumes 'worker_id', 'attendence_user_id', and 'source' exist
// in 'contact_messages' for the joins to work properly, but they are missing in the CREATE TABLE query.
// I will add them here for robustness, assuming the logic relies on them.

$conn->query("
  CREATE TABLE IF NOT EXISTS contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contact_id INT NULL,
    worker_id INT NULL, 
    attendence_user_id INT NULL, 
    source VARCHAR(20) NOT NULL DEFAULT 'contact', 
    category VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'Open',
    admin_reply TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    replied_at TIMESTAMP NULL DEFAULT NULL,
    INDEX idx_contact_created (contact_id, created_at),
    CONSTRAINT fk_cm_contact_admin FOREIGN KEY (contact_id) REFERENCES contacts(id) ON DELETE CASCADE
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// --- Handle reply / status update ---
$action_feedback = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg_id = intval($_POST['msg_id'] ?? 0);
    $reply = trim($_POST['reply'] ?? '');
    $new_status = trim($_POST['status_update'] ?? ''); // Renamed input to avoid clash
    $allowed_status = ['Open', 'Replied', 'Closed'];

    if ($msg_id > 0) {
        if ($reply !== '') {
            // Save Reply and set status to 'Replied'
            if ($stmt = $conn->prepare('UPDATE contact_messages SET admin_reply=?, status=\'Replied\', replied_at=NOW() WHERE id=?')) {
                $stmt->bind_param('si', $reply, $msg_id);
                $ok = $stmt->execute();
                $stmt->close();
                $action_feedback = $ok ? ['type' => 'success', 'text' => 'Reply saved and status updated to Replied.'] : ['type' => 'danger', 'text' => 'Failed to save reply.'];
            }
        } elseif ($new_status !== '' && in_array($new_status, $allowed_status, true)) {
            // Only update Status (if reply field is empty)
            if ($stmt = $conn->prepare('UPDATE contact_messages SET status=? WHERE id=?')) {
                $stmt->bind_param('si', $new_status, $msg_id);
                $ok = $stmt->execute();
                $stmt->close();
                $action_feedback = $ok ? ['type' => 'success', 'text' => 'Status updated to ' . htmlspecialchars($new_status) . '.'] : ['type' => 'danger', 'text' => 'Failed to update status.'];
            }
        }
    }
}

// --- Filters Setup ---
$status_filter = $_GET['status'] ?? 'Open';
$category_filter = $_GET['category'] ?? '';
$source_filter = $_GET['source'] ?? 'All';

$where = [];
$params = [];
$types = '';

if ($status_filter !== 'All') { 
    $where[] = 'm.status=?'; 
    $types .= 's'; 
    $params[] = $status_filter; 
}
if ($category_filter !== '') { 
    $where[] = 'm.category=?'; 
    $types .= 's'; 
    $params[] = $category_filter; 
}
$validSources = ['contact', 'worker', 'attendance'];
if ($source_filter !== 'All' && in_array($source_filter, $validSources, true)) { 
    $where[] = 'm.source=?'; 
    $types .= 's'; 
    $params[] = $source_filter; 
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Main Query ---
$sql = "SELECT m.*, 
          c.name AS contact_name, 
          w.name AS worker_name,
          au.name AS attendence_name
  FROM contact_messages m
  LEFT JOIN contacts c ON m.contact_id=c.id
  LEFT JOIN workers w ON m.worker_id=w.id
  LEFT JOIN attendence_users au ON m.attendence_user_id=au.id
  $whereSql
  ORDER BY m.created_at DESC LIMIT 200";

$stmt = $conn->prepare($sql);

if ($types) { 
    // Use the array spreading operator (...) for passing parameters dynamically
    $stmt->bind_param($types, ...$params); 
}

$stmt->execute();
$messages = $stmt->get_result();
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Messages | Admin</title>
    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .card-modern {
            border: none;
            border-radius: 12px;
            box-shadow: 0 6px 28px rgba(45, 62, 110, 0.12);
            background: #fff;
        }

        .table thead th {
            background-color: #f8f9fc;
            color: #858796;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.8rem;
            border-bottom: 2px solid #e3e6f0;
        }

        /* Styling for the reply/update column */
        .reply-form-td {
            min-width: 380px; /* Ensure space for controls */
            max-width: 400px;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4 py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="text-dark fw-bold m-0"><i class="bi bi-chat-dots text-primary me-2"></i>Inbox & Support Tickets</h3>
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
        </div>
    </div>

    <?php if ($action_feedback): ?>
        <div class="alert alert-<?= htmlspecialchars($action_feedback['type']) ?> d-flex align-items-center">
            <i class="bi bi-<?= $action_feedback['type'] === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
            <?= htmlspecialchars($action_feedback['text']) ?>
        </div>
    <?php endif; ?>

    <div class="card card-modern shadow-sm mb-4 p-3">
        <form class="row g-3 align-items-center" method="GET" action="messages.php">
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <?php $opts = ['All', 'Open', 'Replied', 'Closed']; foreach ($opts as $o): ?>
                        <option value="<?= $o ?>" <?= $status_filter === $o ? 'selected' : '' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="" <?= $category_filter === '' ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach (['Work', 'Payment', 'New Work', 'Profile', 'Other'] as $cat): ?>
                        <option value="<?= $cat ?>" <?= $category_filter === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">Source</label>
                <select name="source" class="form-select form-select-sm">
                    <?php $sopts = ['All', 'contact', 'worker', 'attendance']; foreach ($sopts as $o): ?>
                        <option value="<?= $o ?>" <?= $source_filter === $o ? 'selected' : '' ?>><?= ucfirst($o) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto mt-auto">
                <button class="btn btn-primary btn-sm"><i class="bi bi-filter"></i> Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="card card-modern p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="sticky-top">
                    <tr>
                        <th width="12%">Date</th>
                        <th width="15%">From</th>
                        <th width="10%">Category</th>
                        <th width="23%">Message</th>
                        <th width="10%">Status</th>
                        <th width="30%" class="reply-form-td">Reply / Status Update</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($messages && $messages->num_rows > 0): ?>
                        <?php while ($m = $messages->fetch_assoc()): ?>
                            <tr>
                                <td class="small text-muted text-nowrap"><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></td>
                                
                                <td>
                                    <?php 
                                    $source_badge = 'secondary';
                                    $name = 'Unknown';
                                    $id_tag = '';
                                    
                                    if ($m['source'] === 'worker') {
                                        $source_badge = 'primary';
                                        $name = htmlspecialchars($m['worker_name'] ?? 'Unknown');
                                        $id_tag = 'ID: #' . (int)($m['worker_id'] ?? 0);
                                    } elseif ($m['source'] === 'attendance') {
                                        $source_badge = 'info text-dark';
                                        $name = htmlspecialchars($m['attendence_name'] ?? 'Unknown');
                                        $id_tag = 'ID: #' . (int)($m['attendence_user_id'] ?? 0);
                                    } else { // default 'contact'
                                        $source_badge = 'success';
                                        $name = htmlspecialchars($m['contact_name'] ?? 'Unknown');
                                        $id_tag = 'ID: #' . (int)($m['contact_id'] ?? 0);
                                    }
                                    ?>
                                    <span class="badge bg-<?= $source_badge ?>"><?= ucfirst($m['source']) ?></span>
                                    <div class="fw-semibold mt-1"><?= $name ?></div>
                                    <small class="text-muted"><?= $id_tag ?></small>
                                </td>
                                
                                <td><?= htmlspecialchars($m['category']) ?></td>
                                
                                <td>
                                    <div class="small">
                                        <?= nl2br(htmlspecialchars($m['message'])) ?>
                                    </div>
                                </td>
                                
                                <td>
                                    <?php 
                                    $badge = 'secondary'; 
                                    if ($m['status'] === 'Open') $badge = 'warning'; 
                                    elseif ($m['status'] === 'Replied') $badge = 'success'; 
                                    elseif ($m['status'] === 'Closed') $badge = 'dark';
                                    ?>
                                    <span class="badge bg-<?= $badge ?> fw-normal"><?= htmlspecialchars($m['status']) ?></span>
                                    <?php if ($m['replied_at']): ?>
                                        <div class="small text-muted mt-1" title="Replied on <?= date('d M Y, h:i A', strtotime($m['replied_at'])) ?>">
                                            <i class="bi bi-check-all"></i> Replied
                                        </div>
                                    <?php endif; ?>
                                </td>
                                
                                <td class="reply-form-td">
                                    <form method="POST" class="row g-2">
                                        <input type="hidden" name="msg_id" value="<?= (int)$m['id'] ?>">
                                        
                                        <div class="col-12">
                                            <textarea name="reply" class="form-control form-control-sm" rows="2" placeholder="Admin Reply..."><?= htmlspecialchars($m['admin_reply'] ?? '') ?></textarea>
                                        </div>
                                        
                                        <div class="col-auto">
                                            <button class="btn btn-primary btn-sm" type="submit" title="Send reply and set status to 'Replied'">
                                                <i class="bi bi-send"></i> Send Reply
                                            </button>
                                        </div>
                                        
                                        <div class="col-auto">
                                            <select name="status_update" class="form-select form-select-sm">
                                                <option value="" disabled>Change Status</option>
                                                <?php foreach (['Open', 'Replied', 'Closed'] as $s): ?>
                                                    <option value="<?= $s ?>" <?= $m['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        
                                        <div class="col-auto">
                                            <button class="btn btn-outline-secondary btn-sm" type="submit" title="Update status only">
                                                <i class="bi bi-arrow-repeat"></i> Update
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">No messages found matching the filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

</body>
</html>