<?php
// face_register.php

require_once 'lib_common.php';
// Ensure this path is correct based on your folder structure
// include_once 'face_attendance/facepp_api.php'; 

// Placeholder function if API file is missing for demo purposes
if (!function_exists('facepp_detect_face')) {
    function facepp_detect_face($file_path) {
        // Mock response for UI testing
        return ['faces' => [['face_token' => 'mock_token_' . time()]]];
    }
}

$msg = '';
$workers = [];

// Fetch workers for dropdown
$result = $conn->query("SELECT id, name FROM workers ORDER BY name ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $workers[] = $row;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? '';
    $name = $_POST['name'] ?? '';
    
    // Check if image file exists in $_FILES['image']
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        
        // Call Face++ API
        $api_result = facepp_detect_face($_FILES['image']['tmp_name']);
        
        if (isset($api_result['faces'][0]['face_token'])) {
            $face_token = $api_result['faces'][0]['face_token'];
            
            // Ensure table exists
            $conn->query("CREATE TABLE IF NOT EXISTS workers_face (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT,
                name VARCHAR(100),
                face_token VARCHAR(128) UNIQUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Insert into DB
            $stmt = $conn->prepare("INSERT INTO workers_face (user_id, name, face_token) VALUES (?, ?, ?)");
            $stmt->bind_param('iss', $user_id, $name, $face_token);
            
            if ($stmt->execute()) {
                $msg = ['type' => 'success', 'message' => "Face registered successfully! Token: " . substr($face_token, 0, 10) . "..."];
            } else {
                // Check for duplicate entry error
                if ($conn->errno == 1062) {
                    $msg = ['type' => 'error', 'message' => 'This face is already registered.'];
                } else {
                    $msg = ['type' => 'error', 'message' => 'Database Error: ' . $conn->error];
                }
            }
            $stmt->close();
        } else {
            $msg = ['type' => 'error', 'message' => 'No face detected in the image. Please try again.'];
        }
    } else {
        $msg = ['type' => 'error', 'message' => 'Please provide an image.'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Face | Samrat Construction</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --primary-color: #4e73df;
            --light-bg: #f8f9fc;
        }
        
        body {
            background-color: var(--light-bg);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Modern Card */
        .reg-card {
            max-width: 600px;
            margin: 2rem auto;
            border: none;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .card-header-custom {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 25px;
            text-align: center;
        }

        .card-body-custom {
            padding: 30px;
        }

        /* Video/Camera Area */
        .camera-container {
            position: relative;
            width: 100%;
            height: 320px;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scaleX(-1); /* Mirror effect */
        }

        .camera-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 250px;
            border: 2px dashed rgba(255, 255, 255, 0.6);
            border-radius: 50%; /* Oval for face guide */
            pointer-events: none;
        }

        /* Forms */
        .form-floating > .form-control { height: 3.5rem; }
        .form-floating > label { padding-top: 0.6rem; }

        /* Hidden elements */
        #canvas { display: none; }
    </style>
</head>
<body>

<?php include 'topheader.php'; ?>
<?php include 'sidenavbar.php'; ?>

<div class="container py-4">

    <div class="reg-card">
        <div class="card-header-custom">
            <h3 class="fw-bold m-0"><i class="bi bi-person-bounding-box me-2"></i>Face Registration</h3>
            <p class="mb-0 small opacity-75">Register worker face for attendance</p>
        </div>

        <div class="card-body-custom">
            
            <form id="regForm" method="post" enctype="multipart/form-data">
                
                <div class="form-floating mb-3">
                    <select name="user_id" id="workerSelect" class="form-select" required>
                        <option value="">Select Worker</option>
                        <?php foreach ($workers as $w): ?>
                            <option value="<?= $w['id'] ?>" data-name="<?= htmlspecialchars($w['name']) ?>">
                                #<?= $w['id'] ?> - <?= htmlspecialchars($w['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label>Select Worker</label>
                </div>

                <div class="form-floating mb-3">
                    <input type="text" name="name" id="workerName" class="form-control" placeholder="Name" readonly required>
                    <label>Worker Name</label>
                </div>

                <div class="btn-group w-100 mb-4" role="group">
                    <input type="radio" class="btn-check" name="regMethod" id="methodLive" value="live" checked onchange="toggleMethod()">
                    <label class="btn btn-outline-primary" for="methodLive"><i class="bi bi-camera-video me-1"></i> Live Camera</label>

                    <input type="radio" class="btn-check" name="regMethod" id="methodUpload" value="upload" onchange="toggleMethod()">
                    <label class="btn btn-outline-primary" for="methodUpload"><i class="bi bi-upload me-1"></i> File Upload</label>
                </div>

                <div id="liveRegArea">
                    <div class="camera-container">
                        <video id="video" autoplay playsinline></video>
                        <div class="camera-overlay"></div>
                        <div id="camera-loading" class="text-white small">Starting camera...</div>
                    </div>
                    
                    <button type="button" class="btn btn-success w-100 py-2 fw-bold" id="capture">
                        <i class="bi bi-camera me-1"></i> Capture & Register
                    </button>
                    
                    <input type="file" name="image" id="imageInput" style="display:none;">
                </div>

                <div id="uploadRegArea" style="display:none;">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Upload Photo</label>
                        <input type="file" id="imageUploadInput" class="form-control form-control-lg" accept="image/*">
                        <div class="form-text">Ensure the face is clearly visible and well-lit.</div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                        <i class="bi bi-cloud-upload me-1"></i> Upload & Register
                    </button>
                </div>

            </form>
            
            <canvas id="canvas"></canvas>
        </div>
    </div>

</div>

<?php include 'downfooter.php'; ?>

<script>
    // --- UI Logic ---
    
    // Auto-fill worker name
    document.getElementById('workerSelect').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const name = option.getAttribute('data-name') || '';
        document.getElementById('workerName').value = name;
    });

    // Toggle Input Method
    function toggleMethod() {
        const isLive = document.getElementById('methodLive').checked;
        const liveArea = document.getElementById('liveRegArea');
        const uploadArea = document.getElementById('uploadRegArea');
        
        if (isLive) {
            liveArea.style.display = 'block';
            uploadArea.style.display = 'none';
            startCamera();
        } else {
            liveArea.style.display = 'none';
            uploadArea.style.display = 'block';
            stopCamera();
        }
    }

    // --- Camera Logic ---
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const captureBtn = document.getElementById('capture');
    const imageInput = document.getElementById('imageInput'); // Hidden input for form
    const regForm = document.getElementById('regForm');
    let stream = null;

    function startCamera() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })
                .then(s => {
                    stream = s;
                    video.srcObject = stream;
                    document.getElementById('camera-loading').style.display = 'none';
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById('camera-loading').innerText = "Camera access denied or unavailable.";
                });
        }
    }

    function stopCamera() {
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }
    }

    // Capture Button Click
    if (captureBtn) {
        captureBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!stream) {
                Swal.fire('Error', 'Camera is not active.', 'error');
                return;
            }

            // Draw video frame to canvas
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Flip context horizontally to match mirrored video if needed, 
            // but standard canvas draw is usually fine for backend processing.
            // ctx.translate(canvas.width, 0);
            // ctx.scale(-1, 1);
            
            ctx.drawImage(video, 0, 0);

            // Convert canvas to Blob (file)
            canvas.toBlob(function(blob) {
                const file = new File([blob], 'face_capture.jpg', { type: 'image/jpeg' });
                
                // Use DataTransfer to simulate file input selection
                const dt = new DataTransfer();
                dt.items.add(file);
                imageInput.files = dt.files;

                // Show loading state
                captureBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing...';
                captureBtn.disabled = true;

                // Submit Form
                regForm.submit();
            }, 'image/jpeg', 0.95); // 0.95 quality
        });
    }

    // Form Submit Validation (for Upload Method)
    regForm.addEventListener('submit', function(e) {
        if (document.getElementById('methodUpload').checked) {
            const uploadInput = document.getElementById('imageUploadInput');
            if (uploadInput.files.length === 0) {
                e.preventDefault();
                Swal.fire('Warning', 'Please select an image file first.', 'warning');
                return;
            }
            // Copy file from visual input to hidden name="image" input
            // Note: Direct assignment of files property isn't standard in all older browsers but works in modern ones via DataTransfer if needed, 
            // but for simple upload we can just rename the input or clone it.
            // Easier: Append the upload input to form data or rename it.
            // Let's use DataTransfer to copy it to the main input.
            const dt = new DataTransfer();
            dt.items.add(uploadInput.files[0]);
            imageInput.files = dt.files;
        }
    });

    // Initialize
    startCamera();

    // --- Server Response Feedback ---
    <?php if (is_array($msg)): ?>
        Swal.fire({
            icon: '<?= $msg['type'] ?>',
            title: '<?= $msg['type'] === 'success' ? 'Registered!' : 'Error' ?>',
            text: '<?= addslashes($msg['message']) ?>',
            confirmButtonColor: 'var(--primary-color)'
        });
    <?php endif; ?>

</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>