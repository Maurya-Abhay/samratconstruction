<?php 
// ... (Existing PHP code remains the same)

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../admin/lib_common.php';
@include_once __DIR__ . '/../admin/analytics_track.php';

$cid = $_SESSION['contact_id'] ?? null;
$name = 'Customer';
$photoPath = 'https://ui-avatars.com/api/?name=Customer&background=0d6efd&color=fff&size=96'; 

if ($cid) {
    if ($res = $conn->prepare('SELECT name, photo FROM contacts WHERE id=? LIMIT 1')) {
        $res->bind_param('i', $cid);
        $res->execute();
        $row = $res->get_result()->fetch_assoc();
        
        if ($row) {
            $name = $row['name'];
            $photo = $row['photo'] ?? '';
            $p = $photo ? (strpos($photo, 'uploads/') === 0 ? "../admin/{$photo}" : "../admin/uploads/{$photo}") : '';
            
            if ($p && file_exists($p)) {
                $photoPath = $p;
            } else {
                $photoPath = 'https://ui-avatars.com/api/?name=' . urlencode($name) . '&background=0d6efd&color=fff&size=96';
            }
        }
        $res->close();
    }
}

$contact_show_back_btn = $contact_show_back_btn ?? false;
$contact_back_href = $contact_back_href ?? 'dashboard.php';
?>

<!-- Manual Refresh Button HTML -->
<div style="position:fixed;bottom:18px;right:18px;z-index:9999;">
    <button id="refreshBtn" title="Refresh Data" style="background:#0d6efd;border:none;border-radius:50%;width:54px;height:54px;box-shadow:0 4px 16px #0002;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;overflow:hidden;transition:box-shadow 0.2s;">
        <span id="refreshIcon" style="display:inline-block;transition:transform 0.5s;">
            <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="white" viewBox="0 0 24 24"><path d="M12 4V1L8 5l4 4V6c3.31 0 6 2.69 6 6 0 1.06-.27 2.06-.74 2.93l1.46 1.46C19.74 15.07 20 13.57 20 12c0-4.42-3.58-8-8-8zm-6.74 3.07L3.8 4.61C2.26 6.93 2 8.43 2 10c0 4.42 3.58 8 8 8v3l4-4-4-4v3c-3.31 0-6-2.69-6-6 0-1.06.27-2.06.74-2.93z"/></svg>
        </span>
        <span id="refreshRing" style="position:absolute;top:0;left:0;width:54px;height:54px;display:none;align-items:center;justify-content:center;pointer-events:none;">
            <svg width="54" height="54" viewBox="0 0 54 54"><circle cx="27" cy="27" r="22" stroke="#fff" stroke-width="4" fill="none" stroke-dasharray="120" stroke-dashoffset="60"/></svg>
        </span>
    </button>
    <script>
    const btn = document.getElementById("refreshBtn");
    const icon = document.getElementById("refreshIcon");
    const ring = document.getElementById("refreshRing");
    btn.addEventListener("click", function(e) {
        e.preventDefault();
        icon.style.transform = "rotate(360deg)";
        ring.style.display = "flex";
        btn.style.boxShadow = "0 0 0 6px #0d6efd44";
        setTimeout(function() {
            location.reload();
        }, 600);
    });
    btn.addEventListener("mousedown", function() {
        btn.style.boxShadow = "0 0 0 10px #0d6efd33";
    });
    btn.addEventListener("mouseup", function() {
        btn.style.boxShadow = "0 4px 16px #0002";
    });
    </script>
</div>

<style>
    /* 🌟 NEW COLOR APPLIED HERE 🌟 */
    .contact-header {
        background: #2c3e50; /* Deep Indigo/Navy */
        color: #fff;
        padding: 1rem 1.5rem;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1030;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25); /* Stronger shadow with dark background */
    }

    /* Avatar Styling */
    .contact-header .avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 3px solid rgba(255, 255, 255, 0.5);
        object-fit: cover;
    }

    /* Back Button Styling */
    .contact-header .back {
        color: #fff;
        text-decoration: none;
        margin-right: 1rem;
        transition: color 0.2s;
        padding-top: 5px;
    }
    .contact-header .back:hover {
        color: rgba(255, 255, 255, 0.8);
    }

    /* Responsiveness */
    @media(max-width: 768px) {
        .contact-header {
            padding: 0.8rem 1rem;
        }
        .contact-header .avatar {
            width: 40px;
            height: 40px;
        }
    }
</style>

<div class="contact-header">
    <div class="container d-flex align-items-center">
        
        <?php if ($contact_show_back_btn): ?>
            <a class="back" href="<?= htmlspecialchars($contact_back_href) ?>" aria-label="Back">
                <i class="bi bi-arrow-left fs-4"></i>
            </a>
        <?php endif; ?>
        
        <img class="avatar me-3" src="<?= htmlspecialchars($photoPath) ?>" alt="Avatar">
        <div class="flex-grow-1 text-truncate">
            <div class="fw-bold fs-6 text-truncate" style="line-height:1;"><?= htmlspecialchars($name) ?></div>
            <small class="opacity-75 text-white">Customer Panel</small>
        </div>
        
        <div class="me-3 d-none d-md-block text-end">
            <span class="badge bg-light text-dark fw-normal p-2">
                <i class="bi bi-calendar-event me-1"></i>
                <span id="currentDateTime"></span>
            </span>
        </div>
        
        <a href="logout.php" class="btn btn-outline-light btn-sm rounded-pill"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
    </div>

    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#1976d2">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js');
            });
        }
    </script>
    
    <script>
        function contactUpdateDateTime() {
            const n = new Date();
            const o = {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            };
            const e = document.getElementById('currentDateTime');
            if (e) {
                e.textContent = new Intl.DateTimeFormat('en-US', o).format(n);
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            contactUpdateDateTime(); 
            setInterval(contactUpdateDateTime, 60000); 
        });
    </script>
</div>