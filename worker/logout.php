<?php
session_start();
session_unset();
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logout | Samrat Construction</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body style="background:#f8fafc;">
<script>
Swal.fire({
    icon: 'success',
    title: 'Logout Successful!',
    text: 'You have been logged out. Redirecting to login...',
    showConfirmButton: false,
    timer: 1800,
    position: 'center',
    timerProgressBar: true,
    willClose: () => {
        window.location.href = 'login.php';
    }
});
</script>
</body>
</html>
