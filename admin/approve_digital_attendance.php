<?php
// approve_digital_attendance.php - Modern Admin Console
session_start();
require_once '../admin/database.php';

date_default_timezone_set('Asia/Kolkata');

// --- 1. Database Setup (Keep as is, but ensure 'admin_comment' is added for professional rejection) ---
// Note: If running this, ensure to manually add admin_comment column if it doesn't exist:
// ALTER TABLE digital_attendance_requests ADD COLUMN admin_comment TEXT NULL AFTER status;
$conn->query("CREATE TABLE IF NOT EXISTS digital_attendance_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    worker_id INT NOT NULL,
    date DATE NOT NULL,
    photo VARCHAR(255) NOT NULL,
    location VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    admin_comment TEXT NULL, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// --- 2. Handle approval/rejection ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $request_id = (int)$_POST['request_id'];
    $action = $_POST['action'];
    $admin_comment = $_POST['admin_comment'] ?? null; // Added for rejection reason
    $today = date('Y-m-d');
    $msg = null;
    
    $req = $conn->query("SELECT * FROM digital_attendance_requests WHERE id=$request_id")->fetch_assoc();
    
    if ($req && $req['status'] === 'pending') {
        if ($action === 'approve') {
            // Check if attendance already marked (to prevent double entry if admin refreshed)
            $existing_att = $conn->query("SELECT id FROM worker_attendance WHERE worker_id={$req['worker_id']} AND date='$today'")->num_rows;

            if ($existing_att == 0) {
                // Mark attendance for today in worker_attendance table
                $worker_id = $req['worker_id'];
                $location = $req['location'];
                $check_in = date('H:i:s');
                $notes = 'Digital Approved: ' . $location;
                
                $stmt = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status, check_in, notes) VALUES (?, ?, 'Present', ?, ?)");
                $stmt->bind_param('isss', $worker_id, $today, $check_in, $notes);
                $stmt->execute();
            }
            
            $conn->query("UPDATE digital_attendance_requests SET status='approved', admin_comment=NULL WHERE id=$request_id");
            $msg = ['type' => 'success', 'text' => 'Attendance approved and marked!'];
            
        } elseif ($action === 'reject') {
            $safe_comment = $conn->real_escape_string($admin_comment);
            // Update status and add admin comment
            $conn->query("UPDATE digital_attendance_requests SET status='rejected', admin_comment='$safe_comment' WHERE id=$request_id");
            $msg = ['type' => 'danger', 'text' => 'Attendance request rejected. Reason recorded.'];
        }
    }
    // Redirect to clear POST data and show message
    header('Location: approve_digital_attendance.php');
    exit;
}

// Fetch pending requests
$pending = $conn->query("SELECT r.*, w.name FROM digital_attendance_requests r JOIN workers w ON r.worker_id=w.id WHERE r.status='pending' ORDER BY r.date DESC, r.id DESC");

