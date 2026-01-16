<?php
// worker_digital_attendance.php - Modern Light Design
session_start();
require_once '../admin/database.php';
date_default_timezone_set('Asia/Kolkata');

$worker_id = $_SESSION['worker_id'] ?? null;
if (!$worker_id) { header('Location: login.php'); exit; }

$msg = null;
$today = date('Y-m-d');

// --- 1. Check existing submission ---
$existing = $conn->query("SELECT * FROM digital_attendance_requests WHERE worker_id=$worker_id AND date='$today' ORDER BY id DESC LIMIT 1")->fetch_assoc();

// --- 2. Handle attendance request submission (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_attendance'])) {
    // If already submitted, block re-upload
    if ($existing) {
        $msg = ['type' => 'info', 'text' => 'Aapne aaj attendance already submit kar diya hai. Neeche status check kar sakte hain.'];
    } else {
        $location = $_POST['location'] ?? '';
        $photo_data = $_POST['photo_data'] ?? '';
        
        if (!$photo_data || !$location) {
            $msg = ['type' => 'error', 'text' => 'Photo and location required!'];
        } else {
            // Cloudinary credentials (replace with your real values)
            $cloud_name = 'dppoq5cqf';
            $upload_url = 'https://api.cloudinary.com/v1_1/' . $cloud_name . '/image/upload';
            $upload_preset = 'unsigned_preset'; // Make sure this preset exists in your Cloudinary dashboard

            $img_data = explode(',', $photo_data)[1];
            $temp_file = tempnam(sys_get_temp_dir(), 'att_');
            file_put_contents($temp_file, base64_decode($img_data));

            $fields = [
                'file' => new CURLFile($temp_file, 'image/jpeg'),
                'upload_preset' => $upload_preset
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $upload_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
            $response = curl_exec($ch);
            $curl_error = curl_error($ch);
            curl_close($ch);
            unlink($temp_file);

            $cloudinary_url = '';
            $error_msg = '';
            if ($response) {
                $resp_data = json_decode($response, true);
                if (isset($resp_data['secure_url'])) {
                    $cloudinary_url = $resp_data['secure_url'];
                } elseif (isset($resp_data['error']['message'])) {
                    $error_msg = $resp_data['error']['message'];
                }
            } else {
                $error_msg = $curl_error ?: 'No response from Cloudinary.';
            }

            if ($cloudinary_url) {
                $stmt = $conn->prepare("INSERT INTO digital_attendance_requests (worker_id, date, photo, location, status) VALUES (?, ?, ?, ?, 'pending')");
                $stmt->bind_param('isss', $worker_id, $today, $cloudinary_url, $location);
                if ($stmt->execute()) {
                    $msg = ['type' => 'success', 'text' => 'Attendance request submitted! Awaiting admin approval.'];
                    // Refresh $existing for status display
                    $existing = $conn->query("SELECT * FROM digital_attendance_requests WHERE worker_id=$worker_id AND date='$today' ORDER BY id DESC LIMIT 1")->fetch_assoc();
                } else {
                    $msg = ['type' => 'error', 'text' => 'Failed to submit attendance request.'];
                }
            } else {
                $msg = ['type' => 'error', 'text' => 'Image upload failed! ' . htmlspecialchars($error_msg)];
            }
        }
    }
}

