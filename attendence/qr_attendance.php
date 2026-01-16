<?php
// qr_attendance.php

// 1. Session Management and Setup
session_start();
if (!isset($_SESSION['attendance_id'])) { header('Location: login.php'); exit; }
require_once '../admin/database.php'; 

// NOTE: Production session check logic remains same
$staff_id = $_SESSION['attendance_id'] ?? 1; 
$today = date('Y-m-d');
$msg = null; 

// 2. Attendance logic is handled via JS/AJAX below
include 'header.php'; 
?>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        --glass-bg: rgba(255, 255, 255, 0.85);
        --glass-border: 1px solid rgba(255, 255, 255, 0.5);
        --shadow-xl: 0 20px 40px rgba(0, 0, 0, 0.1);
        --scan-color: #6366f1;
        --success-color: #10b981;
    }

    body {
        font-family: 'Plus Jakarta Sans', sans-serif;
        background: #f0f2f5;
        background-image: 
            radial-gradient(at 0% 0%, hsla(253,16%,7%,1) 0, transparent 50%), 
            radial-gradient(at 50% 0%, hsla(225,39%,30%,1) 0, transparent 50%), 
            radial-gradient(at 100% 0%, hsla(339,49%,30%,1) 0, transparent 50%);
        background-attachment: fixed;
        min-height: 100vh;
        color: #1e293b;
    }

    /* --- Glassmorphism Card --- */
    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: var(--glass-border);
        border-radius: 24px;
        box-shadow: var(--shadow-xl);
        padding: 40px 30px;
        position: relative;
        overflow: hidden;
    }

    .glass-card::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; height: 6px;
        background: var(--primary-gradient);
    }

    /* --- Scanner Area --- */
    .scanner-wrapper {
        position: relative;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.25);
        border: 4px solid #fff;
        background: #000;
        min-height: 300px;
    }

    #qr-video {
        width: 100vw !important;
        max-width: 100%;
        height: 60vh !important;
        min-height: 320px;
        object-fit: cover;
        border-radius: 16px;
    }

    /* Laser Scan Animation */
    .scan-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxyZWN0IHdpZHRoPSIxMDAlIiBoZWlnaHQ9IjEwMCUiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjEpIiBzdHJva2Utd2lkdGg9IjQiLz48L3N2Zz4=');
        z-index: 10;
        pointer-events: none;
    }
    
    .scan-line {
        position: absolute;
        width: 100%;
        height: 3px;
        background: #00ffaa;
        box-shadow: 0 0 15px #00ffaa, 0 0 30px #00ffaa;
        animation: scan 2s linear infinite;
        z-index: 11;
        opacity: 0.8;
    }

    @keyframes scan {
        0% { top: 0%; opacity: 0; }
        10% { opacity: 1; }
        90% { opacity: 1; }
        100% { top: 100%; opacity: 0; }
    }

    /* --- Controls --- */
    .camera-toggles {
        background: #fff;
        padding: 5px;
        border-radius: 50px;
        display: inline-flex;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 25px;
    }
    .toggle-btn {
        border: none;
        background: transparent;
        padding: 10px 24px;
        border-radius: 50px;
        font-weight: 600;
        color: #64748b;
        font-size: 0.9rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .toggle-btn.active {
        background: var(--primary-gradient);
        color: #fff;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    .toggle-btn:hover:not(.active) { color: #333; }

    /* --- Status Text --- */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 8px 16px;
        background: #e0e7ff;
        color: #4338ca;
        border-radius: 12px;
        font-weight: 600;
        font-size: 0.85rem;
        margin-top: 20px;
    }
    .status-badge i { font-size: 1rem; margin-right: 8px; }

    /* --- High-Tech Popup Styling (SweetAlert Override) --- */
    .id-card-popup {
        padding: 0 !important;
        border-radius: 24px !important;
        overflow: hidden;
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    
    .id-card-header {
        background: var(--primary-gradient);
        padding: 30px 20px 50px;
        text-align: center;
        color: white;
        position: relative;
    }
    
    .id-avatar-wrapper {
        width: 100px;
        height: 100px;
        margin: -50px auto 15px; /* Pull up into header */
        position: relative;
        z-index: 10;
    }
    
    .id-avatar {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid #fff;
        box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        background: #fff;
    }
    
    .id-details-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        text-align: left;
        background: #f8fafc;
        padding: 15px;
        border-radius: 16px;
        margin-top: 15px;
    }
    
    .detail-item {
        display: flex;
        flex-direction: column;
    }
    .detail-label { font-size: 0.7rem; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 0.5px; }
    .detail-val { font-size: 0.9rem; color: #334155; font-weight: 600; }

    .confirm-btn-nextgen {
        background: #10b981 !important; /* Green */
        box-shadow: 0 4px 15px rgba(16, 185, 129, 0.4) !important;
        border-radius: 12px !important;
        padding: 12px 30px !important;
        font-weight: 700 !important;
        width: 100%;
        margin-top: 10px;
    }
    .cancel-btn-nextgen {
        background: #f1f5f9 !important;
        color: #64748b !important;
        border-radius: 12px !important;
        padding: 12px 30px !important;
        font-weight: 600 !important;
        width: 100%;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            
            <div class="glass-card animate__animated animate__fadeInUp">
                
                <div class="text-center mb-4">
                    <div class="d-inline-block p-3 rounded-circle mb-3" style="background: rgba(99, 102, 241, 0.1);">
                        <i class="bi bi-qr-code-scan" style="font-size: 2rem; color: #6366f1;"></i>
                    </div>
                    <h3 class="fw-bold mb-1" style="letter-spacing: -0.5px;">Attendance Check-In</h3>
                    <p class="text-muted small">Smart Office Portal &bull; v2.0</p>
                </div>

                <div class="text-center">
                    <div class="camera-toggles">
                        <button id="btnBackCamera" data-facing="environment" class="toggle-btn active">
                            <i class="bi bi-camera-rear me-1"></i> Back
                        </button>
                        <button id="btnFrontCamera" data-facing="user" class="toggle-btn">
                            <i class="bi bi-person-bounding-box me-1"></i> Front
                        </button>
                    </div>
                </div>

                <div class="scanner-wrapper mb-3">
                    <div class="scan-overlay">
                        <div class="scan-line"></div>
                    </div>
                    <div id="qr-video"></div>
                </div>

                <form method="POST" id="qrForm" class="text-center">
                    <input type="hidden" name="qr_code" id="qr_code_val">
                    
                    <div id="scan-status" class="status-badge">
                        <i class="bi bi-lightning-charge-fill"></i> Initializing AI Scanner...
                    </div>
                </form>

            </div>
            
            <p class="text-white-50 text-center small mt-4">
                &copy; <?= date('Y') ?> Secure Attendance System
            </p>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// --- NEXT-GEN JS LOGIC ---

// Global Variables
const qrCodeContainerId = "qr-video";
const scanStatus = document.getElementById('scan-status');
const html5QrCode = new Html5Qrcode(qrCodeContainerId);

function updateStatus(type, text) {
    let icon, bg, color;
    if(type === 'wait') { icon = 'bi-hourglass-split'; bg = '#e0e7ff'; color = '#4338ca'; }
    if(type === 'success') { icon = 'bi-check-circle-fill'; bg = '#d1fae5'; color = '#059669'; }
    if(type === 'error') { icon = 'bi-exclamation-octagon-fill'; bg = '#fee2e2'; color = '#dc2626'; }
    if(type === 'scanning') { icon = 'bi-qr-code-scan'; bg = '#f3f4f6'; color = '#4b5563'; }

    scanStatus.innerHTML = `<i class="bi ${icon}"></i> ${text}`;
    scanStatus.style.background = bg;
    scanStatus.style.color = color;
}

function onScanSuccess(decodedText) {
    // Basic format validation
    if (decodedText === 'OFFICE_PUNCH' || /^WORKER_ID:\d+$/.test(decodedText)) {
        
        // Pause Scanner visually
        html5QrCode.stop().then(() => {
            updateStatus('success', 'Code Detected!');
            
            // Play a beep sound (Optional)
            // let audio = new Audio('beep.mp3'); audio.play();

            let workerId = decodedText === 'OFFICE_PUNCH' ? null : decodedText.split(':')[1];
            
            // Fetch Info
            if (workerId) {
                fetch('worker_info_api.php?id=' + encodeURIComponent(workerId))
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'success') {
                        const w = data.worker;
                        checkAndShowPopup(workerId, w);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Not Found',
                            text: 'Worker ID not valid.',
                            customClass: { confirmButton: 'confirm-btn-nextgen' }
                        }).then(() => restartScanner());
                    }
                })
                .catch(() => {
                    updateStatus('error', 'Network Error');
                    setTimeout(() => restartScanner(), 2000);
                });
            } else {
                // Handle OFFICE_PUNCH logic here if different
                // For now assuming mostly worker ID scanning
                restartScanner();
            }
        });
    }
}

