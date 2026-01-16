<?php
// PHP Logic (Unchanged from original code)
session_start();
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }
require_once '../admin/database.php';

$attendance_id = $_SESSION['attendance_id'];
$user_id = $attendance_id;
$user_name = '';
if ($stmt = $conn->prepare('SELECT id, name FROM attendence_users WHERE id=? LIMIT 1')){
    $stmt->bind_param('i', $attendance_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    if ($row) { $user_id = (int)$row['id']; $user_name = $row['name']; }
    $stmt->close();
}
if (!$user_id) { header('Location: login.php'); exit; }

// Ensure table exists (Prevent table creation in every load, put this in installation script)
// Keeping it here as per your original code, but generally, this belongs outside the main execution path.
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
        INDEX idx_att_created (attendence_user_id, created_at),
        INDEX idx_source_created (source, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$feedback = null;
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['send_message'])){
    $allowed = ['Work','Payment','New Work','Profile','Other'];
    $category = trim($_POST['category'] ?? '');
    $messageText = trim($_POST['message'] ?? '');
    if (!in_array($category, $allowed, true)) {
        $feedback = ['type'=>'danger','text'=>'Invalid category.'];
    } elseif ($messageText==='') {
        $feedback = ['type'=>'danger','text'=>'Please write your message.'];
    } else {
        if ($stmt=$conn->prepare("INSERT INTO contact_messages (source, contact_id, worker_id, attendence_user_id, category, message) VALUES ('attendance', NULL, NULL, ?, ?, ?)")){
            $stmt->bind_param('iss', $user_id, $category, $messageText);
            $ok=$stmt->execute();
            $stmt->close();
            $feedback = $ok?['type'=>'success','text'=>'Message sent successfully!']:['type'=>'danger','text'=>'Failed to send message.'];
        } else {
            $feedback = ['type'=>'danger','text'=>'System error.'];
        }
    }
}

// Recent messages
$recent=null;
if ($stmt=$conn->prepare('SELECT id, category, message, status, admin_reply, created_at FROM contact_messages WHERE attendence_user_id=? ORDER BY created_at DESC LIMIT 10')){
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $recent=$stmt->get_result();
    $stmt->close();
}

// Include the styled header file
include 'header.php';
?>

<style>
body { background: #f8fafc; font-family: 'Plus Jakarta Sans', sans-serif; }
.glass-card, .container, .card { background: rgba(255,255,255,0.98); border-radius: 24px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 30px; }
.form-control, .form-select { border-radius: 12px; background: #fff; color: #222; border: 1px solid #e5e7eb; }
.form-control:focus, .form-select:focus { border-color: #6366f1; box-shadow: 0 0 0 0.15rem #6366f133; }
.btn { border-radius: 12px; font-weight: 600; }
.btn-primary { background: #6366f1; border-color: #6366f1; }
.btn-primary:hover { background: #4338ca; border-color: #4338ca; }
.table { color: #222; background: #fff; }
.table thead { background: #f3f4f6 !important; color: #6366f1; font-weight: 600; }
.table tbody tr:hover { background: #f1f5f9 !important; }
.badge-status-open { background: #f59e0b; color: #fff; }
.badge-status-replied { background: #10b981; color: #fff; }
.badge-status-secondary { background: #64748b; color: #fff; }
/* Card icon */
.card-icon { font-size: 2.2rem; color: #6366f1; background: #eef2ff; border-radius: 16px; padding: 12px; display: inline-block; margin-bottom: 10px; }
</style>

<div class="container py-5">
    <div class="row g-4">
        <div class="col-12">
            <div class="glass-card mb-4">
                <span class="card-icon"><i class="bi bi-chat-dots-fill"></i></span>
                <h4 class="fw-bold mb-4">Send a New Message</h4>
                <?php if ($feedback): ?>
                    <div class="alert alert-<?= htmlspecialchars($feedback['type']) == 'success' ? 'success' : 'danger' ?> alert-dismissible fade show border-0" role="alert" style="color: <?= htmlspecialchars($feedback['type']) == 'success' ? '#10b981' : '#ef4444' ?>; background: #f8fafc; border-left: 5px solid <?= htmlspecialchars($feedback['type']) == 'success' ? '#10b981' : '#ef4444' ?> !important;">
                        <?= htmlspecialchars($feedback['text']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                <form method="POST" class="row g-4">
                    <div class="col-md-4">
                        <label class="form-label text-muted fw-bold small">Message Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Work">Work Inquiry</option>
                            <option value="Payment">Payment Issue</option>
                            <option value="New Work">New Work/Project</option>
                            <option value="Profile">Profile Update</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label text-muted fw-bold small">Your Message Details</label>
                        <textarea name="message" rows="3" class="form-control" placeholder="Describe your issue or request..." required></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button class="btn btn-primary" name="send_message">
                            <i class="bi bi-send-fill me-2"></i> Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-12 mt-5">
            <div class="glass-card">
                <h4 class="fw-bold mb-3">Your Recent Conversations</h4>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 15%;">Date</th>
                                <th style="width: 15%;">Category</th>
                                <th style="width: 30%;">Message</th>
                                <th style="width: 10%;">Status</th>
                                <th style="width: 30%;">Admin Reply</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent && $recent->num_rows): while($m=$recent->fetch_assoc()): 
                                $badge_class='badge-status-secondary'; 
                                if($m['status']==='Open') $badge_class='badge-status-open'; 
                                elseif($m['status']==='Replied') $badge_class='badge-status-replied'; 
                            ?>
                                <tr>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($m['created_at'])) ?><br><?= date('h:i A', strtotime($m['created_at'])) ?></small></td>
                                    <td><span class="badge bg-primary bg-opacity-75"><?= htmlspecialchars($m['category']) ?></span></td>
                                    <td><?= nl2br(htmlspecialchars(substr($m['message'], 0, 100) . (strlen($m['message']) > 100 ? '...' : ''))) ?></td>
                                    <td><span class="badge rounded-pill <?= $badge_class ?>"><?= htmlspecialchars($m['status']) ?></span></td>
                                    <td class="text-muted">
                                        <?= $m['admin_reply'] ? '<span class="text-dark small">' . nl2br(htmlspecialchars(substr($m['admin_reply'], 0, 100) . (strlen($m['admin_reply']) > 100 ? '...' : ''))) . '</span>' : '<span class="text-secondary small">Awaiting Admin Reply...</span>' ?>
                                    </td>
                                </tr>
                            <?php endwhile; else: ?>
                                <tr><td colspan="5" class="text-center py-4 text-secondary">No messages found. Start a new conversation above!</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Auto-refresh every 1 minute
    setInterval(function() {
        location.reload();
    }, 60000); // 60 seconds
</script>
</body>
</html>