// --- 3. Include Header and CDNs ---
include 'header.php';
// SweetAlert2 CDN
echo '<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Digital Attendance</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* Define a Primary Color for consistency */
        :root {
            --primary: #4f46e5; /* Indigo */
            --bg-light: #f8fafc; /* Very light gray/off-white */
            --text-dark: #1e293b;
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.08);
            --border-subtle: 1px solid #e2e8f0;
        }

        body {
            background-color: var(--bg-light); /* Light background for the page */
            color: var(--text-dark);
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        /* Modern Card Styling */
        .modern-card { 
            background: #fff; 
            border-radius: 20px; 
            box-shadow: var(--shadow-soft); 
            padding: 40px; 
            border: var(--border-subtle);
            margin-top: 30px;
        }
        
        .card-header-icon {
            color: var(--primary);
            font-size: 2.5rem;
            margin-bottom: 15px;
        }

        /* Video/Camera Preview Styling */
        #video, #photoPreview {
            width: 100% !important;
            max-width: 380px; /* Optimal size */
            height: auto; /* Maintain aspect ratio */
            border-radius: 16px;
            border: 4px solid var(--primary); 
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.1);
            transition: all 0.3s ease;
            display: block;
            margin: 0 auto 15px auto;
        }
        #video.active-capture {
             /* Subtle border glow when stream is active */
            box-shadow: 0 0 0 6px rgba(79, 70, 229, 0.2), 0 0 20px rgba(79, 70, 229, 0.1);
        }
        #photoPreview {
            border-style: dashed;
            cursor: pointer;
        }
        #photoPreview.ready {
            border-style: solid;
        }
        
        /* Button Styling */
        .btn-attendance { 
            background: var(--primary); 
            color: #fff; 
            border-radius: 10px; 
            font-weight: 600;
            padding: 10px 25px;
            transition: background 0.3s;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.3);
        }
        .btn-attendance:hover { 
            background: #4338ca; 
        }
        
        #submitBtn {
            padding: 10px 25px;
            border-radius: 10px;
        }

        /* Status Box */
        .status-box {
            padding: 20px;
            border-radius: 12px;
            margin-top: 25px;
            border: var(--border-subtle);
            background: var(--bg-light);
        }
        .status-box b {
            font-weight: 700;
            color: var(--primary);
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7 col-md-9">
            <div class="modern-card text-center">
                
                <i class="bi bi-geo-alt-fill card-header-icon"></i>
                <h3 class="fw-bolder mb-3 text-dark">Digital Attendance Terminal</h3>
                <p class="text-secondary mb-4">Live photo capture and GPS location are required for request submission.</p>
                
                <?php if($msg): ?>
                    <div class="alert alert-<?= $msg['type'] == 'success' ? 'success' : ($msg['type'] == 'error' ? 'danger' : 'info') ?> py-3 px-4 mb-4 fw-medium" role="alert"> 
                        <i class="bi bi-<?= $msg['type'] == 'success' ? 'check-circle-fill' : ($msg['type'] == 'error' ? 'x-octagon-fill' : 'info-circle-fill') ?> me-2"></i>
                        <?= htmlspecialchars($msg['text']) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="attendanceForm">
                    
                    <div class="mb-4 text-center">
                        <video id="video" autoplay playsinline class="<?= $existing ? 'd-none' : 'active-capture' ?>"></video>
                        <canvas id="canvas" style="display:none;"></canvas>
                        <input type="hidden" name="photo_data" id="photoData">
                        <img id="photoPreview" src="" alt="Captured Photo Preview" class="ready" style="<?= $existing ? 'display:none;' : '' ?>" />
                        
                        <div class="mt-3">
                            <input type="hidden" name="location" id="locationInput">
                            <span id="locationStatus" class="text-muted small fw-medium"><i class="bi bi-pin-map me-1"></i>Location: Detecting...</span>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <?php if(!$existing): ?>
                            <button type="button" class="btn btn-attendance" id="captureBtn"><i class="bi bi-camera me-2"></i> Capture Photo</button>
                            <button type="submit" name="submit_attendance" class="btn btn-success" id="submitBtn" disabled><i class="bi bi-send-check me-2"></i> Submit Request</button>
                        <?php else: ?>
                            <div class="alert alert-info py-2 fw-medium">Attendance already submitted for today.</div>
                        <?php endif; ?>
                    </div>

                    <?php if($existing): ?>
                        <div class="status-box text-start shadow-sm">
                            <p class="mb-2 fw-bold text-dark"><i class="bi bi-calendar-check me-2 text-primary"></i>Today's Request Status:</p>
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <td class="fw-bold p-1">Status:</td>
                                    <td class="p-1">
                                        <span class="badge rounded-pill bg-<?= $existing['status']=='approved'?'success':'warning text-dark' ?> text-uppercase"> 
                                            <?= htmlspecialchars($existing['status']) ?> 
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold p-1">Location:</td>
                                    <td class="p-1"><span id="locationName" class="text-wrap text-break"><?= htmlspecialchars($existing['location']) ?></span></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold p-1">Photo:</td>
                                    <td class="p-1"><a href="<?= htmlspecialchars($existing['photo']) ?>" target="_blank" class="text-primary fw-medium">View Captured Photo</a></td>
                                </tr>
                                <?php if($existing['status'] == 'rejected' && $existing['admin_comment']): ?>
                                <tr>
                                    <td class="fw-bold p-1 text-danger">Reason:</td>
                                    <td class="p-1 text-danger"><?= htmlspecialchars($existing['admin_comment']) ?></td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// ====================================================================
// JAVASCRIPT LOGIC
// ====================================================================

const video = document.getElementById('video');
const canvas = document.getElementById('canvas');
const photoData = document.getElementById('photoData');
const captureBtn = document.getElementById('captureBtn');
const submitBtn = document.getElementById('submitBtn');
const locationInput = document.getElementById('locationInput');
const locationStatus = document.getElementById('locationStatus');
const photoPreview = document.getElementById('photoPreview');
const existingSubmission = <?= json_encode((bool)$existing) ?>;

let cameraStream = null;

// --- 1. Camera Setup ---
if (!existingSubmission) {
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'user' // Prefer front camera for selfie
        } 
    })
    .then(stream => {
        video.srcObject = stream;
        cameraStream = stream;
        video.play();
    })
    .catch(err => {
        console.error("Camera access denied: ", err);
        Swal.fire({
            icon: 'error',
            title: 'Camera Access Denied',
            text: 'Please allow camera access to take your attendance photo.',
            confirmButtonColor: '#4f46e5'
        });
        captureBtn.disabled = true;
    });
}

