
<?php
include 'topheader.php';
include 'sidenavbar.php';
require_once __DIR__ . '/lib_common.php';

$message = '';
$error = '';
$today = date('Y-m-d');

// Ensure worker_attendance has check_in/check_out columns for new features
$hasCheckIn = false; $hasCheckOut = false;
if ($res = $conn->query("SHOW COLUMNS FROM worker_attendance LIKE 'check_in'")) { $hasCheckIn = $res->num_rows > 0; $res->free(); }
if ($res = $conn->query("SHOW COLUMNS FROM worker_attendance LIKE 'check_out'")) { $hasCheckOut = $res->num_rows > 0; $res->free(); }
if (!$hasCheckIn) { $conn->query("ALTER TABLE worker_attendance ADD COLUMN check_in TIME NULL AFTER status"); $hasCheckIn = true; }
if (!$hasCheckOut) { $conn->query("ALTER TABLE worker_attendance ADD COLUMN check_out TIME NULL AFTER check_in"); $hasCheckOut = true; }

// --- Attendance Mode Logic ---
$attendance_mode = get_setting('attendance_mode', 'simple');
// Helper to determine status based on check-in/out
function get_attendance_status($check_in, $check_out, $mode = 'simple') {
    if ($mode === 'simple') return 'Present';
    // Two-time mode logic
    if (!$check_in) return 'Absent';
    $in = strtotime($check_in);
    $out = $check_out ? strtotime($check_out) : null;
    // Entry allowed 5:00-11:00, half day if exit 11:50-14:00, full if exit 16:00-19:00 or after
    $t_5am = strtotime('05:00:00');
    $t_11am = strtotime('11:00:00');
    $t_1150am = strtotime('11:50:00');
    $t_2pm = strtotime('14:00:00');
    $t_4pm = strtotime('16:00:00');
    $t_7pm = strtotime('19:00:00');
    if ($in < $t_5am || $in > $t_11am) return 'Absent';
    if ($out) {
        if ($out >= $t_1150am && $out <= $t_2pm) return 'Half Day';
        if ($out >= $t_4pm) return 'Present';
        // If exit before 11:50am, treat as absent
        if ($out < $t_1150am) return 'Absent';
    }
    // If no exit, but entry is valid, default to Present (can be adjusted)
    return 'Present';
}

// Handle Manual Attendance Marking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_manual'])) {
    $worker_id = (int)$_POST['worker_id'];
    $date = $_POST['date'] ?? $today;
    $check_in = $_POST['check_in'] ?? null;
    $check_out = $_POST['check_out'] ?? null;

    // Normalize time HH:MM to HH:MM:SS, allow nulls
    if ($check_in === '') { $check_in = null; }
    if ($check_out === '') { $check_out = null; }
    if ($check_in && strlen($check_in) === 5) { $check_in .= ':00'; }
    if ($check_out && strlen($check_out) === 5) { $check_out .= ':00'; }
    $notes = trim($_POST['notes'] ?? '');

    // Determine status based on mode
    $status = get_attendance_status($check_in, $check_out, $attendance_mode);

    if ($worker_id <= 0) {
        $error = 'Please select a worker.';
    } else {
        // Check if attendance already marked for this worker on this date
        $stmt = $conn->prepare("SELECT id FROM worker_attendance WHERE worker_id=? AND date=?");
        $stmt->bind_param("is", $worker_id, $date);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Update existing
            $stmt2 = $conn->prepare("UPDATE worker_attendance SET status=?, check_in=?, check_out=?, notes=? WHERE worker_id=? AND date=?");
            $stmt2->bind_param("ssssis", $status, $check_in, $check_out, $notes, $worker_id, $date);
            if ($stmt2->execute()) {
                $message = '✅ Attendance updated successfully!';
            } else {
                $error = 'Failed to update attendance.';
            }
        } else {
            // Insert new
            $stmt2 = $conn->prepare("INSERT INTO worker_attendance (worker_id, date, status, check_in, check_out, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("isssss", $worker_id, $date, $status, $check_in, $check_out, $notes);
            if ($stmt2->execute()) {
                $message = '✅ Attendance marked successfully!';
            } else {
                $error = 'Failed to mark attendance.';
            }
        }
    }
}

// Fetch all workers for dropdown
$workers = $conn->query("SELECT id, name, email FROM workers ORDER BY name ASC");

// Fetch today's attendance with safe ordering
$orderBy = $hasCheckIn ? "wa.check_in DESC, wa.id DESC" : "wa.id DESC";
$sqlToday = "SELECT wa.*, w.name, w.email
             FROM worker_attendance wa
             JOIN workers w ON wa.worker_id = w.id
             WHERE wa.date = '$today'
             ORDER BY $orderBy";
$today_attendance = $conn->query($sqlToday);
?>

<style>
    .qr-scanner-container {
        max-width: 500px;
        margin: 0 auto 1rem auto;
        position: relative;
    }
    #qr-reader {
        border: 2px dashed #007bff;
        border-radius: 8px;
        position: relative;
        z-index: 1;
    }
    .attendance-card {
        transition: transform 0.2s;
    }
    .attendance-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .qr-controls {
        position: relative;
        z-index: 3;
    }
    /* Avoid clipping inside tabs */
    .tab-content { overflow: visible; }
</style>