include 'topheader.php';
include 'sidenavbar.php';
// SweetAlert2 CDN
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
?>
<style>
    :root {
        --primary: #4f46e5; /* Indigo */
        --bg-light: #f4f7fa; 
        --text-dark: #1e293b;
        --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.08);
        --border-subtle: 1px solid #e2e8f0;
    }
    body { background-color: var(--bg-light); font-family: 'Plus Jakarta Sans', sans-serif; }

    /* Modern Card Styling */
    .att-card { 
        background: #fff; 
        border-radius: 16px; 
        box-shadow: var(--shadow-soft); 
        padding: 30px; 
        margin-bottom: 25px; 
        border: 1px solid #eef;
        transition: transform 0.3s ease;
    }
    .att-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
    }
    .att-photo { 
        border-radius: 12px; 
        box-shadow: 0 0 0 4px var(--primary), 0 8px 20px rgba(79, 70, 229, 0.2);
        max-width: 150px; 
        height: auto;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .att-photo:hover {
        transform: scale(1.03);
    }

    /* Info Badge Styling */
    .info-label {
        font-weight: 600;
        color: var(--primary);
        margin-right: 10px;
    }
    .info-value {
        font-weight: 500;
        color: var(--text-dark);
    }
    /* Button Styling */
    .btn-approve {
        background: #10b981; /* Emerald Green */
        border-color: #10b981;
        font-weight: 600;
        border-radius: 8px;
    }
    .btn-reject {
        background: #f43f5e; /* Rose Red */
        border-color: #f43f5e;
        font-weight: 600;
        border-radius: 8px;
    }
</style>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h3 class="fw-bolder text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>Pending Digital Attendance Requests</h3>
        <span class="badge bg-primary rounded-pill p-3 fs-6"><?= $pending->num_rows ?> Pending</span>
    </div>

    <?php if(isset($_SESSION['msg'])): 
        $msg = $_SESSION['msg'];
        unset($_SESSION['msg']); // Clear message after display
        ?>
        <script>
            Swal.fire({
                icon: '<?= $msg['type'] == 'success' ? 'success' : ($msg['type'] == 'danger' ? 'error' : 'info') ?>',
                title: 'Attendance Action',
                text: '<?= $msg['text'] ?>',
                confirmButtonColor: 'var(--primary)'
            });
        </script>
    <?php endif; ?>

    <div class="row">
    <?php if($pending->num_rows > 0): ?>
    <?php while($row = $pending->fetch_assoc()): ?>
        <div class="col-lg-10 offset-lg-1">
            <div class="att-card">
                <div class="row align-items-center">
                    
                    <div class="col-md-2 text-center">
                        <?php
                        $photo = $row['photo'];
                        // Assuming $photo is already a direct Cloudinary URL or full path
                        $img_src = htmlspecialchars($photo); 
                        ?>
                        <img src="<?= $img_src ?>" class="att-photo mb-2" alt="Worker Photo" onclick="showImageModal('<?= $img_src ?>')">
                    </div>
                    
                    <div class="col-md-7">
                        <h4 class="fw-bolder mb-2 text-dark"><?= htmlspecialchars($row['name']) ?> 
                            <small class="badge bg-warning text-dark ms-2 fw-normal">#<?= $row['worker_id'] ?></small>
                        </h4>
                        
                        <p class="mb-1 text-muted">
                            <span class="info-label"><i class="bi bi-calendar-check me-1"></i>Date:</span>
                            <span class="info-value"><?= date('F j, Y', strtotime($row['date'])) ?></span>
                        </p>
                        
                        <p class="mb-0 text-muted">
                            <span class="info-label"><i class="bi bi-geo-alt-fill me-1"></i>Location:</span>
                            <span class="info-value text-wrap text-break" id="location-<?= $row['id'] ?>"><?= htmlspecialchars($row['location']) ?></span>
                            <span id="coords-<?= $row['id'] ?>" class="d-none"><?= htmlspecialchars($row['location']) ?></span>
                            <small class="text-secondary d-block mt-1">Request ID: <?= $row['id'] ?></small>
                        </p>
                    </div>
                    
                    <div class="col-md-3 text-end">
                        <button type="button" onclick="handleApprove(<?= $row['id'] ?>)" class="btn btn-approve w-100 mb-2"><i class="bi bi-check-circle-fill me-1"></i> Approve</button>
                        <button type="button" onclick="handleReject(<?= $row['id'] ?>)" class="btn btn-reject w-100"><i class="bi bi-x-octagon-fill me-1"></i> Reject</button>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-4 rounded-3">
                <i class="bi bi-check-all me-2"></i>Hooray! No pending digital attendance requests right now.
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<form id="actionForm" method="POST" style="display:none;">
    <input type="hidden" name="request_id" id="formRequestId">
    <input type="hidden" name="action" id="formAction">
    <input type="hidden" name="admin_comment" id="formAdminComment">
</form>

<?php include 'downfooter.php'; ?>

<script>
// --- 1. Image Modal Function ---
function showImageModal(imgUrl) {
    Swal.fire({
        title: 'Worker Photo Preview',
        imageUrl: imgUrl,
        imageAlt: 'Worker Photo',
        showCloseButton: true,
        width: 450,
        customClass: {
            image: 'rounded-3',
            confirmButton: 'd-none'
        }
    });
}

// --- 2. Approval Handler ---
function handleApprove(requestId) {
    Swal.fire({
        title: 'Confirm Approval?',
        text: 'This will mark the worker as Present for today.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Approve',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#10b981'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formRequestId').value = requestId;
            document.getElementById('formAction').value = 'approve';
            document.getElementById('formAdminComment').value = ''; 
            document.getElementById('actionForm').submit();
        }
    });
}

// --- 3. Rejection Handler (with Comment Input) ---
function handleReject(requestId) {
    Swal.fire({
        title: 'Reject Attendance Request',
        text: 'Please provide a clear reason for rejection.',
        input: 'text',
        inputPlaceholder: 'e.g., Photo unclear, Incorrect location, etc.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Reject Request',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#f43f5e',
        preConfirm: (reason) => {
            if (!reason) {
                Swal.showValidationMessage('Rejection reason is mandatory.');
            }
            return reason;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('formRequestId').value = requestId;
            document.getElementById('formAction').value = 'reject';
            document.getElementById('formAdminComment').value = result.value;
            document.getElementById('actionForm').submit();
        }
    });
}

// --- 4. Reverse Geocoding for better location display (Requires Google Maps API Key) ---
window.addEventListener('DOMContentLoaded', function() {
    var apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // !!! IMPORTANT: REPLACE WITH YOUR API KEY !!!

    if (!apiKey || apiKey === 'YOUR_GOOGLE_MAPS_API_KEY') {
         console.warn("Google Maps API key missing. Location coordinates will be displayed.");
         return;
    }

    document.querySelectorAll('[id^="coords-"]').forEach(coordElement => {
        const id = coordElement.id.split('-')[1];
        const loc = coordElement.innerText;
        const displayElement = document.getElementById(`location-${id}`);

        if(loc.match(/^-?\d+\.\d+,-?\d+\.\d+$/)) {
            const latlng = loc.split(',');
            const url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latlng[0]},${latlng[1]}&key=${apiKey}`;
            
            fetch(url)
            .then(r => r.json())
            .then(data => {
                let name = loc;
                if(data.status === 'OK' && data.results && data.results.length > 0) {
                    name = data.results[0].formatted_address; 
                }
                if(displayElement) displayElement.innerText = name;
            })
            .catch(function() {
                if(displayElement) displayElement.innerText = loc + ' (Name Fetch Error)';
            });
        }
    });
});
</script>