<?php
// logout.php - The clean and optimized logout file

// 1. Initial Session Start (Needed for session_unset/session_destroy to work)
session_start();

// Include analytics tracker if available (silently ignores if file not found)
@include_once __DIR__ . '/../admin/analytics_track.php';

// 2. Destroy Session Data
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

// 3. Remove all cookies (Crucial for complete logout)
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        // Set cookie expiration to the past for both / path and default path
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600);
    }
}

// 4. Render HTML/JS to display SweetAlert and then redirect
// We are using the HTML/JS method for a better look and feel.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out...</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Optional: Center the body content if the redirect is slow */
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e8ff 100%);
        }
    </style>
</head>
<body>
<script>
    // SweetAlert2 shows the success message and handles the redirect to login.php
    Swal.fire({
        icon: 'success',
        title: 'Logged out successfully!',
        text: 'You have been logged out. Redirecting to login...',
        showConfirmButton: false,
        timer: 1800, // 1.8 seconds delay
        timerProgressBar: true
    }).then(() => {
        // Execute the redirect after the SweetAlert timer finishes
        window.location.href = 'login.php'; 
    });
</script>
</body>
</html>