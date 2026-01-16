<?php
ob_start();

$page_title = 'Messages';

$show_back_btn = true;

// Define helper function for output safety
if (!function_exists('s')) {
    function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
}

include 'header.php';

require_once '../admin/database.php';

// Ensure table exists as per new schema
if ($conn instanceof mysqli) {
    $conn->query(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            source VARCHAR(20) NOT NULL DEFAULT 'contact',
            contact_id INT NULL,
            worker_id INT NULL,
            attendence_user_id INT NULL,
            category VARCHAR(50) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Open',
            admin_reply TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            replied_at TIMESTAMP NULL DEFAULT NULL,
            INDEX idx_worker_created (worker_id, created_at),
            INDEX idx_source_created (source, created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}


if (!$worker_id) { header('Location: login.php'); exit(); }


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $allowed = ['Work','Payment','New Work','Profile','Other'];
    $category = trim($_POST['category'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    // Simple sanitization for category selection
    if (!in_array($category, $allowed, true)) {
        $category = 'Other'; 
    }
    if ($messageText === '') {
        header("Location: messages.php?error=1");
        exit();
    } else {
        if ($stmt = $conn->prepare("INSERT INTO contact_messages (source, contact_id, worker_id, attendence_user_id, category, message) VALUES ('worker', NULL, ?, NULL, ?, ?)")) {
            $stmt->bind_param('iss', $worker_id, $category, $messageText);
            $ok = $stmt->execute();
            $stmt->close();
            if ($ok) {
                header("Location: messages.php?success=1");
                exit();
            } else {
                header("Location: messages.php?error=1");
                exit();
            }
        } else {
            header("Location: messages.php?error=1");
            exit();
        }
    }
}

// SweetAlert2 popup for success/error
if (isset($_GET['success'])) {
    echo "<script>Swal.fire({icon:'success',title:'Success!',text:'Message sent successfully. We will respond soon!',showConfirmButton:true, timer:2500}).then(function(){if(window.history.replaceState){var url=window.location.href.split('?')[0];window.history.replaceState({},document.title,url);}});</script>";
}
if (isset($_GET['error'])) {
    echo "<script>Swal.fire({icon:'error',title:'Error!',text:'Failed to send message. Please try again.',showConfirmButton:true, timer:3500}).then(function(){if(window.history.replaceState){var url=window.location.href.split('?')[0];window.history.replaceState({},document.title,url);}});</script>";
}

// Recent messages for this worker (Limit 10)
$recent = $conn->query("SELECT * FROM contact_messages WHERE worker_id = $worker_id ORDER BY created_at DESC LIMIT 10");

?>
<style>
    :root {
        --primary: #4f46e5;
        --secondary: #64748b;
        --bg-body: #f8fafc;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
    }
    body {
        font-family: "Plus Jakarta Sans", sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }
    .page-card {
        background: var(--card-bg);
        border-radius: 16px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.07);
        border: 1px solid #e2e8f0;
        overflow: hidden;
    }
    .card-header-custom {
        padding: 20px;
        background-color: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
    }
    .status-badge {
        padding: 5px 12px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
    }
    .status-open { background-color: #fcd34d; color: #92400e; } /* Amber/Warning */
    .status-replied { background-color: #d1fae5; color: #047857; } /* Green/Success */
    .status-closed { background-color: #e5e7eb; color: #4b5563; } /* Gray/Secondary */

    .message-table thead th {
        color: var(--secondary);
        font-size: .8rem;
        text-transform: uppercase;
        font-weight: 600;
        background: #f8fafc;
    }
    .message-table tbody tr:hover { background-color: #f1f5f9; }
    .message-text { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .admin-reply-box {
        background-color: #eff6ff;
        border-left: 3px solid var(--primary);
        padding: 10px;
        border-radius: 4px;
        font-size: 0.9rem;
        color: #1e40af;
    }
    .admin-reply-box small { font-weight: 600; }
</style>

<div class="container py-4">

    <!-- 1. SEND MESSAGE FORM -->
    <div class="page-card mb-5">
        <div class="card-header-custom">
            <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-chat-dots me-2"></i> Initiate New Query</h5>
        </div>

        <div class="p-4">
            <!-- SweetAlert2 handles feedback, no Bootstrap alert needed -->

            <form method="POST" class="row g-4">
                <div class="col-md-4">
                    <label class="form-label fw-semibold">Query Category</label>
                    <select name="category" class="form-select rounded-3 shadow-sm" required>
                        <option value="Work">Work Assignment/Schedule</option>
                        <option value="Payment">Payment/Salary Issue</option>
                        <option value="New Work">New Work Request</option>
                        <option value="Profile">Profile Update/Issue</option>
                        <option value="Other" selected>General / Other</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label fw-semibold">Your Message Details</label>
                    <textarea name="message" class="form-control rounded-3 shadow-sm" rows="3" required placeholder="Describe your question or request clearly and concisely..."></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                    <button type="submit" name="send_message" class="btn btn-primary px-4 py-2 rounded-pill shadow-sm">
                        <i class="bi bi-send me-2"></i> Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. RECENT MESSAGE HISTORY -->
    <div class="page-card">
        <div class="card-header-custom">
            <h5 class="mb-0 fw-bold text-secondary"><i class="bi bi-list-columns-reverse me-2"></i> Recent Communication History</h5>
            <small class="text-muted">Last 10 messages sent to the admin.</small>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0 message-table">
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="15%">Category</th>
                        <th width="25%">Your Message</th>
                        <th width="10%">Status</th>
                        <th width="35%">Admin Reply</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($recent && $recent->num_rows): 
                        while($m=$recent->fetch_assoc()): 
                            $status_class = 'status-closed';
                            if($m['status']==='Open') $status_class='status-open'; 
                            elseif($m['status']==='Replied') $status_class='status-replied';
                    ?>
                        <tr>
                            <td><?= date('d M Y, h:i A', strtotime($m['created_at'])) ?></td>
                            <td><span class="badge bg-light text-dark fw-normal border"><?= s($m['category']) ?></span></td>
                            <td title="<?= s($m['message']) ?>"><div class="message-text"><?= nl2br(s($m['message'])) ?></div></td>
                            <td>
                                <span class="status-badge <?= $status_class ?>">
                                    <?php if ($m['status']==='Open'): ?>
                                        <i class="bi bi-clock me-1"></i>
                                    <?php else: ?>
                                        <i class="bi bi-check-circle me-1"></i>
                                    <?php endif; ?>
                                    <?= s($m['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($m['admin_reply']): ?>
                                    <div class="admin-reply-box">
                                        <small>Admin:</small>
                                        <?= nl2br(s($m['admin_reply'])) ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted fst-italic">Awaiting response...</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; else: ?>
                        <tr><td colspan="5" class="text-center text-muted p-4">No recent messages found. Use the form above to send your first query.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>