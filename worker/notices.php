<?php
// notices.php (Modern Light Premium redesign)
session_start();

$page_title = "Notices";
$show_back_btn = true;
include 'header.php';

// Ensure DB connection ($conn) is available
if (!isset($conn) || !($conn instanceof mysqli)) {
    if (file_exists(__DIR__ . '/../admin/database.php')) {
        require_once __DIR__ . '/../admin/database.php';
    }
}

if (!isset($_SESSION['worker_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch notices
$notices = null;
if ($conn instanceof mysqli) {
    $stmt = $conn->prepare("
        SELECT id, title, content, priority, created_at 
        FROM notices 
        WHERE is_active='Yes' 
        ORDER BY FIELD(priority, 'Urgent','High','Medium','Low'), created_at DESC
    ");
    $stmt->execute();
    $notices = $stmt->get_result();
}

function s($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #4f46e5;
        --secondary: #64748b;
        --bg-body: #f1f5f9;
        --card-bg: #ffffff;
        --text-dark: #1e293b;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background-color: var(--bg-body);
        color: var(--text-dark);
    }

    .notice-container {
        max-width: 1100px;
        margin: 30px auto;
        padding: 0 15px;
    }

    /* Header Section */
    .header-card {
        background: linear-gradient(135deg, var(--primary), #818cf8);
        color: white;
        border-radius: 16px;
        padding: 30px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .header-info h2 { font-weight: 700; margin: 0; font-size: 1.8rem; }
    .header-info p { margin: 5px 0 0; opacity: 0.9; font-size: 0.95rem; }

    /* Notice Card Styling */
    .notice-card {
        background: var(--card-bg);
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
        display: flex;
        flex-direction: column;
        border-left: 5px solid transparent; /* Colored border placeholder */
        animation: fadeIn 0.5s ease-out;
    }

    .notice-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.1);
    }

    /* Priority Variants */
    .border-urgent { border-left-color: #ef4444; }
    .border-high   { border-left-color: #f97316; }
    .border-medium { border-left-color: #eab308; }
    .border-low    { border-left-color: #3b82f6; }

    .badge-urgent { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-high   { background: #ffedd5; color: #9a3412; border: 1px solid #fed7aa; }
    .badge-medium { background: #fef9c3; color: #854d0e; border: 1px solid #fde047; }
    .badge-low    { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

    /* Pulse for Urgent */
    .pulse-dot {
        width: 8px; height: 8px; border-radius: 50%; display: inline-block; margin-right: 6px;
        animation: pulse-red 2s infinite;
    }
    @keyframes pulse-red {
        0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }

    .card-body { padding: 25px; flex-grow: 1; }
    .notice-title { font-weight: 700; font-size: 1.15rem; color: var(--text-dark); margin-bottom: 8px; line-height: 1.4; }
    .notice-meta { font-size: 0.85rem; color: var(--secondary); margin-bottom: 15px; display: flex; align-items: center; gap: 15px; }
    .notice-content { color: #334155; font-size: 0.95rem; line-height: 1.6; white-space: pre-wrap; }

    /* Footer area of card */
    .card-footer-custom {
        padding: 15px 25px;
        background: #f8fafc;
        border-top: 1px solid #f1f5f9;
        font-size: 0.8rem;
        color: var(--secondary);
        border-bottom-left-radius: 16px;
        border-bottom-right-radius: 16px;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<div class="notice-container">

    <div class="header-card">
        <div class="header-info">
            <h2>Notice Board</h2>
            <p>Important announcements and updates from the administration.</p>
        </div>
        <div>
            <div class="bg-white bg-opacity-25 p-2 rounded-circle">
                <i class="bi bi-megaphone fs-3 text-white px-2"></i>
            </div>
        </div>
    </div>

    <?php if ($notices && $notices->num_rows > 0): ?>
        <div class="row g-4">
            <?php while ($n = $notices->fetch_assoc()): 
                // Determine styling based on priority
                $p = strtolower($n['priority']);
                $borderClass = 'border-low';
                $badgeClass = 'badge-low';
                $icon = 'bi-info-circle';

                if ($p == 'urgent') { 
                    $borderClass = 'border-urgent'; 
                    $badgeClass = 'badge-urgent'; 
                    $icon = 'bi-exclamation-octagon-fill';
                } elseif ($p == 'high') { 
                    $borderClass = 'border-high'; 
                    $badgeClass = 'badge-high'; 
                    $icon = 'bi-exclamation-triangle-fill';
                } elseif ($p == 'medium') { 
                    $borderClass = 'border-medium'; 
                    $badgeClass = 'badge-medium'; 
                    $icon = 'bi-bell-fill';
                }
            ?>
                <div class="col-md-6 col-lg-4">
                    <div class="notice-card <?= $borderClass ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="badge rounded-pill <?= $badgeClass ?> px-3 py-2 d-flex align-items-center">
                                    <?php if($p == 'urgent'): ?>
                                        <span class="pulse-dot bg-danger"></span>
                                    <?php else: ?>
                                        <i class="bi <?= $icon ?> me-2"></i>
                                    <?php endif; ?>
                                    <?= s($n['priority']) ?> Priority
                                </span>
                            </div>

                            <h5 class="notice-title"><?= s($n['title']) ?></h5>
                            
                            <div class="notice-meta">
                                <span><i class="bi bi-calendar3 me-1"></i> <?= date('d M, Y', strtotime($n['created_at'])) ?></span>
                                <span><i class="bi bi-clock me-1"></i> <?= date('h:i A', strtotime($n['created_at'])) ?></span>
                            </div>

                            <div class="notice-content text-muted">
                                <?= nl2br(s($n['content'])) ?>
                            </div>
                        </div>
                        
                        <div class="card-footer-custom d-flex justify-content-between">
                            <span>Posted by Admin</span>
                            <span><i class="bi bi-eye"></i> Public</span>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <div class="bg-white p-5 rounded-4 shadow-sm d-inline-block">
                <i class="bi bi-clipboard-x display-1 text-muted opacity-25 mb-3"></i>
                <h4 class="fw-bold text-dark">No Notices Yet</h4>
                <p class="text-muted mb-0">There are no active announcements to display right now.</p>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
    // Auto-refresh every 60 seconds to fetch new notices
    setInterval(() => location.reload(), 60000);
</script>

<?php include 'footer.php'; ?>