<div class="container py-4">
    <h2 class="mb-4 text-primary"><i class="bi bi-calendar-check me-2"></i>Attendance Marking</h2>

    <!-- Attendance Mode Info -->
    <div class="alert alert-info mb-3">
        <b>Attendance Mode:</b> <?php echo $attendance_mode==='twotime' ? 'Two-time (Entry/Exit, Half/Full Day)' : 'Simple (Any time, Full Day)'; ?><br>
        <?php if ($attendance_mode==='twotime'): ?>
            <ul class="mb-0">
                <li>Entry allowed <b>5:00 AM to 11:00 AM</b> (India time)</li>
                <li>If exit between <b>11:50 AM and 2:00 PM</b>, <span class="text-warning fw-bold">Half Day</span> is marked</li>
                <li>If exit between <b>4:00 PM and 7:00 PM</b> or after, <span class="text-success fw-bold">Full Day</span> is marked</li>
                <li>Entry or exit outside these times: <span class="text-danger fw-bold">Absent</span></li>
            </ul>
        <?php else: ?>
            Attendance can be marked any time, always counted as <span class="text-success fw-bold">Full Day</span>.
        <?php endif; ?>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabs for Manual and QR -->
    <ul class="nav nav-tabs mb-4" id="attendanceTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="manual-tab" data-bs-toggle="tab" data-bs-target="#manual" type="button">
                <i class="bi bi-pencil-square me-1"></i> Manual Attendance
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr" type="button">
                <i class="bi bi-qr-code-scan me-1"></i> QR Code Scanner
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="face-tab" data-bs-toggle="tab" data-bs-target="#face" type="button">
                <i class="bi bi-person-bounding-box me-1"></i> Face Attendance
            </button>
        </li>
    </ul>

    <div class="tab-content" id="attendanceTabContent">
        <!-- Manual Attendance Tab -->
        <div class="tab-pane fade show active" id="manual" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Mark Attendance Manually</h5>
                </div>
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Select Worker *</label>
                            <select name="worker_id" class="form-select" required>
                                <option value="">Choose worker...</option>
                                <?php while ($worker = $workers->fetch_assoc()): ?>
                                    <option value="<?= $worker['id'] ?>">
                                        <?= htmlspecialchars($worker['name']) ?> (<?= htmlspecialchars($worker['email']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date *</label>
                            <input type="date" name="date" class="form-control" value="<?= $today ?>" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status *</label>
                            <select name="status" class="form-select" required>
                                <option value="Present">Present</option>
                                <option value="Absent">Absent</option>
                                <option value="Half Day">Half Day</option>
                                <option value="Leave">Leave</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Check In Time</label>
                            <input type="time" name="check_in" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Check Out Time</label>
                            <input type="time" name="check_out" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Notes (Optional)</label>
                            <input type="text" name="notes" class="form-control" placeholder="Any additional notes...">
                        </div>
                        <div class="col-12 text-end">
                            <button type="submit" name="mark_manual" class="btn btn-success">
                                <i class="bi bi-check-circle me-1"></i> Mark Attendance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- QR Code Scanner Tab -->
        <div class="tab-pane fade" id="qr" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-qr-code-scan me-2"></i>Scan QR Code</h5>
                </div>
                <div class="card-body">
                        <div class="qr-scanner-container">
                            <div class="d-flex flex-wrap gap-2 align-items-end mb-3 qr-controls">
                                <div class="flex-grow-1">
                                    <label class="form-label">Camera</label>
                                    <select id="qr-camera-select" class="form-select"></select>
                                </div>
                                <div>
                                    <button type="button" id="qr-start" class="btn btn-success"><i class="bi bi-play-circle"></i> Start</button>
                                    <button type="button" id="qr-stop" class="btn btn-outline-danger" disabled><i class="bi bi-stop-circle"></i> Stop</button>
                                </div>
                                <!-- Removed Scan from Image and Permission button -->
                            </div>
                            <div id="qr-reader" style="width: 100%;"></div>
                            <div id="qr-result" class="mt-3 text-center"></div>
                        </div>
                        <div id="qr-help" class="alert alert-info mt-3">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>How to use:</strong> Workers should show their QR code (generated from their profile). 
                            Scan it with the camera to mark attendance instantly. If the camera is blocked, allow camera permissions in your browser. For mobile/LAN access, use HTTPS or localhost; browsers may block camera on insecure (http) origins.
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Worker Info Modal (NEW, for QR scan only) -->
        <div class="modal fade" id="qrWorkerInfoModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="bi bi-qr-code-scan me-2"></i>QR Worker Profile</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center" id="qrWorkerInfoBody">
                        <!-- QR worker info will be injected here -->
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-success" id="qrBtnConfirm" style="display:none;">Confirm</button>
                        <button type="button" class="btn btn-secondary" id="qrBtnCancel" style="display:none;">Cancel</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Face Attendance Tab -->
        <div class="tab-pane fade" id="face" role="tabpanel">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-person-bounding-box me-2"></i>Face Attendance (Live Scan)</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
                        <div class="flex-grow-1">
                            <label class="form-label">Camera</label>
                            <select id="face-camera-select" class="form-select"></select>
                        </div>
                        <div>
                            <button type="button" id="face-start" class="btn btn-success"><i class="bi bi-play-circle"></i> Start</button>
                            <button type="button" id="face-stop" class="btn btn-outline-danger" disabled><i class="bi bi-stop-circle"></i> Stop</button>
                        </div>
                    </div>
                    <div class="qr-scanner-container">
                        <video id="faceVideo" style="width:100%;max-width:400px;border-radius:12px;" autoplay playsinline></video>
                        <canvas id="faceCanvas" style="display:none;"></canvas>
                        <div id="faceResult" class="mt-3"></div>
                    </div>
                </div>
            </div>
            <!-- Worker Info Modal -->
            <div class="modal fade" id="workerInfoModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title"><i class="bi bi-person-bounding-box me-2"></i>Worker Profile</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center" id="workerInfoBody">
                            <!-- Worker info will be injected here -->
                        </div>
                        <div class="modal-footer justify-content-center">
                            <button type="button" class="btn btn-success" id="btnConfirm" style="display:none;">Confirm</button>
                            <button type="button" class="btn btn-secondary" id="btnCancel" style="display:none;">Cancel</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
<script>

// Face Attendance Live Scan Logic (Start/Stop)
const faceVideo = document.getElementById('faceVideo');
const faceCanvas = document.getElementById('faceCanvas');
const faceResult = document.getElementById('faceResult');
let workerInfo = null;
let faceScanInterval = null;
let faceStream = null;

const faceStartBtn = document.getElementById('face-start');
const faceStopBtn = document.getElementById('face-stop');

// Camera selection for face attendance
const faceCameraSelect = document.getElementById('face-camera-select');
let faceCameraId = null;

function populateFaceCameras() {
    faceCameraSelect.innerHTML = '<option value="">Detecting cameras…</option>';
    if (!navigator.mediaDevices || !navigator.mediaDevices.enumerateDevices) {
        faceCameraSelect.innerHTML = '<option value="">Camera API not supported</option>';
        return;
    }
    navigator.mediaDevices.enumerateDevices().then(devices => {
        const cams = devices.filter(d => d.kind === 'videoinput');
        faceCameraSelect.innerHTML = '';
        if (cams.length === 0) {
            faceCameraSelect.innerHTML = '<option value="">No camera found</option>';
            return;
        }
        // Try to label front/back cameras explicitly
        let hasFront = false, hasBack = false;
        cams.forEach((d, idx) => {
            let label = d.label || `Camera ${idx+1}`;
            let lower = label.toLowerCase();
            let opt = document.createElement('option');
            opt.value = d.deviceId;
            if (/back|environment|rear/.test(lower)) {
                opt.textContent = 'Back Camera';
                hasBack = true;
            } else if (/front|user/.test(lower)) {
                opt.textContent = 'Front Camera';
                hasFront = true;
            } else {
                opt.textContent = label;
            }
            faceCameraSelect.appendChild(opt);
        });
        // If both front and back are present, prefer back; else first
        const opts = Array.from(faceCameraSelect.options);
        let back = opts.find(o => o.textContent === 'Back Camera');
        let front = opts.find(o => o.textContent === 'Front Camera');
        if (back) back.selected = true;
        else if (front) front.selected = true;
        else faceCameraSelect.selectedIndex = 0;
        faceCameraId = faceCameraSelect.value;
    }).catch(() => {
        faceCameraSelect.innerHTML = '<option value="">Unable to enumerate cameras</option>';
    });
}

faceCameraSelect.addEventListener('change', function() {
    faceCameraId = faceCameraSelect.value;
    if (faceScanInterval) {
        stopFaceScan();
        startFaceScan();
    }
});

function showWorkerInfoModal(info) {
    fetch('worker_info_api.php?id=' + encodeURIComponent(info.user_id))
        .then(res => res.json())
        .then(data => {
            let w = data && data.worker ? data.worker : info;
            let html = `
                <div class='card shadow-lg border-0 p-0 mb-2' style='max-width:500px;min-width:340px;margin:auto;background:#fff;'>
                  <div class='d-flex align-items-center justify-content-between px-3 pt-3 pb-1'>
                    <div class='d-flex align-items-center gap-2'>
                      <span style='font-size:1.5rem;'><i class="bi bi-person-badge-fill text-warning"></i></span>
                      <span class='fw-bold text-primary' style='font-size:1.25rem;'>Worker Data Card</span>
                    </div>
                    <img src='../admin/assets/111.png' alt='Samrat Construction' style='height:32px;'>
                  </div>
                  <div class='d-flex align-items-center gap-3 px-3 py-2'>
                    <div style='flex:0 0 90px;'>
                      <img src='${w.photo ? w.photo : 'https://ui-avatars.com/api/?name='+encodeURIComponent(w.name)+'&size=120'}' width='110' height='140' style='object-fit:cover;border-radius:10px;border:2px solid #1976d2;background:#fff;'>
                    </div>
                    <div style='flex:1 1 0; text-align:left;'>
                      <div class='fw-bold text-primary mb-1' style='font-size:1rem;'>${w.name}</div>
                      <div class='mb-1'><span class='fw-bold'>Email:</span> <span style='font-size:0.98rem;'>${w.email || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Phone:</span> <span style='font-size:0.98rem;'>${w.phone || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Aadhaar:</span> <span style='font-size:0.98rem;'>${w.aadhaar || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Joining:</span> <span style='font-size:0.98rem;'>${w.joining_date || '-'}</span></div>
                    </div>
                  </div>
                  <div class='px-3 pb-2 pt-1 text-end text-secondary' style='font-size:0.92rem;'>
                    Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                  </div>
                </div>
            `;
            document.getElementById('workerInfoBody').innerHTML = html;
            document.getElementById('btnConfirm').style.display = '';
            document.getElementById('btnCancel').style.display = '';
            let modal = new bootstrap.Modal(document.getElementById('workerInfoModal'));
            modal.show();
        });
}

function startFaceScan() {
    if (faceScanInterval) return; // Prevent multiple scans
    let constraints = { video: true };
    if (faceCameraId) constraints = { video: { deviceId: { exact: faceCameraId } } };
    if (faceVideo && navigator.mediaDevices) {
        navigator.mediaDevices.getUserMedia(constraints)
        .then(stream => {
            faceVideo.srcObject = stream;
            faceStream = stream;
        })
        .catch(err => { faceResult.innerHTML = '<div class="alert alert-danger">Camera access failed!</div>'; });
    }
    faceScanInterval = setInterval(() => {
        if (faceVideo.readyState === 4) {
            faceCanvas.width = faceVideo.videoWidth;
            faceCanvas.height = faceVideo.videoHeight;
            faceCanvas.getContext('2d').drawImage(faceVideo, 0, 0);
            faceCanvas.toBlob(function(blob) {
                let formData = new FormData();
                formData.append('image', blob, 'scan.jpg');
                fetch('face_attendance/scan_backend.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.status === 'success') {
                        workerInfo = data.user;
                        showWorkerInfoModal(workerInfo);
                        faceResult.innerHTML = `<div class='alert alert-success'>Attendance marked for <b>${workerInfo.name}</b> (ID: ${workerInfo.user_id})</div>`;
                        // Play thank you sound
                        try {
                            let audio = document.getElementById('thankyou-audio');
                            if (!audio) {
                                audio = document.createElement('audio');
                                audio.id = 'thankyou-audio';
                                audio.src = '../sounds/thankyou.mp3';
                                audio.preload = 'auto';
                                document.body.appendChild(audio);
                            }
                            audio.currentTime = 0;
                            audio.play();
                        } catch(e) { console.warn('Audio play failed', e); }
                        stopFaceScan();
                    } else {
                        faceResult.innerHTML = `<div class='alert alert-danger'>${data.msg}</div>`;
                    }
                })
                .catch(() => {
                    faceResult.innerHTML = '<div class="alert alert-danger">Scan failed!</div>';
                });
            }, 'image/jpeg');
        }
    }, 4000);
    faceStartBtn.disabled = true;
    faceStopBtn.disabled = false;
}