// --- 2. Capture Photo ---
captureBtn.onclick = function() {
    if (cameraStream) {
        // Stop video stream and hide it
        cameraStream.getTracks().forEach(track => track.stop());
        video.classList.add('d-none');
    }
    
    // Draw frame to canvas
    canvas.width = video.videoWidth || 320;
    canvas.height = video.videoHeight || 240;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    
    // Convert to DataURL
    const dataUrl = canvas.toDataURL('image/jpeg', 0.9); // Use 0.9 quality for balance
    photoData.value = dataUrl;
    
    // Update UI
    submitBtn.disabled = false;
    captureBtn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i> Retake Photo';
    
    photoPreview.src = dataUrl;
    photoPreview.style.display = 'block';
    photoPreview.classList.add('ready');
    
    // Re-initialize camera stream for next retake
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: 'user' 
        } 
    })
    .then(stream => {
        video.srcObject = stream;
        cameraStream = stream;
    })
    .catch(err => {
        // If retake fails, just log it and leave buttons enabled
        console.warn("Retake camera re-initialization failed: ", err);
    });
};

// --- 3. Preview Click (SweetAlert2) ---
photoPreview.onclick = function() {
    if (this.src) {
        Swal.fire({
            title: 'Captured Photo',
            imageUrl: this.src,
            imageAlt: 'Captured Photo',
            showCloseButton: true,
            width: 400,
            customClass: {
                confirmButton: 'd-none'
            }
        });
    }
};

// --- 4. Geolocation ---
function getLocation() {
    if (navigator.geolocation) {
        locationStatus.innerHTML = '<i class="bi bi-pin-map-fill me-1 text-warning"></i>Location: Fetching...';
        navigator.geolocation.getCurrentPosition(function(pos) {
            const coords = pos.coords.latitude + ',' + pos.coords.longitude;
            locationInput.value = coords;
            locationStatus.innerHTML = '<i class="bi bi-pin-map-fill me-1 text-success"></i>Location: Detected (' + pos.coords.latitude.toFixed(4) + ', ' + pos.coords.longitude.toFixed(4) + ')';
        }, function(error) {
            console.error('Geolocation failed: ', error);
            locationStatus.innerHTML = '<i class="bi bi-pin-map-fill me-1 text-danger"></i>Location: Permission Denied!';
            // Disable submit if location is crucial
            submitBtn.disabled = true;
            Swal.fire({
                icon: 'error',
                title: 'Location Required',
                text: 'Please allow location access to submit your attendance.',
                confirmButtonColor: '#4f46e5'
            });
        }, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        });
    } else {
        locationStatus.innerHTML = '<i class="bi bi-pin-map-fill me-1 text-danger"></i>Location: Not supported by device!';
        submitBtn.disabled = true;
    }
}

if (!existingSubmission) {
    getLocation(); // Get location on load if no existing submission
}

// --- 5. Reverse Geocode (Only for Displaying Existing Location Name) ---
<?php if($existing && $existing['location']): ?>
window.addEventListener('DOMContentLoaded', function() {
    var loc = "<?= htmlspecialchars($existing['location']) ?>";
    // NOTE: GOOGLE MAPS API KEY IS REQUIRED HERE
    var apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; // MUST BE REPLACED
    var locationElem = document.getElementById('locationName');
    
    function setSafeLocation(msg) {
        if(locationElem) locationElem.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>' + msg;
    }
    
    setSafeLocation('Fetching name...'); // Initial status

    if (!apiKey || apiKey === 'YOUR_GOOGLE_MAPS_API_KEY') {
        setSafeLocation(loc + ' (Name API key missing)');
        return;
    }

    if(loc.match(/^-?\d+\.\d+,-?\d+\.\d+$/)) {
        var latlng = loc.split(',');
        var url = `https://maps.googleapis.com/maps/api/geocode/json?latlng=${latlng[0]},${latlng[1]}&key=${apiKey}`;
        
        fetch(url)
        .then(r => r.json())
        .then(data => {
            var name = loc + ' (Name unavailable)';
            if(data.status === 'OK' && data.results && data.results.length > 0) {
                // Use a simplified address component for cleaner look
                name = data.results[0].formatted_address; 
            }
            setSafeLocation(name);
        })
        .catch(function() {
            setSafeLocation(loc + ' (Error fetching name)');
        });
    } else {
        setSafeLocation(loc + ' (Invalid format)');
    }
});
<?php endif; ?>

</script>
<?php include 'footer.php'; ?>
</body>
</html>