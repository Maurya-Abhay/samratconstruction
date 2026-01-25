<?php
// admin/contact_messages.php
require_once 'lib_common.php';

// --- LOGIC: AS IT IS (No changes) ---
$conn->query("ALTER TABLE full_texts ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'Pending'");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['msg_id'])) {
    $msg_id = intval($_POST['msg_id']);
    if ($_POST['action'] === 'accept') {
        $conn->query("UPDATE full_texts SET status='Accepted' WHERE id=$msg_id");
    } elseif ($_POST['action'] === 'pending') {
        $conn->query("UPDATE full_texts SET status='Pending' WHERE id=$msg_id");
    } elseif ($_POST['action'] === 'delete') {
        $conn->query("DELETE FROM full_texts WHERE id=$msg_id");
    }
    header('Location: contact_messages.php');
    exit();
}

include 'topheader.php';
include 'sidenavbar.php';

$messages = [];
if ($conn) {
    $result = $conn->query("SELECT * FROM full_texts ORDER BY id DESC");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
}
?>

<style>
    /* --- NEW MODERN UI DESIGN --- */
    .admin-card {
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.05);
        border: none;
        margin-bottom: 2rem;
    }

    .table thead th {
        background-color: #f8fafc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        font-weight: 700;
        color: #64748b;
        border-top: none;
        padding: 15px 20px;
    }

    .table tbody td {
        padding: 18px 20px;
        vertical-align: middle;
        color: #334155;
        border-bottom: 1px solid #f1f5f9;
    }

    /* Status Pill Styles */
    .status-pill {
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
    }
    .status-pending { background: #ffedd5; color: #9a3412; }
    .status-accepted { background: #dcfce7; color: #166534; }

    /* Action Buttons Clean-up */
    .btn-action {
        border-radius: 8px;
        width: 35px;
        height: 35px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: 0.2s;
        border: none;
    }
    .btn-acc { background: #e0e7ff; color: #4338ca; }
    .btn-pen { background: #fef3c7; color: #92400e; }
    .btn-del { background: #fee2e2; color: #b91c1c; }
    .btn-action:hover { transform: translateY(-2px); opacity: 0.8; }

    .msg-box {
        max-width: 250px;
        font-size: 0.85rem;
        color: #64748b;
        line-height: 1.4;
    }

    .user-info b { color: #1e293b; display: block; font-size: 0.95rem; }
    .user-info span { font-size: 0.8rem; color: #94a3b8; }
</style>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark m-0">Inquiry Management</h3>
        <div class="text-muted small">Total Messages: <b><?= count($messages) ?></b></div>
    </div>

    <div class="admin-card">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Inquirer</th>
                        <th>Contact Details</th>
                        <th>Subject</th>
                        <th>Message</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($messages)): ?>
                        <tr><td colspan="6" class="text-center py-5">No messages found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): 
                            $currStatus = $msg['status'] ?? 'Pending';
                            $statusClass = ($currStatus == 'Accepted') ? 'status-accepted' : 'status-pending';
                        ?>
                            <tr>
                                <td class="user-info">
                                    <b><?= htmlspecialchars($msg['name']) ?></b>
                                    <span>ID: #<?= $msg['id'] ?></span>
                                </td>
                                <td>
                                    <div class="small fw-bold"><?= htmlspecialchars($msg['mobile']) ?></div>
                                    <div class="small text-muted"><?= htmlspecialchars($msg['email']) ?></div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border"><?= htmlspecialchars($msg['subject']) ?></span>
                                </td>
                                <td>
                                    <div class="msg-box"><?= nl2br(htmlspecialchars($msg['message'])) ?></div>
                                </td>
                                <td>
                                    <span class="status-pill <?= $statusClass ?>"><?= $currStatus ?></span>
                                </td>
                                <td>
                                    <form method="post" class="d-flex justify-content-center gap-2">
                                        <input type="hidden" name="msg_id" value="<?= (int)$msg['id'] ?>">
                                        
                                        <?php if ($currStatus !== 'Accepted'): ?>
                                            <button name="action" value="accept" class="btn-action btn-acc" title="Accept">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        <?php endif; ?>

                                        <?php if ($currStatus !== 'Pending'): ?>
                                            <button name="action" value="pending" class="btn-action btn-pen" title="Set Pending">
                                                <i class="bi bi-clock-history"></i>
                                            </button>
                                        <?php endif; ?>

                                        <button name="action" value="delete" class="btn-action btn-del" onclick="return confirm('Delete permanently?')" title="Delete">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>