function stopFaceScan() {
    if (faceScanInterval) {
        clearInterval(faceScanInterval);
        faceScanInterval = null;
    }
    if (faceVideo && faceVideo.srcObject) {
        faceVideo.srcObject.getTracks().forEach(t => t.stop());
        faceVideo.srcObject = null;
    }
    faceResult.innerHTML = '';
    faceStartBtn.disabled = false;
    faceStopBtn.disabled = true;
}

document.addEventListener('DOMContentLoaded', function() {
    // Wire up Start/Stop buttons for face attendance
    faceStartBtn.onclick = function() {
        startFaceScan();
    };
    faceStopBtn.onclick = function() {
        stopFaceScan();
    };
    // Confirm/Cancel button logic
    document.getElementById('btnConfirm').onclick = function() {
        let modal = bootstrap.Modal.getInstance(document.getElementById('workerInfoModal'));
        modal.hide();
        // Mark attendance via AJAX
        if (workerInfo && workerInfo.user_id) {
            let formData = new FormData();
            formData.append('worker_id', workerInfo.user_id);
            formData.append('action', 'qr_mark');
            formData.append('date', new Date().toISOString().slice(0,10));
            formData.append('status', 'Present');
            formData.append('check_in', new Date().toTimeString().slice(0, 5));
            fetch('attendance_qr_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                let camModal = bootstrap.Modal.getInstance(document.getElementById('faceCameraModal'));
                if (camModal) camModal.hide();
                if (data.success) {
                    faceResult.innerHTML = `<div class='alert alert-success'>Attendance marked for <b>${workerInfo.name}</b> (ID: ${workerInfo.user_id})</div>`;
                } else {
                    faceResult.innerHTML = `<div class='alert alert-danger'>${data.message || 'Failed to mark attendance.'}</div>`;
                }
            })
            .catch(() => {
                let camModal = bootstrap.Modal.getInstance(document.getElementById('faceCameraModal'));
                if (camModal) camModal.hide();
                faceResult.innerHTML = '<div class="alert alert-danger">Network error while marking attendance.</div>';
            });
        }
    };
    document.getElementById('btnCancel').onclick = function() {
        let modal = bootstrap.Modal.getInstance(document.getElementById('workerInfoModal'));
        modal.hide();
        faceResult.innerHTML = `<div class='alert alert-warning'>Scan cancelled.</div>`;
    };
    // Populate face cameras on load
    populateFaceCameras();
});
</script>
    </div>

    <!-- Today's Attendance Summary -->
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Today's Attendance (<?= date('d M Y') ?>)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Worker</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Source</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($today_attendance && $today_attendance->num_rows > 0): ?>
                            <?php
                            // Helper for status badge
                            function badge_class($status) {
                                if ($status == 'Present') return 'success';
                                if ($status == 'Absent') return 'danger';
                                if ($status == 'Half Day') return 'warning';
                                if ($status == 'Leave') return 'info';
                                return 'secondary';
                            }
                            // Helper for status logic (same as backend)
                            function get_attendance_status_ui($check_in, $check_out, $mode = 'simple') {
                                if ($mode === 'simple') return 'Present';
                                if (!$check_in) return 'Absent';
                                $in = strtotime($check_in);
                                $out = $check_out ? strtotime($check_out) : null;
                                $t_5am = strtotime('05:00:00');
                                $t_11am = strtotime('11:00:00');
                                $t_1150am = strtotime('11:50:00');
                                $t_2pm = strtotime('14:00:00');
                                $t_4pm = strtotime('16:00:00');
                                $t_7pm = strtotime('19:00:00');
                                if ($in < $t_5am || $in > $t_11am) return 'Absent';
                                if ($out) {
                                    if ($out >= $t_1150am && $out <= $t_2pm) return 'Half Day';
                                    if ($out >= $t_4pm) return 'Present';
                                    if ($out < $t_1150am) return 'Absent';
                                }
                                return 'Present';
                            }
                            while ($att = $today_attendance->fetch_assoc()):
                                $status = ($attendance_mode==='twotime')
                                    ? get_attendance_status_ui($att['check_in'], $att['check_out'], 'twotime')
                                    : ($att['status'] ?? 'Present');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($att['name']) ?></td>
                                    <td><?= htmlspecialchars($att['email']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= badge_class($status) ?>"><?= htmlspecialchars($status) ?></span>
                                    </td>
                                    <td><?= $att['check_in'] ? date('h:i A', strtotime($att['check_in'])) : '-' ?></td>
                                    <td><?= $att['check_out'] ? date('h:i A', strtotime($att['check_out'])) : '-' ?></td>
                                    <td>
                                        <?php
                                        $src = '-';
                                        if (isset($att['notes'])) {
                                            if (stripos($att['notes'], 'face') !== false) $src = 'Face Mark';
                                            elseif (stripos($att['notes'], 'qr') !== false) $src = 'QR Mark';
                                            elseif (stripos($att['notes'], 'manual') !== false) $src = 'Manual';
                                            else $src = 'Manual';
                                        }
                                        echo $src;
                                        ?>
                                    </td>
                                    <td><?= htmlspecialchars($att['notes'] ?: '-') ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fa-2x mb-2"></i><br>
                                    No attendance marked today yet.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'downfooter.php'; ?>

<!-- QR Code Scanner Library with CDN fallback -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js" onerror="(function(){var s=document.createElement('script');s.src='https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js';document.head.appendChild(s);})();"></script>
<!-- Face recognition dependencies (TFJS 1.x + face-api.js) -->
<script defer src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@1.7.4/dist/tf.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs-backend-wasm@1.7.4/dist/tf-backend-wasm.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script>
// QR Code Scanner Implementation
let html5QrCode;
let currentCameraId = null;
let autoStartAttempted = false;

const qrReaderEl = document.getElementById('qr-reader');
const qrResultEl = document.getElementById('qr-result');
const qrHelpEl = document.getElementById('qr-help');
const cameraSelectEl = document.getElementById('qr-camera-select');
const startBtn = document.getElementById('qr-start');
const stopBtn = document.getElementById('qr-stop');
const fileInput = document.getElementById('qr-file');
const permBtn = document.getElementById('qr-permission');

function setButtons(scanning) {
    startBtn.disabled = scanning;
    stopBtn.disabled = !scanning;
}

function showError(message) {
    qrResultEl.innerHTML = `<div class="alert alert-danger">${message}</div>`;
}

function populateCameras() {
    cameraSelectEl.innerHTML = '<option value="">Detecting cameras…</option>';
    return new Promise((resolve) => {
        try {
            if (typeof Html5Qrcode === 'undefined' || !Html5Qrcode.getCameras) {
                showError('Scanner library failed to load. Check your internet connection or try again.');
                cameraSelectEl.innerHTML = '<option value="">Scanner library unavailable</option>';
                return resolve([]);
            }
            Html5Qrcode.getCameras().then(devices => {
                cameraSelectEl.innerHTML = '';
                if (!devices || devices.length === 0) {
                    cameraSelectEl.innerHTML = '<option value="">No camera found</option>';
                    showError('No camera found. If on desktop without webcam, use Scan from Image.');
                    return resolve([]);
                }
                devices.forEach((d, idx) => {
                    const opt = document.createElement('option');
                    opt.value = d.id;
                    opt.textContent = d.label || `Camera ${idx+1}`;
                    cameraSelectEl.appendChild(opt);
                });
                // Prefer back/environment camera; else last camera; else first
                const opts = Array.from(cameraSelectEl.options);
                const back = opts.find(o => /back|environment|rear/i.test(o.textContent));
                if (back) back.selected = true;
                else if (opts.length > 1) cameraSelectEl.selectedIndex = opts.length - 1;
                else cameraSelectEl.selectedIndex = 0;
                resolve(devices);
            }).catch(err => {
                const tip = (location.protocol !== 'https:' && location.hostname !== 'localhost')
                    ? ' Use HTTPS or access via localhost to allow camera.' : '';
                showError('Unable to enumerate cameras. ' + tip);
                cameraSelectEl.innerHTML = '<option value="">Unable to enumerate cameras</option>';
                resolve([]);
            });
        } catch (e) {
            showError('Unexpected error while listing cameras.');
            cameraSelectEl.innerHTML = '<option value="">Error</option>';
            resolve([]);
        }
    });
}

function initQrInstance() {
    if (!html5QrCode) {
        try {
            html5QrCode = new Html5Qrcode("qr-reader");
        } catch (e) {
            showError('Failed to initialize scanner. Try reloading the page.');
        }
    }
}

function startScanner() {
    initQrInstance();
    qrResultEl.innerHTML = '';
    const config = { fps: 12, qrbox: { width: 280, height: 280 }, formatsToSupport: [ Html5QrcodeSupportedFormats.QR_CODE ] };
    const cameraId = (cameraSelectEl.value && cameraSelectEl.value.length > 0) ? cameraSelectEl.value : { facingMode: 'environment' };
    currentCameraId = cameraId;
    html5QrCode.start(cameraId, config, onScanSuccess, onScanError)
        .then(() => setButtons(true))
        .catch(err => {
            const tip = (location.protocol !== 'https:' && location.hostname !== 'localhost')
                ? ' Use HTTPS or access via localhost to allow camera.' : '';
            showError('Camera access denied or not available. ' + tip + (err ? ' [' + err + ']' : ''));
            setButtons(false);
        });
}

function stopScanner() {
    if (html5QrCode) {
        html5QrCode.stop().then(() => setButtons(false)).catch(() => setButtons(false));
    }
}

function autoStartIfPossible() {
    if (autoStartAttempted) return;
    autoStartAttempted = true;
    // Only auto-start if on secure origin or localhost
    if (location.protocol === 'https:' || location.hostname === 'localhost') {
        // Attempt to get permission silently then start
        navigator.mediaDevices && navigator.mediaDevices.getUserMedia({ video: true })
            .then(stream => {
                // Immediately stop the test stream
                stream.getTracks().forEach(t => t.stop());
                // Start scanner after a short delay
                setTimeout(() => startScanner(), 300);
            })
            .catch(() => {/* Permission not granted; user can click Start */});
    }
}

// Populate devices when QR tab is shown (Bootstrap event) and on click fallback
document.getElementById('qr-tab').addEventListener('click', function() {
    setTimeout(() => { populateCameras().then(() => autoStartIfPossible()); }, 200);
});
document.addEventListener('shown.bs.tab', function (event) {
    const target = event.target && event.target.getAttribute('data-bs-target');
    if (target === '#qr') {
        populateCameras().then(() => autoStartIfPossible());
    }
});

// Populate once on page load so dropdown is not empty
document.addEventListener('DOMContentLoaded', function () {
    populateCameras().then(() => {
        // If QR tab is already active by default, try auto-start too
        const qrTabPane = document.querySelector('#qr');
        if (qrTabPane && qrTabPane.classList.contains('active')) {
            autoStartIfPossible();
        }
    });
});

document.getElementById('manual-tab').addEventListener('click', function() { stopScanner(); });

startBtn.addEventListener('click', startScanner);
stopBtn.addEventListener('click', stopScanner);

fileInput.addEventListener('change', async (e) => {
    const file = e.target.files && e.target.files[0];
    if (!file) return;
    stopScanner();
    initQrInstance();
    try {
        const decodedText = await html5QrCode.scanFile(file, true);
        onScanSuccess(decodedText, null);
    } catch (err) {
        showError('Unable to decode QR from image. Try a clearer photo.');
    }
});

permBtn.addEventListener('click', async () => {
    qrResultEl.innerHTML = '';
    try {
        // Request camera permission explicitly
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        // Stop tracks immediately — we only needed permission
        stream.getTracks().forEach(t => t.stop());
        qrResultEl.innerHTML = '<div class="alert alert-success">Camera permission is available. Click Start to begin scanning.</div>';
        // Refresh camera list
        populateCameras();
    } catch (err) {
        const tip = (location.protocol !== 'https:' && location.hostname !== 'localhost')
            ? ' Use HTTPS or access via localhost to allow camera.' : '';
        showError('Cannot access camera: ' + (err && err.message ? err.message : err) + '. ' + tip + ' Check site permission settings (Camera → Allow).');
    }
});
// Remove auto-start; user will click Start. Also avoid duplicate listeners.

function extractWorkerId(text) {
    try {
        // 1) WORKER_ID:123
        let m = text.match(/WORKER_ID\s*[:=]\s*(\d+)/i);
        if (m) return m[1];
        // 2) URL with worker_id param
        if (/^https?:/i.test(text)) {
            const u = new URL(text);
            const p = u.searchParams.get('worker_id') || u.searchParams.get('id');
            if (p && /^\d+$/.test(p)) return p;
        }
        // 3) inline param worker_id=123 or id=123
        m = text.match(/(?:worker[_-]?id|id)\s*[:=]\s*(\d+)/i);
        if (m) return m[1];
        // 4) just digits
        if (/^\d+$/.test(text.trim())) return text.trim();
    } catch (_) {}
    return null;
}

function onScanSuccess(decodedText, decodedResult) {
    const workerId = extractWorkerId(decodedText || '');
    if (workerId) {
        markAttendanceViaQR(workerId);
    } else {
        qrResultEl.innerHTML = '<div class="alert alert-warning">Invalid QR code. Expected worker id.</div>';
    }
}

function onScanError(errorMessage) { /* ignore frequent decode errors */ }

function markAttendanceViaQR(workerId) {
    // Stop scanning temporarily (pause/resume may not exist on all versions)
    if (html5QrCode) {
        try { if (html5QrCode.pause) html5QrCode.pause(); else html5QrCode.stop(); } catch(e) {}
    }
    // Fetch worker info and show QR-specific modal
    fetch('worker_info_api.php?id=' + encodeURIComponent(workerId))
        .then(res => res.json())
        .then(data => {
            let w = data && data.worker ? data.worker : { user_id: workerId };
            let html = `
                <div class='card shadow-lg border-0 p-0 mb-2' style='max-width:500px;min-width:340px;margin:auto;background:#fff;'>
                  <div class='d-flex align-items-center justify-content-between px-3 pt-3 pb-1'>
                    <div class='d-flex align-items-center gap-2'>
                      <span style='font-size:1.5rem;'><i class="bi bi-qr-code-scan text-success"></i></span>
                      <span class='fw-bold text-primary' style='font-size:1.25rem;'>QR Attendance</span>
                    </div>
                    <img src='https://samratpro.in/assets/logo.png' alt='Samrat Construction' style='height:32px;'>
                  </div>
                  <div class='px-3 text-secondary' style='font-size:0.98rem;'>Samrat Construction</div>
                  <div class='d-flex align-items-center gap-3 px-3 py-2'>
                    <div style='flex:0 0 90px;'>
                      <img src='${w.photo ? w.photo : 'https://ui-avatars.com/api/?name='+encodeURIComponent(w.name || '')+'&size=120'}' width='90' height='90' style='object-fit:cover;border-radius:10px;border:2px solid #1976d2;background:#fff;'>
                    </div>
                    <div style='flex:1 1 0; text-align:left;'>
                      <div class='fw-bold text-primary mb-1' style='font-size:1.08rem;'>${w.name || '-'}</div>
                      <div class='mb-1'><span class='fw-bold'>Email:</span> <span style='font-size:0.98rem;'>${w.email || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Phone:</span> <span style='font-size:0.98rem;'>${w.phone || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Aadhaar:</span> <span style='font-size:0.98rem;'>${w.aadhaar || '-'}</span></div>
                      <div class='mb-1'><span class='fw-bold'>Joining:</span> <span style='font-size:0.98rem;'>${w.joining_date || '-'}</span></div>
                    </div>
                  </div>
                  <div class='px-3 pb-2 pt-1 text-end text-secondary' style='font-size:0.92rem;'>
                    Generated on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                  </div>
                </div>
            `;
            document.getElementById('qrWorkerInfoBody').innerHTML = html;
            document.getElementById('qrBtnConfirm').style.display = '';
            document.getElementById('qrBtnCancel').style.display = '';
            let qrModal = new bootstrap.Modal(document.getElementById('qrWorkerInfoModal'));
            qrModal.show();
            // On confirm, mark attendance
            document.getElementById('qrBtnConfirm').onclick = function() {
                qrModal.hide();
                let formData = new FormData();
                formData.append('worker_id', workerId);
                formData.append('action', 'qr_mark');
                formData.append('date', new Date().toISOString().slice(0,10));
                formData.append('status', 'Present');
                formData.append('check_in', new Date().toTimeString().slice(0, 5));
                fetch('attendance_qr_handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('qr-result').innerHTML = 
                            `<div class="alert alert-success">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                <strong>Success!</strong> Attendance marked for ${w.name || data.worker_name}
                            </div>`;
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        document.getElementById('qr-result').innerHTML = 
                            `<div class="alert alert-danger">${data.message}</div>`;
                        setTimeout(() => {
                            if (html5QrCode) { try { if (html5QrCode.resume) html5QrCode.resume(); else startScanner(); } catch(e) { startScanner(); } }
                        }, 3000);
                    }
                })
                .catch(() => {
                    document.getElementById('qr-result').innerHTML = 
                        '<div class="alert alert-danger">Error processing QR code.</div>';
                    setTimeout(() => {
                        if (html5QrCode) { try { if (html5QrCode.resume) html5QrCode.resume(); else startScanner(); } catch(e) { startScanner(); } }
                    }, 3000);
                });
            };
            // Cancel button logic
            document.getElementById('qrBtnCancel').onclick = function() {
                qrModal.hide();
                if (html5QrCode) { try { if (html5QrCode.resume) html5QrCode.resume(); else startScanner(); } catch(e) { startScanner(); } }
            };
        });
}
</script>

