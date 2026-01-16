<?php
// PHP Logic (No changes to the backend logic are needed for the UI upgrade)
session_start();
require_once '../admin/database.php';
@include_once __DIR__ . '/../admin/analytics_track.php';

if (!isset($_SESSION['contact_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$contact_id = (int)$_SESSION['contact_id'];

// Create table (same logic) - Wrapped in an existence check for safety
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
        replied_at TIMESTAMP NULL DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);


// --- Submit Handler ---

$feedback = null;
$category = $_POST['category'] ?? '';
$messageText = $_POST['message'] ?? '';

// PRG pattern: handle feedback from session after redirect
if (isset($_SESSION['feedback'])) {
    $feedback = $_SESSION['feedback'];
    unset($_SESSION['feedback']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $allowed_categories = ['Work','Payment','New Work','Profile','Other'];
    $category = trim($category);
    $messageText = trim($messageText);
    $messageText = substr($messageText, 0, 5000);

    if (!in_array($category, $allowed_categories, true)) {
        $_SESSION['feedback'] = ['type'=>'danger','text'=>'Invalid category selected.'];
    } elseif ($messageText === '') {
        $_SESSION['feedback'] = ['type'=>'danger','text'=>'Message cannot be empty.'];
    } else {
        $source = 'contact';
        $stmt = $conn->prepare("INSERT INTO contact_messages (source, contact_id, category, message) VALUES (?, ?, ?, ?)");
        $stmt->bind_param('siss', $source, $contact_id, $category, $messageText);

        if ($stmt->execute()) {
            $_SESSION['feedback'] = ['type'=>'success','text'=>'Message sent! Admin will reply soon.'];
            // Clear form values after successful submission
            $_SESSION['clear_form'] = true;
        } else {
            $_SESSION['feedback'] = ['type'=>'danger','text'=>'Database error!'];
        }
        $stmt->close();
    }
    // Redirect to self to prevent resubmission
    header('Location: messages.php');
    exit();
}

// Clear form values if needed
if (isset($_SESSION['clear_form'])) {
    unset($category, $messageText);
    unset($_SESSION['clear_form']);
}


// Fetch last 15 messages
$res = $conn->prepare('
    SELECT id, category, message, status, admin_reply, created_at, replied_at 
    FROM contact_messages 
    WHERE contact_id = ? 
    ORDER BY created_at DESC 
    LIMIT 15
');
$res->bind_param('i', $contact_id);
$res->execute();
$messages = $res->get_result();
$res->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Support Center</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
    /* 1. Global Styles */
    body {
        /* Vibrant but soft background */
        background: linear-gradient(135deg, #f0f4ff 0%, #e0e8ff 100%);
        font-family: 'Poppins', sans-serif;
        padding-top: 85px;
        min-height: 100vh;
    }
    .container {
        max-width: 1000px; /* Max width for desktop readability */
    }

    /* 2. Card Styling */
    .modern-card {
        border-radius: 16px;
        border: none;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); /* Stronger shadow for depth */
        transition: all 0.3s ease;
    }
    .modern-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 15px 45px rgba(0,0,0,0.1);
    }
    .header-title {
        font-weight: 700;
        font-size: 2rem;
        color: #3f51b5; /* Deeper primary color */
    }
    
    /* 3. Form Input Card */
    .input-card {
        background: #ffffff; /* Keep it white for clarity */
        border: 1px solid #e0e8ff;
    }
    .form-select, .form-control {
        border-radius: 10px;
        transition: border-color 0.2s;
    }
    .form-select:focus, .form-control:focus {
        border-color: #3f51b5;
        box-shadow: 0 0 0 0.25rem rgba(63, 81, 181, 0.25);
    }
    .btn-primary {
        background-color: #3f51b5;
        border-color: #3f51b5;
        border-radius: 10px;
        font-weight: 600;
        transition: background-color 0.3s;
    }
    .btn-primary:hover {
        background-color: #303f9f;
        border-color: #303f9f;
    }

    /* 4. Table and History */
    .table thead {
        background: #e5f0ff; /* Light blue header */
        color: #3f51b5;
        font-weight: 600;
    }
    .table-hover tbody tr:hover {
        background-color: #f7f9ff;
    }
    
    /* 5. Status Badges */
    .badge-status {
        padding: 6px 12px;
        border-radius: 50px; /* Pill shape */
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* 6. Admin Reply - The "Chat Bubble" look */
    .reply-box {
        background: #e3f2fd; /* Very light blue */
        border-left: none;
        border-radius: 15px 15px 15px 0; /* Chat bubble corner effect */
        padding: 10px 15px;
        position: relative;
        font-style: italic;
    }
    .reply-box-sent-time {
        font-size: 0.65rem;
        margin-top: 5px;
        color: #7986cb;
    }

    /* Responsive adjustments */
    @media (max-width: 767.98px) {
        .header-title {
            font-size: 1.75rem;
        }
        .table thead {
            display: none; /* Hide header on small screens */
        }
        .table tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #eee;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .table tbody td {
            display: block;
            width: 100%;
            padding: 0.5rem 1rem;
            text-align: left !important;
        }
        .table tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            color: #3f51b5;
            display: inline-block;
            min-width: 80px;
            margin-right: 10px;
        }
        .table tbody tr td:first-child { border-top-left-radius: 10px; border-top-right-radius: 10px; }
        .table tbody tr td:last-child { border-bottom-left-radius: 10px; border-bottom-right-radius: 10px; }
    }
</style>
</head>

<body>

<?php 
// Assuming header.php handles the navigation bar with proper responsiveness
$contact_show_back_btn = true;
$contact_back_href = 'dashboard.php';
include __DIR__ . '/header.php';
?>

<div class="container">

    <h2 class="header-title mb-4">
        <i class="bi bi-headset me-2 text-primary"></i> Support Center
    </h2>

    <?php if ($feedback): ?>
        <div class="alert alert-<?= $feedback['type'] ?> shadow-sm d-flex align-items-center rounded-3 mb-4">
            <i class="bi bi-<?= $feedback['type']=='success'?'check-circle-fill':'exclamation-octagon-fill' ?> me-2 fs-5"></i>
            <div><?= htmlspecialchars($feedback['text']) ?></div>
        </div>
    <?php endif; ?>


    <div class="modern-card p-4 mb-5 input-card">
        <h5 class="fw-bold mb-4 border-bottom pb-2">
            <i class="bi bi-send-plus me-2 text-primary"></i> Submit a New Request
        </h5>

        <form method="POST" class="row g-3">
            
            <div class="col-md-4">
                <label for="category" class="form-label fw-semibold">Category</label>
                <select id="category" name="category" class="form-select shadow-sm">
                    <?php 
                    $categories = ['Work','Payment','New Work','Profile','Other'];
                    foreach ($categories as $cat) {
                        $selected = ($category === $cat) ? 'selected' : '';
                        echo "<option value=\"$cat\" $selected>$cat</option>";
                    }
                    ?>
                </select>
            </div>

            <div class="col-md-8">
                <label for="message" class="form-label fw-semibold">Your Message</label>
                <textarea id="message" name="message" rows="3" class="form-control shadow-sm" placeholder="Write your detailed message here... (Max 5000 characters)"><?= htmlspecialchars($messageText ?? '') ?></textarea>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" name="send_message" class="btn btn-primary px-4 py-2 shadow-lg">
                    <i class="bi bi-chat-dots me-1"></i> Send Request
                </button>
            </div>
        </form>
    </div>


    <div class="modern-card p-0 mb-5">
        <div class="p-4 rounded-top-4" style="background: #f0f4ff; border-bottom: 2px solid #e0e8ff;">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-clock-history me-2 text-primary"></i> Message History (Last 15)
            </h5>
        </div>

        <div class="table-responsive">
            <table class="table table-borderless align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ps-4">Date Sent</th>
                        <th>Category</th>
                        <th>Your Message</th>
                        <th>Status</th>
                        <th class="pe-4">Admin Reply</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($messages->num_rows > 0): while($m=$messages->fetch_assoc()): ?>
                    <tr>
                        <td class="small text-muted ps-4" data-label="Date Sent:">
                            <span class="d-block fw-semibold"><?= date('d M Y', strtotime($m['created_at'])) ?></span>
                            <span class="text-secondary"><?= date('h:i A', strtotime($m['created_at'])) ?></span>
                        </td>

                        <td data-label="Category:"><span class="badge bg-secondary rounded-pill"><?= htmlspecialchars($m['category']) ?></span></td>

                        <td class="small text-wrap" style="max-width: 250px;" data-label="Your Message:"><?= nl2br(htmlspecialchars($m['message'])) ?></td>

                        <td data-label="Status:">
                            <?php 
                                $badgeClass = match($m['status']) {
                                    'Open' => 'warning text-dark',
                                    'Replied' => 'success',
                                    'Closed' => 'secondary',
                                    default => 'info',
                                };
                            ?>
                            <span class="badge bg-<?= $badgeClass ?> badge-status">
                                <?= htmlspecialchars($m['status']) ?>
                            </span>
                        </td>

                        <td class="small pe-4" data-label="Admin Reply:">
                            <?php if ($m['admin_reply']): ?>
                                <div class="reply-box">
                                    <?= nl2br(htmlspecialchars($m['admin_reply'])) ?>
                                    <?php if ($m['replied_at']): ?>
                                        <div class="reply-box-sent-time text-end">
                                            Replied: <?= date('d M Y, h:i A', strtotime($m['replied_at'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <span class="text-muted fst-italic">Awaiting response...</span>
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center text-muted py-5">
                        <i class="bi bi-inbox-fill fs-3 mb-2"></i><br>
                        You have not submitted any support messages yet.
                    </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php include __DIR__ . '/footer.php'; ?>
</body>
</html>