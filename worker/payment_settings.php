<?php
$page_title = "Payment Settings";
$show_back_btn = true;
include 'header.php';

require_once '../admin/database.php';
@include __DIR__ . '/../admin/analytics_track.php';

// Ensure table exists
$conn->query("CREATE TABLE IF NOT EXISTS worker_payment_settings (
    worker_id INT PRIMARY KEY,
    upi_vpa VARCHAR(120) NULL,
    upi_payee VARCHAR(120) NULL,
    upi_mobile VARCHAR(20) NULL,
    upi_qr_path VARCHAR(255) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$ok = null;
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $vpa = trim($_POST['vpa'] ?? '');
    $payee = trim($_POST['payee'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    try {
        $stmt = $conn->prepare("
            INSERT INTO worker_payment_settings (worker_id, upi_vpa, upi_payee, upi_mobile)
            VALUES (?,?,?,?)
            ON DUPLICATE KEY UPDATE 
            upi_vpa=VALUES(upi_vpa),
            upi_payee=VALUES(upi_payee),
            upi_mobile=VALUES(upi_mobile)
        ");
        // $worker_id is assumed to be defined by included files (like 'header.php')
        $stmt->bind_param('isss', $worker_id, $vpa, $payee, $mobile);
        $stmt->execute();
        $stmt->close();
        $ok = "Settings updated.";
    } catch (Throwable $e) {
        $err = "Failed: " . $e->getMessage();
    }

    // QR Upload
    if (isset($_FILES['qr_image']) && $_FILES['qr_image']['error'] === UPLOAD_ERR_OK) {

        $allowed = ['image/jpeg', 'image/png', 'image/webp'];

        if (!in_array($_FILES['qr_image']['type'], $allowed)) {
            $err = "QR must be JPG, PNG or WEBP";
        } else {

            $dir = __DIR__ . '/../admin/uploads/worker_qr/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $ext = pathinfo($_FILES['qr_image']['name'], PATHINFO_EXTENSION);
            $fname = "worker_qr_{$worker_id}_" . time() . "." . $ext;
            $path = $dir . $fname;

            if (move_uploaded_file($_FILES['qr_image']['tmp_name'], $path)) {
                $rel = "uploads/worker_qr/" . $fname;
                $conn->query("UPDATE worker_payment_settings SET upi_qr_path='".$conn->real_escape_string($rel)."' WHERE worker_id=$worker_id");

                $ok .= " QR Updated.";
            } else {
                $err = "QR upload failed!";
            }
        }
    }
}

// $worker_id is assumed to be defined
$wps = $conn->query("SELECT * FROM worker_payment_settings WHERE worker_id=$worker_id")->fetch_assoc();
?>

<!-- DESIGN: Using Tailwind CSS and Lucide Icons for a modern look -->
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');
    :root {
        --accent: #2563eb; /* blue-600 */
        --accent-2: #60a5fa; /* blue-400 */
    }
    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(180deg, #f6f9ff 0%, #e0e7ff 100%);
        min-height: 100vh;
        padding: 0; /* Remove PHP body padding, handled by container */
    }
    .card-modern {
        background: #fff;
        border-radius: 22px;
        border: 1px solid #e5e7eb;
        box-shadow: 0 8px 32px rgba(37,99,235,0.08);
        overflow: hidden;
        margin-bottom: 32px;
    }
    .header-box {
        background: linear-gradient(135deg, var(--accent), var(--accent-2));
        padding: 24px 18px 18px 18px; 
        color: #fff;
        text-align: center;
        border-radius: 22px 22px 0 0;
    }
    .header-box .lucide-icon { font-size: 2.8rem; width: 2.8rem; height: 2.8rem; margin: 0 auto; }
    .header-box h3 { font-size: 2.1rem; }
    
    /* Custom input styling for a modern look */
    .custom-input-label { 
        font-weight: 600; 
        margin-bottom: 6px; 
        color: var(--accent); 
        display: block;
    }
    .custom-input {
        background-color: #f6f9ff;
        border: 1.5px solid #dbeafe;
        border-radius: 10px;
        font-size: 1.08rem;
        padding: 12px;
        width: 100%;
        transition: box-shadow 0.2s, border-color 0.2s;
    }
    .custom-input:focus {
        box-shadow: 0 0 0 2px rgba(37,99,235,0.2);
        border-color: var(--accent);
        outline: none;
    }
    
    /* Custom button styling */
    .btn-primary-custom {
        background: linear-gradient(90deg, #2563eb, #60a5fa);
        border: none;
        border-radius: 8px;
        font-weight: 600;
        padding: 10px 20px;
        color: white;
        box-shadow: 0 2px 8px rgba(37,99,235,0.13);
        transition: background 0.2s, box-shadow 0.2s;
        display: inline-flex;
        align-items: center;
    }
    .btn-primary-custom:hover {
        background: linear-gradient(90deg, #1d4ed8, #2563eb);
        box-shadow: 0 4px 16px rgba(37,99,235,0.2);
    }
    
    /* Alert styling for PHP messages */
    .alert-box {
        border-radius: 8px;
        padding: 1rem;
        font-size: 1rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
    }
    .alert-success {
        background-color: #d1fae5; /* green-100 */
        border: 1px solid #6ee7b7; /* green-300 */
        color: #065f46; /* green-800 */
    }
    .alert-danger {
        background-color: #fee2e2; /* red-100 */
        border: 1px solid #fca5a5; /* red-300 */
        color: #991b1b; /* red-800 */
    }

    .qr-image {
        box-shadow: 0 2px 12px rgba(37,99,235,0.13);
        border-radius: 14px;
        border: 1px solid #e5e7eb;
    }
    .text-sm-muted { font-size: 0.97rem; color: #6b7280; /* gray-500 */ }
</style>

<!-- Initializing Lucide icons -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>


<div class="max-w-xl mx-auto p-4 md:p-8">
    <!-- HEADER -->
    <div class="card-modern mb-8">
        <div class="header-box">
            <!-- Replacing bi-qr-code with Lucide icon (qr-code) -->
            <i data-lucide="qr-code" class="lucide-icon mx-auto"></i> 
            <h3 class="font-bold mt-2">Payment Settings</h3>
            <div class="opacity-75 text-sm">Manage your UPI details for receiving payments</div>
        </div>
    </div>
    
    <!-- FORM CARD -->
    <div class="card-modern p-6">
        <?php if($ok): ?>
            <!-- Modern success alert -->
            <div class="alert-box alert-success" role="alert">
                <i data-lucide="check-circle" class="w-5 h-5 mr-3 flex-shrink-0"></i> 
                <span><?= htmlspecialchars($ok) ?></span>
            </div>
        <?php endif; ?>
        <?php if($err): ?>
            <!-- Modern error alert -->
            <div class="alert-box alert-danger" role="alert">
                <i data-lucide="x-circle" class="w-5 h-5 mr-3 flex-shrink-0"></i> 
                <span><?= htmlspecialchars($err) ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Replacing Bootstrap grid classes (row g-4) with Tailwind grid (gap-6) -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- UPI VPA (ID) - col-md-4 -->
                <div>
                    <label class="custom-input-label">UPI VPA (ID)</label>
                    <input type="text" name="vpa" class="custom-input" placeholder="e.g. abhay@upi" value="<?= htmlspecialchars($wps['upi_vpa'] ?? '') ?>">
                </div>
                <!-- Payee Name - col-md-4 -->
                <div>
                    <label class="custom-input-label">Payee Name</label>
                    <input type="text" name="payee" class="custom-input" placeholder="e.g. Abhay Prasad" value="<?= htmlspecialchars($wps['upi_payee'] ?? '') ?>">
                </div>
                <!-- Mobile (Optional) - col-md-4 -->
                <div>
                    <label class="custom-input-label">Mobile (Optional)</label>
                    <input type="text" name="mobile" class="custom-input" placeholder="e.g. 9876543210" value="<?= htmlspecialchars($wps['upi_mobile'] ?? '') ?>">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4">
                <!-- QR Upload - col-md-6 -->
                <div>
                    <label class="custom-input-label">Upload New QR</label>
                    <input type="file" name="qr_image" class="custom-input file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200" accept="image/*">
                    <small class="text-sm-muted block mt-1">Upload JPG, PNG or WEBP QR code.</small>
                </div>
                <!-- Current QR - col-md-6 -->
                <div>
                    <label class="custom-input-label">Current QR</label><br>
                    <?php if(!empty($wps['upi_qr_path'])): ?>
                        <!-- Image styled with modern classes -->
                        <img src="../admin/<?= htmlspecialchars($wps['upi_qr_path']) ?>" class="qr-image" style="max-height:180px;">
                    <?php else: ?>
                        <span class="text-sm-muted">No QR uploaded.</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pt-4 text-right">
                <!-- Modern Save button, replacing btn btn-primary -->
                <button type="submit" class="btn-primary-custom">
                    <i data-lucide="save" class="w-5 h-5 mr-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Re-run icon rendering after the PHP content loads
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

<?php include 'footer.php'; ?>