<!-- Face Attendance Script -->
<script>
// Face Attendance Implementation
let faceModelsLoaded = false;
let faceKnown = []; // { id: number, descriptor: Float32Array }
let faceVideo = document.getElementById('face-video');
let faceOverlay = document.getElementById('face-overlay');
let faceCtx = faceOverlay.getContext('2d');
let faceStatus = document.getElementById('face-status');
let faceResult = document.getElementById('face-result');
let faceStartBtn = document.getElementById('face-start');
let faceStopBtn = document.getElementById('face-stop');

// Camera selection for face attendance
let faceCameraSelect = document.getElementById('face-camera-select');
let faceCameraId = null;

function faceSetButtons(scanning) {
    faceStartBtn.disabled = scanning || !faceModelsLoaded || faceKnown.length === 0;
    faceStopBtn.disabled = !scanning;
}

function faceShow(message, type = 'info') {
    faceStatus.className = 'alert alert-' + type + ' py-2 mb-0';
    faceStatus.textContent = message;
}

async function facePopulateCameras() {
    faceCameraSelect.innerHTML = '<option>Detecting cameras…</option>';
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        const videos = devices.filter(d => d.kind === 'videoinput');
        faceCameraSelect.innerHTML = '';
        if (!videos.length) {
            faceCameraSelect.innerHTML = '<option value="">No camera found</option>';
            faceShow('No camera found. Use a device with a camera.', 'danger');
            return [];
        }
        videos.forEach((d, idx) => {
            const opt = document.createElement('option');
            opt.value = d.deviceId;
            opt.textContent = d.label || `Camera ${idx+1}`;
            faceCameraSelect.appendChild(opt);
        });
        // Prefer back camera
        const opts = Array.from(faceCameraSelect.options);
        const back = opts.find(o => /back|environment|rear/i.test(o.textContent));
        if (back) back.selected = true; else faceCameraSelect.selectedIndex = 0;
        return videos;
    } catch (e) {
        faceShow('Unable to list cameras. Allow camera permission.', 'warning');
        return [];
    }
}

