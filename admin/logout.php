<?php
// Start session
session_start();

// Unset all session variables
$_SESSION = array();

// Delete session cookie manually
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session fully
session_destroy();
// Remove all cookies
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600);
    }
}
// Clear Remember Me cookie if set
if (isset($_COOKIE['rememberme'])) {
    setcookie('rememberme', '', time() - 3600, '/');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Logging Out...</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="font-family:Inter;background:#f7f9fc;height:100vh;display:flex;align-items:center;justify-content:center">

<script>
Swal.fire({
    icon: 'success',
    title: 'Logged out successfully!',
    text: 'Redirecting...',
    timer: 1500,
    showConfirmButton: false
}).then(() => {
    window.location.href = 'index.php';
});

// Unregister all service workers to clear session/cache
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.getRegistrations().then(function(registrations) {
        for(let registration of registrations) {
            registration.unregister();
        }
    });
}
</script>

</body>
</html>