function checkAndShowPopup(workerId, w) {
    // Check if marked today
    let fd = new FormData();
    fd.append('action', 'check_marked');
    fd.append('worker_id', workerId);
    fd.append('date', new Date().toISOString().slice(0,10));

    fetch('../admin/attendance_qr_handler.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        let alreadyMarked = res.success && res.already_marked;
        
        // --- THIS IS THE "MORE ATTRACTIVE" POPUP ---
        Swal.fire({
            html: `
                <div class="id-card-header">
                    <h5 class="mb-0 text-white-50 text-uppercase" style="font-size:0.8rem; letter-spacing:1px;">Identify Verify</h5>
                    <h2 class="text-white fw-bold mb-0">${alreadyMarked ? 'Marked' : 'Confirm?'}</h2>
                </div>
                
                <div class="id-avatar-wrapper">
                    <img src="${w.photo || 'https://ui-avatars.com/api/?name='+w.name+'&background=random'}" class="id-avatar">
                    ${alreadyMarked 
                        ? '<div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-white p-2"></div>' 
                        : '<div class="position-absolute bottom-0 end-0 bg-warning rounded-circle border border-white p-2"></div>'
                    }
                </div>

                <div class="px-4 pb-4">
                    <h3 class="fw-bold mb-0 text-dark">${w.name}</h3>
                    <p class="text-muted mb-3">${w.designation || 'Staff Member'}</p>

                    <div class="id-details-grid">
                        <div class="detail-item">
                            <span class="detail-label">Worker ID</span>
                            <span class="detail-val">#${workerId}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Department</span>
                            <span class="detail-val">${w.department || 'General'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Phone</span>
                            <span class="detail-val">${w.phone || '--'}</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status</span>
                            <span class="detail-val text-${alreadyMarked ? 'success' : 'primary'}">${alreadyMarked ? 'Present' : 'Ready'}</span>
                        </div>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: alreadyMarked ? 'Already Marked' : '<i class="bi bi-fingerprint me-2"></i> Confirm Attendance',
            cancelButtonText: 'Cancel',
            buttonsStyling: false,
            customClass: {
                popup: 'id-card-popup',
                confirmButton: 'btn confirm-btn-nextgen',
                cancelButton: 'btn cancel-btn-nextgen mt-2'
            },
            didOpen: () => {
                if(alreadyMarked) {
                    Swal.getConfirmButton().disabled = true;
                    Swal.getConfirmButton().style.opacity = "0.6";
                    Swal.getConfirmButton().innerHTML = '<i class="bi bi-check-all"></i> Checked In';
                }
            }
        }).then((result) => {
            if (result.isConfirmed && !alreadyMarked) {
                markAttendance(workerId);
            } else {
                restartScanner();
            }
        });
    });
}

function markAttendance(workerId) {
    let formData = new FormData();
    formData.append('action', 'qr_mark');
    formData.append('worker_id', workerId);
    formData.append('date', new Date().toISOString().slice(0,10));
    formData.append('status', 'Present');
    formData.append('check_in', new Date().toLocaleTimeString('en-GB', {hour12:false}));

    fetch('../admin/attendance_qr_handler.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Attendance recorded successfully.',
                timer: 2000,
                showConfirmButton: false
            });
        } else {
            Swal.fire('Error', data.message, 'error');
        }
        restartScanner();
    });
}

function restartScanner() {
    updateStatus('scanning', 'Ready for next scan...');
    // Small delay to let UI settle
    setTimeout(() => {
        startScanner(document.querySelector('.toggle-btn.active').getAttribute('data-facing'));
    }, 500);
}

// Camera Starter
async function startScanner(facingMode) {
    updateStatus('wait', 'Starting Camera...');
    
    try {
        if (html5QrCode.isScanning) { await html5QrCode.stop(); }
    } catch (e) {}

    const config = {
        fps: 30, // Much faster scanning
        qrbox: (viewfinderWidth, viewfinderHeight) => {
            // Use full area for scanning
            return { width: viewfinderWidth, height: viewfinderHeight };
        },
        aspectRatio: 1.0
    };
    
    try {
        await html5QrCode.start({ facingMode: facingMode }, config, onScanSuccess);
        updateStatus('scanning', 'Align QR Code within frame');
    } catch (err) {
        updateStatus('error', 'Camera Permission Denied');
        console.error(err);
    }
}

// Event Listeners
document.addEventListener('DOMContentLoaded', function() {
    startScanner('environment'); // Start with back camera

    document.querySelectorAll('.toggle-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            startScanner(this.getAttribute('data-facing'));
        });
    });
});
</script>

<?php include 'footer.php'; ?>