async function faceLoadModels() {
    try {
        await Promise.all([
            faceapi.nets.tinyFaceDetector.loadFromUri('weights'),
            faceapi.nets.faceLandmark68Net.loadFromUri('weights'),
            faceapi.nets.faceRecognitionNet.loadFromUri('weights')
        ]);
        if (tf && tf.setBackend) {
            try { await tf.setBackend('webgl'); await tf.ready(); } catch (_) {}
        }
        faceModelsLoaded = true;
        faceShow('Models ready. Load registered faces…', 'info');
    } catch (e) {
        faceModelsLoaded = false;
        faceShow('Failed to load face models. Check weights folder.', 'danger');
    }
}

async function faceLoadKnown() {
    faceKnown = [];
    try {
        const res = await fetch('workers_face_data.json', { cache: 'no-store' });
        if (!res.ok) throw new Error('No registered faces yet');
        const arr = await res.json();
        if (Array.isArray(arr)) {
            arr.forEach(item => {
                if (item && item.id && Array.isArray(item.descriptor)) {
                    const d = new Float32Array(item.descriptor);
                    if (d.length > 0 && Number.isFinite(d[0])) {
                        faceKnown.push({ id: parseInt(item.id, 10), descriptor: d });
                    }
                }
            });
        }
        if (faceKnown.length === 0) throw new Error('No valid face entries found');
        faceShow(`Loaded ${faceKnown.length} registered face(s).`, 'success');
    } catch (e) {
        faceKnown = [];
        faceShow('No registered faces found. Register workers first.', 'warning');
    }
    faceSetButtons(false);
}

function faceDistance(a, b) {
    let sum = 0.0;
    for (let i = 0; i < a.length; i++) {
        const diff = a[i] - b[i]; sum += diff * diff;
    }
    return Math.sqrt(sum);
}

async function faceStart() {
    if (!faceModelsLoaded) return;
    if (faceKnown.length === 0) { faceShow('Register faces first.', 'warning'); return; }
    faceResult.innerHTML = '';
    // Setup camera
    const deviceId = faceCameraSelect.value;
    const constraints = deviceId ? { video: { deviceId: { exact: deviceId } } } : { video: { facingMode: { ideal: 'environment' } } };
    try {
        const stream = await navigator.mediaDevices.getUserMedia(constraints);
        faceVideo.srcObject = stream;
        await faceVideo.play().catch(()=>{});
        // Resize overlay to match video
        const adjust = () => { faceOverlay.width = faceVideo.videoWidth; faceOverlay.height = faceVideo.videoHeight; };
        adjust();
        faceVideo.addEventListener('loadedmetadata', adjust, { once: true });
        faceScanning = true;
        faceSetButtons(true);
        faceShow('Scanning… Look at the camera.', 'info');
        // Scan loop
        const options = new faceapi.TinyFaceDetectorOptions({ inputSize: 320, scoreThreshold: 0.35 });
        faceInterval = setInterval(async () => {
            if (!faceScanning || faceVideo.readyState < 2) return;
            try {
                const res = await faceapi.detectSingleFace(faceVideo, options).withFaceLandmarks().withFaceDescriptor();
                faceCtx.clearRect(0,0,faceOverlay.width,faceOverlay.height);
                if (res && res.descriptor) {
                    const box = res.detection.box;
                    faceCtx.strokeStyle = '#00e676'; faceCtx.lineWidth = 2; faceCtx.strokeRect(box.x, box.y, box.width, box.height);
                    const best = faceKnown
                        .map(k => ({ id: k.id, dist: faceDistance(k.descriptor, res.descriptor) }))
                        .sort((a,b) => a.dist - b.dist)[0];
                    if (best) {
                        const conf = (1 - Math.min(best.dist / 1.0, 1)); // rough visualization
                        faceResult.innerHTML = `<div class="alert alert-info">Best match: ID ${best.id} (distance ${best.dist.toFixed(3)})</div>`;
                        if (best.dist <= FACE_THRESHOLD) {
                            // Cooldown check
                            const last = faceCooldown.get(best.id) || 0;
                            const now = Date.now();
                            if (now - last > FACE_COOLDOWN_MS) {
                                faceCooldown.set(best.id, now);
                                await faceMark(best.id);
                            }
                        }
                    }
                }
            } catch (e) {
                // ignore frame errors
            }
        }, 800);
    } catch (e) {
        const tip = (location.protocol !== 'https:' && location.hostname !== 'localhost')
            ? ' Use HTTPS or localhost for camera access.' : '';
        faceShow('Cannot access camera.' + tip, 'danger');
    }
}

async function faceStop() {
    faceScanning = false;
    faceSetButtons(false);
    if (faceInterval) { clearInterval(faceInterval); faceInterval = null; }
    if (faceVideo && faceVideo.srcObject) {
        faceVideo.srcObject.getTracks().forEach(t => t.stop());
        faceVideo.srcObject = null;
    }
    faceCtx && faceCtx.clearRect(0,0,faceOverlay.width,faceOverlay.height);
    faceResult.innerHTML = '';
    faceShow('Stopped.', 'secondary');
}

async function faceMark(workerId) {
    // Optional: fetch worker info for display
    try {
        const r = await fetch('worker_info_api.php?id=' + encodeURIComponent(workerId));
        const j = await r.json();
        if (j && j.status === 'success') {
            faceResult.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>Recognized: ${j.worker.name} (ID ${workerId}). Marking attendance…</div>`;
        }
    } catch (_) {}

    const formData = new FormData();
    formData.append('worker_id', workerId);
    formData.append('action', 'qr_mark'); // reuse existing handler
    formData.append('date', '<?= $today ?>');
    formData.append('status', 'Present');
    formData.append('check_in', new Date().toTimeString().slice(0, 5));

    try {
        const resp = await fetch('attendance_qr_handler.php', { method: 'POST', body: formData });
        const data = await resp.json();
        if (data && data.success) {
            faceResult.innerHTML = `<div class="alert alert-success"><i class=\"bi bi-check-circle-fill me-2\"></i>Attendance marked for ${data.worker_name}</div>`;
            // Optionally reload to reflect table
            setTimeout(() => location.reload(), 1500);
        } else {
            faceResult.innerHTML = `<div class="alert alert-danger">${(data && data.message) || 'Failed to mark attendance.'}</div>`;
        }
    } catch (e) {
        faceResult.innerHTML = '<div class="alert alert-danger">Network error while marking attendance.</div>';
    }
}

// Wiring and lifecycle
document.addEventListener('DOMContentLoaded', async () => {
    // Prepare cameras list and load models + known faces
    await facePopulateCameras();
    // Load models only when Face tab is opened to save time
    const maybeLoad = async () => {
        if (!faceModelsLoaded) await faceLoadModels();
        await faceLoadKnown(); // ensure we have data before enabling start
        faceSetButtons(false);
    };
    document.getElementById('face-tab').addEventListener('click', () => {
        setTimeout(() => { maybeLoad(); }, 200);
    });
    document.addEventListener('shown.bs.tab', function (event) {
        const target = event.target && event.target.getAttribute('data-bs-target');
        if (target === '#face') { maybeLoad(); }
        if (target !== '#face') { faceStop(); }
        if (target === '#qr') { /* QR tab shown */ } else { stopScanner(); }
    });

    document.getElementById('manual-tab').addEventListener('click', () => { faceStop(); });
});

faceStartBtn.addEventListener('click', faceStart);
faceStopBtn.addEventListener('click', faceStop);
</script>
